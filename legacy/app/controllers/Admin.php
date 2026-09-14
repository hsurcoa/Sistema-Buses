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

    // =====================================================================
    //  USUARIOS DEL SISTEMA (cuentas de acceso por rol)
    //  Solo Administrador. Las cuentas con historial (boletos, cajas,
    //  encomiendas) no se borran: se desactivan.
    // =====================================================================

    public function usuarios()
    {
        if (!$this->esAdministrador()) {
            header('Location: ' . URLROOT . '/dashboard');
            exit;
        }

        require_once '../app/models/UsuarioModel.php';
        $usuarioModel = new UsuarioModel();

        $data = [
            'title' => 'Usuarios del sistema',
            'usuarios' => $usuarioModel->listarUsuarios(),
            'roles' => $usuarioModel->listarRolesActivos(),
            'sucursales' => Sucursal::listar(),
            'usuario_actual_id' => (int) $this->sessionManager->getUserId(),
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/usuarios', $data);
        $this->view('layouts/footer', $data);
    }

    /** Crear o editar un usuario (AJAX, JSON). */
    public function guardar_usuario()
    {
        $this->prepararRespuestaUsuarios();
        require_once '../app/models/UsuarioModel.php';
        $usuarioModel = new UsuarioModel();

        $id = (int) ($_POST['id'] ?? 0);
        $d = [
            'id' => $id,
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'email' => strtolower(trim($_POST['email'] ?? '')),
            'username' => trim($_POST['username'] ?? ''),
            'nro_documento' => trim($_POST['nro_documento'] ?? ''),
            'celular' => trim($_POST['celular'] ?? ''),
            'rol_id' => (int) ($_POST['rol_id'] ?? 0),
            'sucursal_id' => (int) ($_POST['sucursal_id'] ?? 0),
            'activo' => !empty($_POST['activo']),
        ];
        $password = (string) ($_POST['password'] ?? '');
        $generar = !empty($_POST['generar_password']);
        $actual = $id ? $usuarioModel->obtenerUsuario($id) : null;
        $miId = (int) $this->sessionManager->getUserId();

        $errores = [];
        if ($id && !$actual) {
            $this->responderUsuarios(false, 'El usuario no existe.');
        }
        if ($d['nombres'] === '' || $d['apellidos'] === '') {
            $errores[] = 'Nombres y apellidos son obligatorios.';
        }
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Ingrese un correo electrónico válido (se usa para iniciar sesión).';
        } elseif ($usuarioModel->campoEnUso('email', $d['email'], $id)) {
            $errores[] = 'El correo ' . $d['email'] . ' ya pertenece a otro usuario.';
        }
        if ($d['username'] !== '' && $usuarioModel->campoEnUso('username', $d['username'], $id)) {
            $errores[] = 'El nombre de usuario ' . $d['username'] . ' ya está en uso.';
        }
        $rol = $usuarioModel->rolActivo($d['rol_id']);
        if (!$rol) {
            $errores[] = 'Seleccione un rol activo.';
        }
        // Cada vendedor trabaja en una sola sucursal; Administrador y Supervisor pueden no tener una fija
        $sucursal = $d['sucursal_id'] ? Sucursal::obtener($d['sucursal_id']) : null;
        if ($d['sucursal_id'] && (!$sucursal || !$sucursal->estado)) {
            $errores[] = 'La sucursal seleccionada no existe o está inactiva.';
        }
        if (!$d['sucursal_id'] && $rol && !in_array($rol->nombre, ['Administrador', 'Supervisor'], true)) {
            $errores[] = 'Asigne la sucursal donde trabaja este usuario (obligatoria para el rol ' . $rol->nombre . ').';
        }

        if ($generar) {
            $password = $this->generarPassword();
        } elseif ($password !== '' || !$id) {
            if (strlen($password) < 8) {
                $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($password !== (string) ($_POST['password_confirmacion'] ?? '')) {
                $errores[] = 'La confirmación de la contraseña no coincide.';
            }
        }

        // Protecciones sobre la propia cuenta y el ultimo administrador
        if ($id && $id === $miId) {
            if (!$d['activo']) {
                $errores[] = 'No puede desactivar su propia cuenta.';
            }
            if ($rol && $rol->nombre !== 'Administrador') {
                $errores[] = 'No puede quitarse a sí mismo el rol Administrador.';
            }
        }
        if ($id && $actual && $actual->rol === 'Administrador' && $actual->estado === 'activo'
            && (!$d['activo'] || ($rol && $rol->nombre !== 'Administrador'))
            && $usuarioModel->contarAdministradoresActivos($id) === 0) {
            $errores[] = 'Debe quedar al menos un Administrador activo.';
        }

        if ($errores) {
            $this->responderUsuarios(false, implode(' ', $errores));
        }

        $d['password_hash'] = $password !== '' ? password_hash($password, PASSWORD_BCRYPT) : null;

        try {
            if ($id) {
                $usuarioModel->actualizarUsuario($d);
            } else {
                $id = $usuarioModel->crearUsuario($d);
            }
        } catch (Exception $e) {
            error_log('Admin::guardar_usuario: ' . $e->getMessage());
            $this->responderUsuarios(false, 'No se pudo guardar el usuario.');
        }

        $this->responderUsuarios(true, $d['id'] ? 'Usuario actualizado.' : 'Usuario creado.', [
            // Solo se devuelve la contraseña cuando la genero el sistema, para mostrarla una vez
            'password_generada' => $generar ? $password : null,
            'email' => $d['email'],
        ]);
    }

    /** Activar o desactivar una cuenta (AJAX, JSON). */
    public function estado_usuario()
    {
        $this->prepararRespuestaUsuarios();
        require_once '../app/models/UsuarioModel.php';
        $usuarioModel = new UsuarioModel();

        $id = (int) ($_POST['id'] ?? 0);
        $activar = !empty($_POST['activo']);
        $usuario = $usuarioModel->obtenerUsuario($id);

        if (!$usuario) {
            $this->responderUsuarios(false, 'El usuario no existe.');
        }
        if (!$activar && $id === (int) $this->sessionManager->getUserId()) {
            $this->responderUsuarios(false, 'No puede desactivar su propia cuenta.');
        }
        if (!$activar && $usuario->rol === 'Administrador' && $usuarioModel->contarAdministradoresActivos($id) === 0) {
            $this->responderUsuarios(false, 'Debe quedar al menos un Administrador activo.');
        }

        $usuarioModel->cambiarEstadoUsuario($id, $activar);
        $this->responderUsuarios(true, $activar ? 'Usuario activado.' : 'Usuario desactivado: ya no puede iniciar sesión.');
    }

    /** Eliminar definitivamente una cuenta SIN historial (AJAX, JSON). */
    public function eliminar_usuario()
    {
        $this->prepararRespuestaUsuarios();
        require_once '../app/models/UsuarioModel.php';
        $usuarioModel = new UsuarioModel();

        $id = (int) ($_POST['id'] ?? 0);
        $usuario = $usuarioModel->obtenerUsuario($id);

        if (!$usuario) {
            $this->responderUsuarios(false, 'El usuario no existe.');
        }
        if ($id === (int) $this->sessionManager->getUserId()) {
            $this->responderUsuarios(false, 'No puede eliminar su propia cuenta.');
        }
        if ($usuario->rol === 'Administrador' && $usuario->estado === 'activo' && $usuarioModel->contarAdministradoresActivos($id) === 0) {
            $this->responderUsuarios(false, 'Debe quedar al menos un Administrador activo.');
        }

        if (!$usuarioModel->eliminarUsuarioSinHistorial($id)) {
            $this->responderUsuarios(false, 'Este usuario tiene ventas, cajas o encomiendas registradas: no se puede eliminar sin perder el historial. Desactívelo en su lugar.');
        }

        // Rol sincronizado en spatie (Laravel) de esa cuenta
        $db = new Database();
        $db->query("DELETE FROM spatie_model_has_roles WHERE model_id = :id AND model_type = :tipo");
        $db->bind(':id', $id);
        $db->bind(':tipo', 'App\\Models\\Usuario');
        $db->execute();

        $this->responderUsuarios(true, 'Usuario eliminado.');
    }

    private function esAdministrador()
    {
        return ($_SESSION['rol'] ?? '') === 'Administrador';
    }

    private function prepararRespuestaUsuarios()
    {
        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderUsuarios(false, 'Método no permitido.');
        }
        if (!$this->esAdministrador()) {
            $this->responderUsuarios(false, 'Solo un Administrador puede gestionar usuarios.');
        }
        if (!$this->sessionManager->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->responderUsuarios(false, 'La sesión expiró o el formulario es inválido. Recargue la página.');
        }
    }

    private function responderUsuarios($ok, $mensaje, array $extra = [])
    {
        echo json_encode(array_merge(['status' => $ok ? 'success' : 'error', 'message' => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function generarPassword()
    {
        // Sin caracteres ambiguos (0/O, 1/l/I) para dictarla o copiarla sin errores
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        return $password;
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
        $tipos_buses = $tipoBusModel->listarTiposParaFlota();

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
        $historial = !empty($_GET['historial']);

        $data = [
            'title' => 'Asignar Buses',
            'choferes' => $this->asignacionModel->obtenerChoferes(),
            'copilotos' => $this->asignacionModel->obtenerCopilotos(),
            'buses' => $this->asignacionModel->obtenerBuses(),
            'asignaciones' => $this->asignacionModel->listarAsignaciones($historial),
            'historial' => $historial,
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/asignar_buses', $data);
        $this->view('layouts/footer', $data);
    }

    /**
     * Crear o editar una asignacion (AJAX).
     * Responde status: success | error | conflict. En "conflict" el frontend
     * muestra los choques y, si el usuario confirma, reenvia con reemplazar=1.
     */
    public function guardar_asignacion()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/admin/asignar_buses');
            return;
        }

        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $datos = [
            'id' => trim($_POST['id'] ?? ''),
            'chofer_id' => trim($_POST['chofer_id'] ?? ''),
            'bus_id' => trim($_POST['bus_id'] ?? ''),
            'copiloto_id' => trim($_POST['copiloto_id'] ?? ''),
        ];
        $reemplazar = !empty($_POST['reemplazar']);

        if ($datos['chofer_id'] === '' || $datos['bus_id'] === '') {
            echo json_encode(['status' => 'error', 'message' => 'El chofer y el bus son obligatorios.']);
            exit;
        }

        try {
            $validacion = $this->asignacionModel->validar($datos);
            if ($validacion['errores']) {
                echo json_encode(['status' => 'error', 'message' => implode(' ', $validacion['errores'])]);
                exit;
            }

            $choques = array_filter($validacion['conflictos'], fn($c) => strpos($c, 'Aviso:') !== 0);
            if ($choques && !$reemplazar) {
                echo json_encode([
                    'status' => 'conflict',
                    'message' => 'Hay asignaciones activas que chocan con esta.',
                    'conflictos' => $validacion['conflictos'],
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $this->asignacionModel->guardar($datos, $reemplazar);

            $avisos = array_values(array_filter($validacion['conflictos'], fn($c) => strpos($c, 'Aviso:') === 0));
            echo json_encode([
                'status' => 'success',
                'message' => $datos['id'] ? 'Asignación actualizada.' : 'Asignación registrada.',
                'avisos' => $avisos,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log('Admin::guardar_asignacion: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Ocurrió un error interno al guardar la asignación.']);
        }
        exit;
    }

    /** Sucursales (antes "Registrar Terminal"): cada terminal es una sucursal con su caja. */
    public function registrar_terminal()
    {
        $data = [
            'title' => 'Sucursales',
            'terminales' => $this->terminalModel->listarSucursales(),
            'es_admin' => $this->esAdministrador(),
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/registrar_terminal', $data);
        $this->view('layouts/footer', $data);
    }

    public function guardar_terminal()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/admin/registrar_terminal');
            exit;
        }
        $volver = function ($msg, $detalle = '') {
            header('Location: ' . URLROOT . '/admin/registrar_terminal?msg=' . $msg . ($detalle !== '' ? '&detalle=' . urlencode($detalle) : ''));
            exit;
        };

        if (!$this->sessionManager->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $volver('error', 'La sesión expiró. Recargue la página.');
        }
        if (!$this->esAdministrador()) {
            $volver('error', 'Solo un Administrador puede crear o modificar sucursales.');
        }

        $d = [
            'id' => (int) ($_POST['id'] ?? 0),
            'nombre_sede' => trim($_POST['nombre_sede'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'numero_oficina' => trim($_POST['numero_oficina'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'prefijo_boleto' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['prefijo_boleto'] ?? '')),
            'pago_qr_titular' => trim($_POST['pago_qr_titular'] ?? ''),
            'pago_qr_entidad' => trim($_POST['pago_qr_entidad'] ?? ''),
        ];

        if ($d['nombre_sede'] === '' || $d['direccion'] === '') {
            $volver('error', 'El nombre y la dirección de la sucursal son obligatorios.');
        }
        if (!preg_match('/^[A-Z0-9]{2,5}$/', $d['prefijo_boleto'])) {
            $volver('error', 'El prefijo de boleto debe tener de 2 a 5 letras o números (p. ej. EAL).');
        }
        if ($this->terminalModel->prefijoEnUso($d['prefijo_boleto'], $d['id'])) {
            $volver('error', 'El prefijo ' . $d['prefijo_boleto'] . ' ya lo usa otra sucursal.');
        }

        if (!empty($_POST['quitar_qr'])) {
            $d['pago_qr_imagen'] = null;
        }
        if (!empty($_FILES['pago_qr_imagen']['name'])) {
            $archivo = $_FILES['pago_qr_imagen'];
            $info = @getimagesize($archivo['tmp_name']);
            $tipos = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];
            if ($archivo['error'] !== UPLOAD_ERR_OK || !$info || !isset($tipos[$info[2]])) {
                $volver('error', 'El QR debe ser una imagen PNG, JPG o WEBP.');
            }
            if ($archivo['size'] > 2 * 1024 * 1024) {
                $volver('error', 'La imagen del QR debe pesar como máximo 2 MB.');
            }
            if (!is_dir('uploads/pagos/')) {
                mkdir('uploads/pagos/', 0755, true);
            }
            $destino = 'uploads/pagos/qr_sucursal_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $tipos[$info[2]];
            if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
                $volver('error', 'No se pudo guardar la imagen del QR.');
            }
            $d['pago_qr_imagen'] = $destino;
        }

        try {
            $this->terminalModel->guardarSucursal($d);
        } catch (Exception $e) {
            error_log('Admin::guardar_terminal: ' . $e->getMessage());
            $volver('error', 'No se pudo guardar la sucursal.');
        }
        $volver($d['id'] ? 'actualizada' : 'creada');
    }

    public function cambiar_estado_terminal($id)
    {
        if ($this->esAdministrador()) {
            $terminal = $this->terminalModel->obtenerTerminal($id);
            if ($terminal) {
                $this->terminalModel->cambiarEstado($id, $terminal->estado ? 0 : 1);
            }
        }
        header('Location: ' . URLROOT . '/admin/registrar_terminal');
    }

    public function eliminar_terminal($id)
    {
        if (!$this->esAdministrador()) {
            header('Location: ' . URLROOT . '/admin/registrar_terminal?msg=error&detalle=' . urlencode('Solo un Administrador puede eliminar sucursales.'));
            exit;
        }
        if ($this->terminalModel->eliminarSucursalSinHistorial($id)) {
            header('Location: ' . URLROOT . '/admin/registrar_terminal?msg=eliminada');
        } else {
            header('Location: ' . URLROOT . '/admin/registrar_terminal?msg=error&detalle=' . urlencode('La sucursal tiene usuarios, viajes, ventas o encomiendas: desactívela en lugar de eliminarla.'));
        }
        exit;
    }

    public function eliminar_asignacion($id)
    {
        $this->asignacionModel->finalizarAsignacion($id);
        header('Location: ' . URLROOT . '/admin/asignar_buses');
    }

    /** Datos de una asignacion para editarla (AJAX). */
    public function obtener_asignacion()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/admin/asignar_buses');
            return;
        }

        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $asignacion = $this->asignacionModel->obtenerAsignacionPorId(trim($_POST['id'] ?? ''));
        if (!$asignacion) {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró la asignación.']);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'id_chofer' => $asignacion->chofer_id,
            'id_bus' => $asignacion->bus_id,
            'id_copiloto' => $asignacion->copiloto_id,
        ]);
        exit;
    }

    /** Compatibilidad: la edicion usa la misma logica y validaciones que el alta. */
    public function editar_asignacion()
    {
        $this->guardar_asignacion();
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

    /** Rutas y tarifas: rutas, paradas intermedias y tarifas por tramo. */
    public function rutas_paradas()
    {
        require_once '../app/models/TramoModel.php';
        $tramos = new TramoModel();

        $db = new Database();
        $db->query("SELECT r.*,
                           (SELECT COUNT(*) FROM rutas_paradas p WHERE p.ruta_id = r.id AND p.estado = 1) AS paradas,
                           (SELECT COUNT(*) FROM viajes v WHERE v.ruta_id = r.id) AS viajes,
                           (SELECT COUNT(*) FROM tarifas_tramo t WHERE t.ruta_id = r.id AND t.precio_sugerido = 1) AS tarifas_sugeridas
                    FROM rutas r
                    ORDER BY r.estado DESC, r.origen, r.destino");
        $rutas = $db->resultSet();

        $rutaId = (int) ($_GET['ruta'] ?? ($rutas[0]->id ?? 0));
        $ruta = null;
        foreach ($rutas as $r) {
            if ((int) $r->id === $rutaId) {
                $ruta = $r;
            }
        }

        $paradasEncomienda = [];
        if ($ruta) {
            $db->query('SELECT id, precio_base_encomienda FROM rutas_paradas WHERE ruta_id = :r AND estado = 1');
            $db->bind(':r', $ruta->id);
            foreach ($db->resultSet() as $p) {
                $paradasEncomienda[(int) $p->id] = (float) $p->precio_base_encomienda;
            }
        }

        $data = [
            'title' => 'Rutas y tarifas',
            'rutas' => $rutas,
            'ruta' => $ruta,
            'puntos' => $ruta ? $tramos->puntos($ruta->id) : [],
            'tarifas' => $ruta ? $tramos->matriz($ruta->id) : [],
            'precio_encomienda' => $paradasEncomienda,
            'es_admin' => $this->esAdministrador(),
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/registrar_rutas_paradas', $data);
        $this->view('layouts/footer', $data);
    }

    /** Guarda las paradas intermedias de una ruta en el orden recibido (AJAX JSON). */
    public function guardar_paradas()
    {
        $entrada = $this->entradaJsonRutas();
        $rutaId = (int) ($entrada['ruta_id'] ?? 0);

        $paradas = [];
        $nombres = [];
        foreach ($entrada['paradas'] ?? [] as $p) {
            $nombre = trim(preg_replace('/\s+/', ' ', (string) ($p['nombre'] ?? '')));
            if ($nombre === '') {
                $this->responderRutas(false, 'Todas las paradas deben tener nombre.');
            }
            $clave = mb_strtolower($nombre);
            if (isset($nombres[$clave])) {
                $this->responderRutas(false, 'La parada "' . $nombre . '" está repetida.');
            }
            $nombres[$clave] = true;
            $paradas[] = [
                'id' => (int) ($p['id'] ?? 0),
                'nombre' => mb_substr($nombre, 0, 100),
                'precio_encomienda' => max(0, round((float) ($p['precio_encomienda'] ?? 0), 2)),
            ];
        }

        require_once '../app/models/TramoModel.php';
        try {
            (new TramoModel())->guardarParadas($rutaId, $paradas);
        } catch (Exception $e) {
            error_log('Admin::guardar_paradas: ' . $e->getMessage());
            $this->responderRutas(false, 'No se pudieron guardar las paradas.');
        }
        $this->responderRutas(true, 'Paradas guardadas. Revise las tarifas de los tramos nuevos.');
    }

    /** Guarda la matriz de tarifas por tramo de una ruta (AJAX JSON). */
    public function guardar_tarifas()
    {
        $entrada = $this->entradaJsonRutas();
        $rutaId = (int) ($entrada['ruta_id'] ?? 0);

        require_once '../app/models/TramoModel.php';
        $tramos = new TramoModel();
        $puntos = $tramos->puntos($rutaId);
        if (!$puntos) {
            $this->responderRutas(false, 'La ruta no existe.');
        }

        // Pares validos: de cada punto a cualquier punto posterior
        $validos = [];
        foreach ($puntos as $i => $desde) {
            if ($desde['tipo'] === 'destino') {
                continue;
            }
            foreach (array_slice($puntos, $i + 1) as $hasta) {
                $validos[$desde['id'] . '-' . ($hasta['tipo'] === 'destino' ? 0 : $hasta['id'])] = true;
            }
        }

        $tarifas = [];
        foreach ($entrada['tarifas'] ?? [] as $t) {
            $clave = (int) ($t['desde'] ?? -1) . '-' . (int) ($t['hasta'] ?? -1);
            $precio = round((float) ($t['precio'] ?? 0), 2);
            if (!isset($validos[$clave])) {
                continue;
            }
            if ($precio <= 0) {
                $this->responderRutas(false, 'Todas las tarifas deben ser mayores a 0.');
            }
            $tarifas[] = ['desde' => (int) $t['desde'], 'hasta' => (int) $t['hasta'], 'precio' => $precio];
        }
        if (count($tarifas) !== count($validos)) {
            $this->responderRutas(false, 'Complete la tarifa de todos los tramos (' . count($validos) . ').');
        }

        try {
            $tramos->guardarTarifas($rutaId, $tarifas);
        } catch (Exception $e) {
            error_log('Admin::guardar_tarifas: ' . $e->getMessage());
            $this->responderRutas(false, 'No se pudieron guardar las tarifas.');
        }
        $this->responderRutas(true, 'Tarifas guardadas.');
    }

    private function entradaJsonRutas()
    {
        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderRutas(false, 'Método no permitido.');
        }
        $entrada = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!$this->esAdministrador()) {
            $this->responderRutas(false, 'Solo un Administrador puede modificar rutas y tarifas.');
        }
        if (!$this->sessionManager->verifyCsrfToken($entrada['csrf_token'] ?? '')) {
            $this->responderRutas(false, 'La sesión expiró. Recargue la página.');
        }
        return $entrada;
    }

    private function responderRutas($ok, $mensaje)
    {
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function guardar_ruta()
    {
        if (!$this->esAdministrador()) {
            header('Location: ' . URLROOT . '/admin/rutas_paradas');
            exit;
        }
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
            if (ob_get_level() > 0) ob_clean();

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
        if (ob_get_level() > 0) ob_clean();
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
        if (ob_get_level() > 0) ob_clean();
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
