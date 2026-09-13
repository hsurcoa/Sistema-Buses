<?php
class Admin extends Controller
{
    private $personalModel;
    private $rolPermisoModel;
    private $vehiculoModel;
    private $asignacionModel;
    private $terminalModel;
    private $serieBoletoModel;
    private $rutaModel;
    private $tipoBusModel;
    private $asientoModel;
    private $sessionManager;

    public function __construct()
    {
        // Inicializar Gestor de Sesiones
        require_once '../app/core/SessionManager.php';
        $this->sessionManager = SessionManager::getInstance();

        $this->personalModel = $this->model('Personal');
        $this->rolPermisoModel = $this->model('RolPermiso');
        $this->vehiculoModel = $this->model('VehiculoModel');
        $this->asignacionModel = $this->model('AsignacionModel');
        $this->terminalModel = $this->model('TerminalModel');
        $this->serieBoletoModel = $this->model('SerieBoletoModel');
        $this->rutaModel = $this->model('RutaModel');
        $this->tipoBusModel = $this->model('TipoBusModel');
        $this->asientoModel = $this->model('AsientoModel');
    }

    public function index()
    {
        $this->registrar_personal();
    }

    public function registrar_personal()
    {
        $personal = $this->personalModel->obtenerPersonal();
        $departamentos = $this->personalModel->obtenerDepartamentos();
        $roles = $this->rolPermisoModel->obtenerRoles(); // Fetch roles dynamically

        $data = [
            'title' => 'Registrar Personal',
            'personal' => $personal,
            'departamentos' => $departamentos,
            'roles' => $roles
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/personal_form', $data);
        $this->view('layouts/footer', $data);
    }

    public function guardar_personal()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            // --- SEGURIDAD: VERIFICACIÓN CSRF ---
            $token = $_POST['csrf_token'] ?? '';
            if (!$this->sessionManager->verifyCsrfToken($token)) {
                die('<script>alert("Error de Seguridad: Sesión inválida o expirada (Token CSRF incorrecto). Por favor recargue la página."); window.history.back();</script>');
            }

            // ========== PROCESAMIENTO DE IMAGEN ==========
            $foto = '';
            $oldEmail = '';

            if (!empty($_POST['id'])) {
                $usuarioActual = $this->personalModel->obtenerPersonalPorId($_POST['id']);
                $foto = $usuarioActual->foto;
                $oldEmail = $usuarioActual->email;
            }

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                // --- SEGURIDAD: VALIDACIÓN STRICTA DE IMAGEN ---
                $fileTmpPath = $_FILES['foto']['tmp_name'];
                $fileName = $_FILES['foto']['name'];
                $fileSize = $_FILES['foto']['size'];

                // 1. Validar Extensión
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($fileExtension, $allowedfileExtensions)) {
                    die('<script>alert("Error de Seguridad: Tipo de archivo no permitido. Solo se aceptan imágenes (JPG, PNG, WEBP)."); window.history.back();</script>');
                }

                // 2. Validar Tipo MIME Real (Evita archivos disfrazados)
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $fileTmpPath);
                finfo_close($finfo);

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if (!in_array($mimeType, $allowedMimeTypes)) {
                    die('<script>alert("Error de Seguridad: El archivo no es una imagen válida o está corrupto."); window.history.back();</script>');
                }

                // 3. Validar Tamaño (Máximo 5MB)
                if ($fileSize > 5 * 1024 * 1024) {
                    die('<script>alert("Error: La imagen es demasiado grande. Máximo 5MB."); window.history.back();</script>');
                }

                // Configurar directorio
                $uploadDir = '../public/img/personal/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // 4. Renombrado Seguro (Evita sobreescritura y nombres maliciosos)
                // Formato: timestamp_hashaleatorio.extensión
                $newFileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;

                // Mover archivo final
                if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                    $foto = $newFileName;
                } else {
                    die('<script>alert("Error del Servidor: No se pudo guardar la imagen."); window.history.back();</script>');
                }
            }

            // ========== DATOS DEL PERSONAL ==========
            $perfilSeleccionado = $_POST['perfil'] ?? '';

            // Validación de Seguridad: Verificar que el rol existe
            $roles = $this->rolPermisoModel->obtenerRoles();
            $rolValido = false;
            foreach ($roles as $rol) {
                if ($rol->nombre === $perfilSeleccionado) {
                    $rolValido = true;
                    break;
                }
            }

            if (!$rolValido) {
                die('<script>alert("Error: El perfil seleccionado no es válido."); window.history.back();</script>');
            }

            $data = [
                'id' => $_POST['id'] ?? '',
                'nombres' => trim($_POST['nombres']),
                'apellidos' => trim($_POST['apellidos']),
                'tipo_documento' => $_POST['tipo_documento'],
                'numero_documento' => trim($_POST['numero_documento']),
                'genero' => $_POST['genero'],
                'fecha_nacimiento' => $_POST['fecha_nacimiento'],
                'celular' => trim($_POST['celular']),
                'email' => trim($_POST['email']),
                'direccion_domicilio' => trim($_POST['direccion']),
                'departamento' => $_POST['departamento'] ?? '',
                'provincia' => $_POST['provincia'] ?? '',
                'distrito' => $_POST['distrito'] ?? '',
                'perfil' => $perfilSeleccionado,
                'foto' => $foto
            ];

            // ========== VALIDACIÓN DE EMAIL DUPLICADO ==========
            require_once '../app/models/UsuarioModel.php';
            $usuarioModel = new UsuarioModel();

            $idParaVerificar = !empty($data['id']) ? $data['id'] : null;
            if ($usuarioModel->existeEmail($data['email'], $idParaVerificar)) {
                echo '<script>alert("Error: El correo electrónico ya está registrado por otro usuario."); window.location.href="' . URLROOT . '/admin/registrar_personal";</script>';
                return;
            }

            try {
                // ========== ACTUALIZAR PERSONAL EXISTENTE ==========
                if (!empty($data['id'])) {
                    $this->personalModel->actualizarPersonal($data);

                    // Si cambió el email, actualizar credenciales
                    if (!empty($oldEmail) && $oldEmail !== $data['email']) {

                        $usuario = $data['numero_documento'];
                        $passwordPlana = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
                        $passwordHash = password_hash($passwordPlana, PASSWORD_BCRYPT);

                        $usuarioModel->actualizarCredenciales($oldEmail, $data['email'], $passwordHash);

                        // Enviar email con nuevas credenciales
                        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                            require_once '../app/core/EmailHelper.php';
                            $emailHelper = new EmailHelper();
                            $nombreCompleto = $data['nombres'] . ' ' . $data['apellidos'];
                            $emailHelper->enviarCredenciales($data['email'], $nombreCompleto, $usuario, $passwordPlana);
                        }
                    }

                    // ========== CREAR NUEVO PERSONAL ==========
                } else {
                    if ($this->personalModel->agregarPersonal($data)) {

                        // Generar credenciales
                        $usuario = $data['numero_documento'];
                        $passwordPlana = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
                        $passwordHash = password_hash($passwordPlana, PASSWORD_BCRYPT);

                        $datosUsuario = [
                            'username' => $usuario,
                            'nombres' => $data['nombres'],
                            'apellidos' => $data['apellidos'],
                            'email' => $data['email'],
                            'password_hash' => $passwordHash,
                            'perfil' => $data['perfil']
                        ];

                        // Guardar usuario en BD
                        if ($usuarioModel->registrarUsuario($datosUsuario)) {

                            // Enviar email con credenciales
                            if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                                require_once '../app/core/EmailHelper.php';
                                $emailHelper = new EmailHelper();
                                $nombreCompleto = $data['nombres'] . ' ' . $data['apellidos'];
                                $emailHelper->enviarCredenciales($data['email'], $nombreCompleto, $usuario, $passwordPlana);
                            }
                        }
                    }
                }

                header('Location: ' . URLROOT . '/admin/registrar_personal');
            } catch (PDOException $e) {
                // SEGURIDAD: No mostrar detalles de error de BD al usuario
                error_log("Error en Admin::guardar_personal: " . $e->getMessage());
                die('<script>alert("Error del Sistema: No se pudo procesar la solicitud. Por favor intente nuevamente."); window.history.back();</script>');
            }
        }
    }

    public function eliminar_personal($id)
    {
        // SEGURIDAD: Solo permitir POST para acciones destructivas
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Verificar CSRF
            $token = $_POST['csrf_token'] ?? '';
            if (!$this->sessionManager->verifyCsrfToken($token)) {
                die('Error de Seguridad: Petición no autorizada (CSRF Token Failed).');
            }

            $this->personalModel->eliminarPersonal($id);
            header('Location: ' . URLROOT . '/admin/registrar_personal');
        } else {
            // Si intentan entrar por URL directa (GET), denegar.
            die('Error 405: Método no permitido. Use la interfaz para eliminar.');
        }
    }

    public function cambiar_estado($id)
    {
        // Toggle simple
        $personal = $this->personalModel->obtenerPersonalPorId($id);
        $nuevo_estado = $personal->estado ? 0 : 1;
        $this->personalModel->cambiarEstado($id, $nuevo_estado);
        header('Location: ' . URLROOT . '/admin/registrar_personal');
    }

    // Método para ser consumido por AJAX para editar
    public function get_personal($id)
    {
        $personal = $this->personalModel->obtenerPersonalPorId($id);
        echo json_encode($personal);
    }

    public function logout()
    {
        // Iniciar sesión si no está iniciada (aunque el core ya lo hace, previene errores)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Destruir todas las variables de sesión
        $_SESSION = [];

        // Si se desea destruir la sesión completamente, borre también la cookie de sesión.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Finalmente, destruir la sesión.
        session_destroy();

        // Redirigir al login
        header('Location: ' . URLROOT . '/login.php');
        exit;
    }

    /**
     * =====================================================
     * MÉTODOS PARA ROLES Y PERMISOS
     * =====================================================
     */

    /**
     * Vista principal de Roles y Permisos
     */
    public function roles_permisos()
    {
        $roles = $this->rolPermisoModel->obtenerRoles();
        $permisosAgrupados = $this->rolPermisoModel->obtenerPermisosAgrupados();

        $data = [
            'title' => 'Roles y Permisos',
            'roles' => $roles,
            'permisosAgrupados' => $permisosAgrupados
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/roles_permisos', $data);
        $this->view('layouts/footer', $data);
    }

    /**
     * Obtener permisos de un rol (AJAX)
     */
    public function get_permisos_rol($rol_id)
    {
        header('Content-Type: application/json');
        $permisos = $this->rolPermisoModel->obtenerPermisosPorRol($rol_id);
        echo json_encode([
            'success' => true,
            'permisos' => $permisos
        ]);
        exit;
    }

    /**
     * Guardar permisos de un rol
     */
    public function guardar_permisos()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $rol_id = $data['rol_id'] ?? null;
            $permisos = $data['permisos'] ?? [];

            if (!$rol_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Rol no especificado'
                ]);
                exit;
            }

            $resultado = $this->rolPermisoModel->actualizarPermisosRol($rol_id, $permisos);

            echo json_encode([
                'success' => $resultado,
                'message' => $resultado ? 'Permisos actualizados correctamente' : 'Error al actualizar permisos'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
        }
        exit;
    }

    /**
     * Crear nuevo rol (AJAX)
     */
    public function crear_rol()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $nombre = trim($data['nombre'] ?? '');
            $descripcion = trim($data['descripcion'] ?? '');

            if (empty($nombre)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El nombre del rol es obligatorio'
                ]);
                exit;
            }

            $datosRol = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'activo' => 1
            ];

            $rol_id = $this->rolPermisoModel->crearRol($datosRol);

            if ($rol_id) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Rol creado correctamente',
                    'rol_id' => $rol_id
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al crear el rol'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
        }
        exit;
    }
    public function registrar_buses()
    {
        $vehiculos = $this->vehiculoModel->listarVehiculos();

        // ⭐ NUEVO: Cargar tipos de buses para el formulario
        $tipoBusModel = $this->model('TipoBusModel');
        $tipos_buses = $tipoBusModel->listarTiposBuses();  // ✅ CORREGIDO

        $data = [
            'title' => 'Registrar Buses',
            'vehiculos' => $vehiculos,
            'tipos_buses' => $tipos_buses  // ⭐ NUEVO: Pasar tipos de buses a la vista
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/registrar_buses', $data);
        $this->view('layouts/footer', $data);
    }

    public function asignar_buses()
    {
        $data = [
            'title' => 'Asignar Buses',
            'choferes' => $this->asignacionModel->obtenerChoferes(),
            'copilotos' => $this->asignacionModel->obtenerCopilotos(),
            'buses' => $this->asignacionModel->obtenerBuses(),
            'asignaciones' => $this->asignacionModel->listarAsignaciones()
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/asignar_buses', $data);
        $this->view('layouts/footer', $data);
    }

    public function guardar_asignacion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            // Limpiar cualquier salida previa para evitar corrupción del JSON
            ob_clean();

            // Establecer headers para respuesta JSON
            header('Content-Type: application/json; charset=utf-8');

            // Recoger datos del formulario
            $chofer_id = $_POST['chofer_id'] ?? '';
            $bus_id = $_POST['bus_id'] ?? '';
            $copiloto_id = !empty($_POST['copiloto_id']) ? $_POST['copiloto_id'] : null;

            // Validación básica (Solo chofer y bus son obligatorios)
            if (empty($chofer_id) || empty($bus_id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El Chofer y el Bus son obligatorios.'
                ]);
                exit;
            }

            // Intentar guardar la asignación
            try {
                if ($this->asignacionModel->crearAsignacion($chofer_id, $bus_id, $copiloto_id)) {
                    // ÉXITO
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'El bus ha sido asignado a la ruta correctamente.'
                    ]);
                } else {
                    // ERROR GENÉRICO
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error al guardar la asignación. Por favor, intente nuevamente.'
                    ]);
                }
            } catch (Exception $e) {
                // SEGURIDAD: Loggear error real y ocultar detalle al usuario
                error_log("Error en Admin::guardar_asignacion: " . $e->getMessage());

                echo json_encode([
                    'status' => 'error',
                    'message' => 'Ocurrió un error interno al procesar la asignación.'
                ]);
            }
            exit;
        } else {
            header('Location: ' . URLROOT . '/admin/asignar_buses');
        }
    }
    public function registrar_terminal()
    {
        $terminales = $this->terminalModel->listarTerminales();

        $data = [
            'title' => 'Registrar Terminal',
            'terminales' => $terminales
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/registrar_terminal', $data);
        $this->view('layouts/footer', $data);
    }

    public function guardar_terminal()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'id' => $_POST['id'] ?? '',
                'nombre_sede' => trim($_POST['nombre_sede']),
                'direccion' => trim($_POST['direccion']),
                'numero_oficina' => trim($_POST['numero_oficina'])
            ];

            if (empty($data['nombre_sede']) || empty($data['direccion'])) {
                echo "<script>alert('Complete los campos obligatorios'); window.location.href='" . URLROOT . "/admin/registrar_terminal';</script>";
                return;
            }

            if (!empty($data['id'])) {
                // Actualizar
                if ($this->terminalModel->actualizarTerminal($data)) {
                    echo "<script>alert('Terminal actualizado'); window.location.href='" . URLROOT . "/admin/registrar_terminal';</script>";
                } else {
                    echo "<script>alert('Error al actualizar'); window.location.href='" . URLROOT . "/admin/registrar_terminal';</script>";
                }
            } else {
                // Crear
                if ($this->terminalModel->agregarTerminal($data)) {
                    echo "<script>alert('Terminal registrado'); window.location.href='" . URLROOT . "/admin/registrar_terminal';</script>";
                } else {
                    echo "<script>alert('Error al registrar'); window.location.href='" . URLROOT . "/admin/registrar_terminal';</script>";
                }
            }
        } else {
            header('Location: ' . URLROOT . '/admin/registrar_terminal');
        }
    }

    public function cambiar_estado_terminal($id)
    {
        $terminal = $this->terminalModel->obtenerTerminal($id);
        if ($terminal) {
            $nuevo_estado = $terminal->estado ? 0 : 1;
            $this->terminalModel->cambiarEstado($id, $nuevo_estado);
        }
        header('Location: ' . URLROOT . '/admin/registrar_terminal');
    }

    public function eliminar_terminal($id)
    {
        if ($this->terminalModel->eliminarTerminal($id)) {
            // Optional: You could set a session flash message here
            // Eliminación de terminal realizada
        }
        header('Location: ' . URLROOT . '/admin/registrar_terminal');
    }

    public function eliminar_asignacion($id)
    {
        if ($this->asignacionModel->eliminarAsignacion($id)) {
            // Optional: You could set a session flash message here
        }
        header('Location: ' . URLROOT . '/admin/asignar_buses');
    }

    /**
     * Método para obtener los datos de una asignación específica (AJAX)
     */
    public function obtener_asignacion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Limpiar cualquier salida previa
            ob_clean();

            // Establecer headers para respuesta JSON
            header('Content-Type: application/json; charset=utf-8');

            // Obtener el ID de la asignación
            $id = isset($_POST['id']) ? trim($_POST['id']) : '';

            // Validar que se recibió el ID
            if (empty($id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se proporcionó el ID de la asignación.'
                ]);
                exit;
            }

            // Obtener los datos de la asignación desde el modelo
            $asignacion = $this->asignacionModel->obtenerAsignacionPorId($id);

            if ($asignacion) {
                echo json_encode([
                    'status' => 'success',
                    'id_chofer' => $asignacion->chofer_id,
                    'id_bus' => $asignacion->bus_id,
                    'id_copiloto' => $asignacion->copiloto_id
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se encontró la asignación con el ID proporcionado.'
                ]);
            }
            exit;
        } else {
            // Si intentan entrar por GET
            header('Location: ' . URLROOT . '/admin/asignar_buses');
        }
    }

    /**
     * Método para actualizar los datos de una asignación (AJAX)
     */
    public function editar_asignacion()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Limpiar cualquier salida previa
            ob_clean();

            // Establecer headers para respuesta JSON
            header('Content-Type: application/json; charset=utf-8');

            // Recoger datos del formulario
            $datos = [
                'id' => trim($_POST['id'] ?? ''),
                'chofer_id' => trim($_POST['chofer_id'] ?? ''),
                'bus_id' => trim($_POST['bus_id'] ?? ''),
                'copiloto_id' => !empty($_POST['copiloto_id']) ? $_POST['copiloto_id'] : null
            ];

            // Validación básica
            if (empty($datos['id']) || empty($datos['chofer_id']) || empty($datos['bus_id'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Chofer y Bus son obligatorios.'
                ]);
                exit;
            }

            // Intentar actualizar en la base de datos
            try {
                if ($this->asignacionModel->actualizarAsignacion($datos)) {
                    // Éxito
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'La asignación ha sido actualizada correctamente.'
                    ]);
                } else {
                    // Fallo genérico
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Ocurrió un error al actualizar la asignación. Por favor, intente nuevamente.'
                    ]);
                }
            } catch (Exception $e) {
                // Capturar errores
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la asignación: ' . $e->getMessage()
                ]);
            }
            exit;
        } else {
            // Si intentan entrar por GET
            header('Location: ' . URLROOT . '/admin/asignar_buses');
        }
    }

    public function registrar_serie_boletos()
    {
        $series = $this->serieBoletoModel->listarSeries();
        $vendedores = $this->personalModel->obtenerPersonal();
        $sedes = $this->terminalModel->listarTerminales();

        $data = [
            'title' => 'Registrar Series de Boletos',
            'series' => $series,
            'vendedores' => $vendedores,
            'sedes' => $sedes
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/registrar_serie_boletos', $data);
        $this->view('layouts/footer', $data);
    }

    public function guardar_serie_boletos()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'id' => $_POST['id'] ?? '',
                'id_vendedor' => $_POST['id_vendedor'] ?? '',
                'id_sede' => $_POST['id_sede'] ?? '',
                'numero_serie' => trim($_POST['numero_serie'] ?? '')
            ];

            if (empty($data['id_vendedor']) || empty($data['id_sede']) || empty($data['numero_serie'])) {
                echo "<script>alert('Complete todos los campos'); window.location.href='" . URLROOT . "/admin/registrar_serie_boletos';</script>";
                return;
            }

            if (!empty($data['id'])) {
                if ($this->serieBoletoModel->actualizarSerie($data)) {
                    echo "<script>alert('Serie actualizada'); window.location.href='" . URLROOT . "/admin/registrar_serie_boletos';</script>";
                } else {
                    echo "<script>alert('Error al actualizar'); window.location.href='" . URLROOT . "/admin/registrar_serie_boletos';</script>";
                }
            } else {
                if ($this->serieBoletoModel->agregarSerie($data)) {
                    echo "<script>alert('Serie registrada'); window.location.href='" . URLROOT . "/admin/registrar_serie_boletos';</script>";
                } else {
                    echo "<script>alert('Error al registrar'); window.location.href='" . URLROOT . "/admin/registrar_serie_boletos';</script>";
                }
            }
        } else {
            header('Location: ' . URLROOT . '/admin/registrar_serie_boletos');
        }
    }

    public function cambiar_estado_serie($id)
    {
        $serie = $this->serieBoletoModel->obtenerSerie($id);
        if ($serie) {
            $nuevo_estado = $serie->estado ? 0 : 1;
            $this->serieBoletoModel->cambiarEstado($id, $nuevo_estado);
        }
        header('Location: ' . URLROOT . '/admin/registrar_serie_boletos');
    }

    /**
     * =====================================================
     * MÉTODOS PARA RUTAS Y PARADAS
     * =====================================================
     */

    public function rutas_paradas()
    {
        $rutas = $this->rutaModel->listarRutas(100, 0);

        $data = [
            'title' => 'Rutas y Paradas',
            'rutas' => $rutas
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/registrar_rutas_paradas', $data);
        $this->view('layouts/footer', $data);
    }

    public function guardar_ruta()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Obtener datos del formulario
            $id = $_POST['id'] ?? '';
            $origen = trim($_POST['origen'] ?? '');
            $destino = trim($_POST['destino'] ?? '');

            // Convertir a MAYÚSCULAS para mantener uniformidad en la BD
            $origen = strtoupper($origen);
            $destino = strtoupper($destino);

            // Validar campos vacíos
            if (empty($origen) || empty($destino)) {
                echo "<script>alert('Complete todos los campos obligatorios'); window.location.href='" . URLROOT . "/admin/rutas_paradas';</script>";
                return;
            }

            if (!empty($id)) {
                // Actualizar ruta existente
                $resultado = $this->rutaModel->actualizarRuta($id, $origen, $destino, 1);

                if ($resultado === true) {
                    echo "<script>alert('Ruta actualizada correctamente'); window.location.href='" . URLROOT . "/admin/rutas_paradas';</script>";
                } else {
                    echo "<script>alert('" . $resultado . "'); window.location.href='" . URLROOT . "/admin/rutas_paradas';</script>";
                }
            } else {
                // Registrar nueva ruta
                $resultado = $this->rutaModel->registrarRuta($origen, $destino);

                if ($resultado === true) {
                    echo "<script>alert('Ruta registrada correctamente'); window.location.href='" . URLROOT . "/admin/rutas_paradas';</script>";
                } else {
                    // Mostrar mensaje de error del modelo (ej: "El origen y el destino no pueden ser iguales")
                    echo "<script>alert('" . $resultado . "'); window.location.href='" . URLROOT . "/admin/rutas_paradas';</script>";
                }
            }
        } else {
            header('Location: ' . URLROOT . '/admin/rutas_paradas');
        }
    }

    public function cambiar_estado_ruta($id)
    {
        $ruta = $this->rutaModel->obtenerRutaPorId($id);
        if ($ruta) {
            $nuevo_estado = $ruta->estado ? 0 : 1;
            $this->rutaModel->cambiarEstado($id, $nuevo_estado);
        }
        header('Location: ' . URLROOT . '/admin/rutas_paradas');
    }

    public function eliminar_ruta($id)
    {
        $resultado = $this->rutaModel->eliminarRuta($id);

        if ($resultado === true) {
            echo "<script>alert('Ruta eliminada correctamente'); window.location.href='" . URLROOT . "/admin/rutas_paradas';</script>";
        } else {
            // Mostrar el mensaje de error específico del modelo
            echo "<script>alert('" . addslashes($resultado) . "'); window.location.href='" . URLROOT . "/admin/rutas_paradas';</script>";
        }
    }

    /**
     * =====================================================
     * MÉTODOS PARA TIPOS DE BUSES
     * =====================================================
     */

    public function tipos_buses()
    {
        $tiposBuses = $this->tipoBusModel->listarTiposBuses();

        $data = [
            'title' => 'Tipos de Buses',
            'tiposBuses' => $tiposBuses
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/tipos_buses', $data);
        $this->view('layouts/footer', $data);
    }

    public function guardar_tipo_bus()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'id' => $_POST['id'] ?? '',
                'nombre' => trim($_POST['nombre'] ?? ''),
                'capacidad' => intval($_POST['capacidad'] ?? 0),
                'pisos' => intval($_POST['pisos'] ?? 1),
                'configuracion_asientos' => $_POST['configuracion_asientos'] ?? ''
            ];

            if (empty($data['nombre']) || empty($data['capacidad'])) {
                echo "<script>alert('Complete todos los campos obligatorios'); window.location.href='" . URLROOT . "/admin/tipos_buses';</script>";
                return;
            }

            if (!empty($data['id'])) {
                if ($this->tipoBusModel->actualizarTipoBus($data)) {
                    echo "<script>alert('Tipo de bus actualizado'); window.location.href='" . URLROOT . "/admin/tipos_buses';</script>";
                } else {
                    echo "<script>alert('Error al actualizar'); window.location.href='" . URLROOT . "/admin/tipos_buses';</script>";
                }
            } else {
                if ($this->tipoBusModel->agregarTipoBus($data)) {
                    echo "<script>alert('Tipo de bus registrado'); window.location.href='" . URLROOT . "/admin/tipos_buses';</script>";
                } else {
                    echo "<script>alert('Error al registrar'); window.location.href='" . URLROOT . "/admin/tipos_buses';</script>";
                }
            }
        } else {
            header('Location: ' . URLROOT . '/admin/tipos_buses');
        }
    }

    public function cambiar_estado_tipo_bus($id)
    {
        $tipoBus = $this->tipoBusModel->obtenerTipoBus($id);
        if ($tipoBus) {
            $nuevo_estado = $tipoBus->estado ? 0 : 1;
            $this->tipoBusModel->cambiarEstado($id, $nuevo_estado);
        }
        header('Location: ' . URLROOT . '/admin/tipos_buses');
    }

    /**
     * Eliminar (desactivar) un tipo de bus (AJAX)
     */
    public function eliminar_tipo_bus()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Limpiar cualquier salida previa
            ob_clean();

            // Establecer headers para respuesta JSON
            header('Content-Type: application/json; charset=utf-8');

            // Obtener el ID del tipo de bus
            $id = isset($_POST['id']) ? trim($_POST['id']) : '';

            // Validar que se recibió el ID
            if (empty($id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se proporcionó el ID del tipo de bus.'
                ]);
                exit;
            }

            // Intentar eliminar (desactivar) el tipo de bus
            try {
                if ($this->tipoBusModel->eliminarTipoBus($id)) {
                    // Éxito
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'El tipo de bus ha sido eliminado correctamente del sistema.'
                    ]);
                } else {
                    // Fallo genérico
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No se pudo eliminar el tipo de bus. Por favor, intente nuevamente.'
                    ]);
                }
            } catch (Exception $e) {
                // Capturar errores
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al eliminar el tipo de bus: ' . $e->getMessage()
                ]);
            }
            exit;
        } else {
            // Si intentan entrar por GET
            header('Location: ' . URLROOT . '/admin/tipos_buses');
        }
    }

    /**
     * =====================================================
     * MÉTODOS PARA GESTIÓN DE ASIENTOS
     * =====================================================
     */

    /**
     * Vista del mapa de asientos para un tipo de bus
     */
    public function mapa_asientos($tipoBusId = null)
    {
        if (!$tipoBusId) {
            header('Location: ' . URLROOT . '/admin/tipos_buses');
            return;
        }

        // Obtener información del tipo de bus
        $tipoBus = $this->tipoBusModel->obtenerTipoBus($tipoBusId);

        if (!$tipoBus) {
            echo "<script>alert('Tipo de bus no encontrado'); window.location.href='" . URLROOT . "/admin/tipos_buses';</script>";
            return;
        }

        // Obtener asientos del tipo de bus
        $asientos = $this->asientoModel->obtenerAsientosPorBus($tipoBusId);

        $data = [
            'title' => 'Mapa de Asientos - ' . $tipoBus->nombre,
            'busData' => [
                'id' => $tipoBus->id,
                'nombre' => $tipoBus->nombre,
                'capacidad' => $tipoBus->capacidad,
                'pisos' => $tipoBus->pisos,
                'configuracion_asientos' => $tipoBus->configuracion_asientos
            ],
            'asientos' => $asientos
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/mapa_asientos', $data);
        $this->view('layouts/footer', $data);
    }

    /**
     * Obtener asientos disponibles para una ruta y fecha (AJAX)
     */
    public function obtener_asientos_disponibles()
    {
        header('Content-Type: application/json');

        $rutaId = $_GET['ruta_id'] ?? 0;
        $fecha = $_GET['fecha'] ?? '';
        $busId = $_GET['bus_id'] ?? 0;

        if (!$rutaId || !$fecha || !$busId) {
            echo json_encode([
                'success' => false,
                'message' => 'Parámetros faltantes'
            ]);
            exit;
        }

        try {
            $asientos = $this->asientoModel->obtenerAsientosDisponibles($rutaId, $fecha, $busId);

            echo json_encode([
                'success' => true,
                'asientos' => $asientos
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener asientos: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Reservar asientos temporalmente (AJAX)
     */
    public function reservar_asientos()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            exit;
        }

        // Obtener datos JSON
        $input = json_decode(file_get_contents('php://input'), true);

        $asientos = $input['asientos'] ?? [];
        $rutaId = $input['ruta_id'] ?? 0;
        $fecha = $input['fecha'] ?? '';
        $usuarioId = $_SESSION['usuario_id'] ?? 0;

        // Validar datos
        if (empty($asientos) || !$rutaId || !$fecha || !$usuarioId) {
            echo json_encode([
                'success' => false,
                'message' => 'Datos incompletos'
            ]);
            exit;
        }

        // Extraer IDs de asientos
        $asientoIds = array_map(function ($a) {
            return $a['numero'] ?? $a['id'] ?? 0;
        }, $asientos);

        try {
            // Reservar en el modelo
            $resultado = $this->asientoModel->reservarAsientos(
                $asientoIds,
                $usuarioId,
                $rutaId,
                $fecha
            );

            if ($resultado) {
                // Guardar en sesión para el siguiente paso
                $_SESSION['asientos_reservados'] = $asientos;
                $_SESSION['reserva_expira'] = time() + (15 * 60); // 15 minutos

                echo json_encode([
                    'success' => true,
                    'message' => 'Asientos reservados exitosamente',
                    'expira_en' => 900 // segundos
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al reservar asientos. Algunos pueden estar ocupados.'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Generar asientos para un tipo de bus
     */
    public function generar_asientos($tipoBusId = null)
    {
        if (!$tipoBusId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de tipo de bus no especificado'
            ]);
            exit;
        }

        $tipoBus = $this->tipoBusModel->obtenerTipoBus($tipoBusId);

        if (!$tipoBus) {
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de bus no encontrado'
            ]);
            exit;
        }

        // Generar configuración por defecto
        $configuracion = $this->asientoModel->generarConfiguracionDefecto(
            $tipoBus->capacidad,
            $tipoBus->pisos
        );

        // Crear asientos en la base de datos
        $resultado = $this->asientoModel->crearAsientosPorTipo($tipoBusId, $configuracion);

        if ($resultado) {
            echo json_encode([
                'success' => true,
                'message' => 'Asientos generados correctamente',
                'total' => count($configuracion)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar asientos'
            ]);
        }
        exit;
    }

    /**
     * Limpiar reservas expiradas (puede ser llamado por CRON o manualmente)
     */
    public function limpiar_reservas_expiradas()
    {
        $resultado = $this->asientoModel->limpiarReservasExpiradas();

        echo json_encode([
            'success' => $resultado,
            'message' => $resultado ? 'Reservas limpiadas' : 'Error al limpiar reservas'
        ]);
        exit;
    }

    /**
     * Obtener estadísticas de asientos de un tipo de bus
     */
    public function estadisticas_asientos($tipoBusId)
    {
        header('Content-Type: application/json');

        $estadisticas = $this->asientoModel->obtenerEstadisticasAsientos($tipoBusId);

        echo json_encode([
            'success' => true,
            'estadisticas' => $estadisticas
        ]);
        exit;
    }
    /**
     * ==========================================
     *  ENDPOINT JSON PARA PARADAS Y ENCOMIENDAS
     * ==========================================
     */

    /**
     * AJAX: Devuelve paradas y tipos de paquete para poblar los selects del frontend.
     * Ruta de acceso: /admin/obtener_info_ruta_json/{ruta_id}
     */
    public function obtener_info_ruta_json($rutaId)
    {
        // Limpiar buffer para asegurar JSON puro
        ob_clean();
        header('Content-Type: application/json');

        if (empty($rutaId)) {
            echo json_encode(['status' => 'error', 'message' => 'ID de ruta no especificado']);
            exit;
        }

        // Obtener datos usando el modelo RutaModel
        $paradas = $this->rutaModel->obtenerParadasPorRuta($rutaId);
        $tiposPaquete = $this->rutaModel->obtenerTiposEncomienda();

        // Estructurar respuesta para fácil consumo en JS
        $data = [
            'status' => 'success',
            'ruta_id' => $rutaId,
            'paradas' => array_map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre_parada,
                    'precio_pasaje' => $p->precio_pasaje,
                    'precio_encomienda' => $p->precio_base_encomienda,
                    // Texto pre-formateado para el <option>
                    'label_pasajero' => $p->nombre_parada . ' - Bs ' . number_format($p->precio_pasaje, 2),
                    'label_encomienda' => $p->nombre_parada . ' - Base Bs ' . number_format($p->precio_base_encomienda, 2)
                ];
            }, $paradas),
            'catalogos' => [
                'paquetes' => $tiposPaquete
            ]
        ];

        echo json_encode($data);
        exit;
    }

    /**
     * AJAX: Calcular cotización en el servidor (Seguridad extra)
     */
    public function cotizar_envio()
    {
        ob_clean();
        header('Content-Type: application/json');

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $paradaId = $data['parada_id'] ?? null;
        $tipo = $data['tipo'] ?? 'pasajero'; // pasajero | encomienda
        $paqueteId = $data['paquete_id'] ?? null;


        if (!$paradaId) {
            echo json_encode(['status' => false, 'message' => 'Faltan datos']);
            exit;
        }

        $resultado = $this->rutaModel->calcularPrecioDinamico($paradaId, $tipo, $paqueteId);
        echo json_encode($resultado);
        exit;
    }
}
