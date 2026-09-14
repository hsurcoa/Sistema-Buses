<?php

/**
 * Controlador: Ventas
 * Descripción: Maneja las operaciones de venta de pasajes y creación de rutas
 */

class Ventas extends Controller
{
    private $rutaModel;
    private $tipoBusModel;
    private $terminalModel;
    private $sessionManager;

    public function __construct()
    {
        // Inicializar SessionManager
        require_once '../app/core/SessionManager.php';
        $this->sessionManager = SessionManager::getInstance();

        $this->rutaModel = $this->model('RutaModel');
        $this->tipoBusModel = $this->model('TipoBusModel');
        $this->terminalModel = $this->model('TerminalModel');
    }

    /**
     * Vista principal de ventas
     */
    public function index()
    {
        $this->crear_ruta();
    }

    /**
     * Vista de venta de pasajes (nueva interfaz con tabs)
     */
    public function venta_pasajes()
    {
        // ---------------------------------------------------------
        // FORCE NO-CACHE: Fix for browser not showing latest changes
        // ---------------------------------------------------------
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: 0");

        // Obtener viajes programados
        $viajesProgramados = $this->rutaModel->listarViajesProgramados(50, 0);

        $config = $this->model('ConfiguracionModel')->obtenerConfiguracion();

        $data = [
            'title' => 'Venta de Pasajes',
            'viajesProgramados' => $viajesProgramados,
            // Cobro con QR (solo si un administrador lo activo y cargo la imagen)
            'pago_qr' => (!empty($config['pago_qr_activo']) && !empty($config['pago_qr_imagen'])) ? [
                'imagen' => URLROOT . '/' . $config['pago_qr_imagen'],
                'titular' => $config['pago_qr_titular'] ?? '',
                'entidad' => $config['pago_qr_entidad'] ?? '',
                'instrucciones' => $config['pago_qr_instrucciones'] ?? '',
                'minutos' => max(3, (int) ($config['pago_qr_minutos'] ?? 15)),
            ] : null,
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('ventas/venta_pasajes', $data);
        $this->view('layouts/footer', $data);
    }

    /**
     * Vista para crear rutas de viaje
     */
    public function crear_ruta()
    {
        // Obtener datos necesarios
        $rutas = $this->rutaModel->listarRutas(100, 0);
        // Incluye tipos inactivos que algun bus real sigue usando
        $tiposBuses = $this->tipoBusModel->listarTiposParaFlota();
        $terminales = $this->terminalModel->listarTerminales();
        $viajesProgramados = $this->rutaModel->listarViajesProgramados(50, 0);

        // Cargar lista de choferes para el dropdown
        $choferes = $this->rutaModel->listarChoferes();

        $data = [
            'title' => 'Crear Rutas de Viaje',
            'rutas' => $rutas,
            'tiposBuses' => $tiposBuses,
            'terminales' => $terminales,
            'terminales' => $terminales,
            'viajesProgramados' => $viajesProgramados,
            'choferes' => $choferes // Pasar choferes a la vista
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('ventas/crear_ruta', $data);
        $this->view('layouts/footer', $data);
    }

    /**
     * Guardar nueva ruta de viaje
     */
    public function guardar_ruta_viaje()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // SEGURIDAD: CSRF
            $token = $_POST['csrf_token'] ?? '';
            if (!$this->sessionManager->verifyCsrfToken($token)) {
                die('<script>alert("Error de Seguridad: Token inválido. Recargue la página."); window.history.back();</script>');
            }

            // Capturar todos los datos del formulario
            $data = [
                'id' => $_POST['id'] ?? '',
                'ruta_id' => $_POST['ruta_id'] ?? '',
                'tipo_bus_id' => $_POST['tipo_bus_id'] ?? '',
                'terminal_origen_id' => $_POST['terminal_origen_id'] ?? '',
                'terminal_destino_id' => $_POST['terminal_destino_id'] ?? '',
                'bus_id' => $_POST['bus_id'] ?? '', // ✅ CAMPO NUEVO
                'chofer_id' => $_POST['chofer_id'] ?? '', // ✅ CAMPO NUEVO
                'fecha_salida' => $_POST['fecha_salida'] ?? '',
                'hora_salida' => $_POST['hora_salida'] ?? '',
                'hora_llegada' => $_POST['hora_llegada'] ?? '',
                'precio_base' => floatval($_POST['precio_base'] ?? 0),
                'tipo_servicio' => $_POST['tipo_servicio'] ?? 'Ejecutivo',
                'servicios' => $_POST['servicios'] ?? [],
                'notas' => $_POST['notas'] ?? '',
                'estado' => $_POST['estado'] ?? 'Programado'
            ];

            // Helper para renderizar respuesta HTML completa
            $renderResponse = function ($icon, $title, $text) {
                echo '<!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Procesando...</title>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                    <style>
                        body { 
                            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                            background: #f4f6f9; 
                            display: flex; 
                            justify-content: center; 
                            align-items: center; 
                            height: 100vh; 
                            margin: 0; 
                        }
                    </style>
                </head>
                <body>
                    <script>
                        Swal.fire({
                            icon: ' . json_encode($icon) . ',
                            title: ' . json_encode($title) . ',
                            text: ' . json_encode($text) . ',
                            confirmButtonColor: "#667eea",
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = "' . URLROOT . '/ventas/crear_ruta";
                        });
                    </script>
                </body>
                </html>';
            };

            // Validar campos obligatorios
            if (empty($data['ruta_id']) || empty($data['tipo_bus_id']) || empty($data['fecha_salida'])) {
                $renderResponse('error', 'Error de Validación', 'Complete todos los campos obligatorios');
                return;
            }

            try {
                // Guardar en base de datos
                $resultado = $this->rutaModel->guardarRutaViaje($data);

                if ($resultado === true) {
                    $renderResponse('success', '¡Éxito!', 'Ruta de viaje guardada exitosamente');
                } else {
                    $renderResponse('error', 'Error', $resultado);
                }
            } catch (Exception $e) {
                $renderResponse('error', 'Error del Sistema', $e->getMessage());
            }
        } else {
            header('Location: ' . URLROOT . '/ventas/crear_ruta');
        }
    }

    /**
     * Obtener datos de un viaje programado para edición y venta (AJAX)
     * Retorna configuración detallada del bus para el mapa de asientos
     */
    public function obtener_ruta_viaje($id)
    {
        // ---------------------------------------------------------
        // FORCE NO-CACHE: Prevent stale seat maps
        // ---------------------------------------------------------
        // Clear any previous output
        if (ob_get_length()) ob_clean();

        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Type: application/json');

        // Verificar si es una petición AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            // Permitir acceso directo para debugging pero lo ideal es bloquear
        }


        $viaje = $this->rutaModel->obtenerViajePorId($id);

        if ($viaje) {
            // 1. Procesar datos básicos del viaje
            if (is_string($viaje->servicios_incluidos)) {
                $viaje->servicios_incluidos = json_decode($viaje->servicios_incluidos, true);
            }
            $viaje->fecha_salida_date = date('Y-m-d', strtotime($viaje->fecha_salida));

            // 2. Obtener Info Avanzada del Tipo de Bus
            $tipoBus = $this->tipoBusModel->obtenerTipoBus($viaje->tipo_bus_id);

            // ✅ CORRECCIÓN CRÍTICA: Incluir información del tipo de bus en la respuesta
            if ($tipoBus) {
                // Decodificar configuración de asientos (JSON)
                if (!empty($tipoBus->configuracion_asientos)) {
                    $viaje->layout_config = json_decode($tipoBus->configuracion_asientos, true);
                } else {
                    // Configuración por defecto si no existe
                    $viaje->layout_config = [
                        'pisos' => $tipoBus->pisos ?? 1,
                        'columnas' => 4,
                        'posicion_pasillo' => 2,
                        'asientos_total' => $tipoBus->capacidad ?? 40
                    ];
                }

                // Asignar datos adicionales del tipo de bus
                $viaje->asientos_total = $tipoBus->capacidad ?? 40;
                $viaje->pisos = $tipoBus->pisos ?? 1;
                $viaje->nombre_tipo_bus = $tipoBus->nombre ?? 'Bus Estándar';
            } else {
                // Valores por defecto si no se encuentra el tipo de bus
                $viaje->layout_config = [
                    'pisos' => 1,
                    'columnas' => 4,
                    'posicion_pasillo' => 2,
                    'asientos_total' => 40
                ];
                $viaje->asientos_total = 40;
                $viaje->pisos = 1;
                $viaje->nombre_tipo_bus = 'Bus Estándar';
            }

            // 3. Obtener Asientos Ocupados Real (Desde DB)
            // Se consulta por viaje_id para evitar conflictos con otros viajes del mismo bus
            $viaje->asientos_ocupados = $this->rutaModel->obtenerAsientosOcupados($viaje->id);

            // 4. Obtener Lista de Rutas Activas para el Select
            $viaje->lista_rutas = $this->rutaModel->listarRutasActivas();

            // 5. Obtener Paradas Intermedias (Nuevo para Venta Dinámica)
            $viaje->paradas = $this->rutaModel->obtenerParadasPorRuta($viaje->ruta_id);

            // ✅ CORRECCIÓN: Retornar en el formato correcto que espera el frontend
            echo json_encode([
                'success' => true,
                'data' => $viaje
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'msg' => 'Viaje no encontrado']);
        }
    }

    /**
     * AJAX: Obtener buses por tipo
     * ✅ VERSIÓN MEJORADA con logging y debugging
     */
    public function obtener_buses_tipo()
    {
        // Limpiar cualquier output previo (errores, warnings, etc.) para garantizar JSON puro
        if (ob_get_length()) ob_clean();

        // Obtener ID del POST o JSON
        $tipoId = $_POST['tipo_id'] ?? null;
        if (!$tipoId) {
            $input = json_decode(file_get_contents('php://input'), true);
            $tipoId = $input['tipo_id'] ?? null;
        }

        header('Content-Type: application/json');

        if ($tipoId) {
            // ✅ LOGGING: Registrar la petición
            error_log("🔍 Buscando buses para tipo_bus_id: $tipoId");

            $buses = $this->rutaModel->obtenerBusesPorTipo($tipoId);

            // ✅ LOGGING: Registrar resultado
            $cantidad = count($buses);
            error_log("📊 Buses encontrados: $cantidad");

            if ($cantidad === 0) {
                // ✅ DEBUGGING: Información adicional cuando no hay buses
                error_log("⚠️ WARNING: No se encontraron buses activos para tipo_bus_id: $tipoId");
                error_log("💡 SUGERENCIA: Verificar que existan buses con tipo_bus_id=$tipoId y estado=1 en la tabla 'buses'");
            }

            echo json_encode([
                'success' => true,
                'buses' => $buses,
                'count' => $cantidad,
                'tipo_bus_id' => $tipoId,
                'debug_info' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => $cantidad === 0 ? 'No hay buses activos de este tipo' : "Se encontraron $cantidad bus(es)"
                ]
            ]);
        } else {
            error_log("❌ ERROR: No se recibió tipo_bus_id en la petición");
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Falta el ID del tipo de bus',
                'received_data' => $_POST
            ]);
        }
    }

    /**
     * AJAX: Obtener tripulación asignada a un bus
     */
    public function obtener_tripulacion_bus()
    {
        // Limpiar cualquier output previo
        if (ob_get_length()) ob_clean();

        // Obtener ID del POST o JSON
        $busId = $_POST['bus_id'] ?? null;
        if (!$busId) {
            $input = json_decode(file_get_contents('php://input'), true);
            $busId = $input['bus_id'] ?? null;
        }

        if ($busId) {
            $tripulacion = $this->rutaModel->obtenerTripulacionBus($busId);
            header('Content-Type: application/json');

            if ($tripulacion) {
                echo json_encode(['success' => true, 'data' => $tripulacion]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No hay tripulación asignada para este bus']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Falta el ID del bus']);
        }
    }


    /**
     * Despachar Bus: Finaliza viaje actual y crea uno nuevo (preserva historial)
     */
    public function despachar_ruta($id)
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            // SEGURIDAD: CSRF (JSON)
            $token = $_POST['csrf_token'] ?? '';
            if (!$this->sessionManager->verifyCsrfToken($token)) {
                echo json_encode(['success' => false, 'message' => 'Error de Seguridad: Token inválido.']);
                exit;
            }

            $nuevoViajeId = $this->rutaModel->despacharYCrearNuevo($id);

            if ($nuevoViajeId) {
                echo json_encode([
                    'success' => true,
                    'nuevo_viaje_id' => $nuevoViajeId,
                    'redirect' => URLROOT . '/ventas/venta_pasajes?viaje_id=' . $nuevoViajeId,
                    'message' => 'Bus despachado exitosamente. Nuevo viaje creado.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al crear nuevo viaje. Revise los logs del servidor.'
                ]);
            }
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        }
    }

    /**
     * Eliminar viaje programado (AJAX)
     */
    public function eliminar_ruta_viaje($id)
    {
        // Verificar si es una petición POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            // SEGURIDAD: CSRF (JSON)
            $token = $_POST['csrf_token'] ?? '';
            if (!$this->sessionManager->verifyCsrfToken($token)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error de Seguridad: Token inválido.']);
                exit;
            }

            try {
                $resultado = $this->rutaModel->eliminarViaje($id);
                if ($resultado === true) {
                    echo json_encode(['success' => true]);
                } else {
                    // Pasar el mensaje de error específico del modelo
                    $msg = is_string($resultado) ? $resultado : 'No se pudo eliminar el viaje por reglas de negocio.';
                    echo json_encode(['success' => false, 'message' => $msg]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Método no permitido']);
        }
    }


    public function procesar_venta()
    {
        // Headers para JSON estricto
        header('Content-Type: application/json');

        // ✅ SOLUCIÓN: Usar SessionManager para validar sesión
        $session = SessionManager::getInstance();
        $session->requireAuthAjax(); // Retorna JSON y sale si no está autenticado

        $usuario_id = $session->getUserId();

        // Obtener datos (Soporta JSON y POST form-data)
        $input = json_decode(file_get_contents('php://input'), true);
        $datos = $input ?? $_POST;

        // SEGURIDAD: CSRF (JSON/POST)
        $token = $datos['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$session->verifyCsrfToken($token)) {
            echo json_encode(['status' => 'error', 'msg' => 'Error de Seguridad: Token CSRF inválido.']);
            return;
        }

        // Validaciones básicas
        if (empty($datos['viaje_id']) || empty($datos['numero_asiento'])) {
            echo json_encode(['status' => 'error', 'msg' => 'Faltan datos (Viaje o Asiento).']);
            return;
        }

        // Determinar estado basado en 'accion' o 'tipo_venta'
        $estado = 'reservado';
        if (isset($datos['accion'])) {
            $estado = ($datos['accion'] === 'venta') ? 'vendido' : 'reservado';
        } elseif (isset($datos['tipo_venta'])) {
            $estado = ($datos['tipo_venta'] == 1) ? 'vendido' : 'reservado';
        }

        // Preparar array para el modelo
        $ventaData = [
            'viaje_id' => $datos['viaje_id'],
            'asiento' => $datos['numero_asiento'],
            'nombres' => $datos['nombre_pasajero'] ?? 'Anónimo',
            'apellidos' => '',
            'documento' => $datos['documento_pasajero'] ?? '0000000',
            'celular' => $datos['celular'] ?? '',
            'precio' => $datos['precio'] ?? 0,
            'estado' => $estado,
            'usuario_id' => $usuario_id,
            'parada_id' => $datos['parada_id'] ?? null // ✅ Nuevo campo para destino intermedio
        ];

        // Separar nombres y apellidos si viene todo en 'nombres'
        if (empty($ventaData['apellidos']) && strpos($ventaData['nombres'], ' ') !== false) {
            $parts = explode(' ', $ventaData['nombres'], 2);
            $ventaData['nombres'] = $parts[0];
            $ventaData['apellidos'] = $parts[1];
        }

        try {
            // Usar la lógica transaccional robusta del modelo
            $ticket = $this->rutaModel->registrarVentaTransaccion($ventaData);

            if ($ticket) {
                echo json_encode([
                    'status' => 'success',
                    'msg' => 'Operación exitosa',
                    'ticket' => $ticket
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'msg' => 'No se pudo guardar la venta (Asiento ocupado o error DB).'
                ]);
            }
        } catch (Exception $e) {
            // SEGURIDAD: Loggear error interno y no mostrarlo al usuario
            error_log("Ventas::procesar_venta Error: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'msg' => 'Ocurrió un error interno al procesar la venta. Intente nuevamente.'
            ]);
        }
    }

    /**
     * Obtener datos crudos de una reserva para editar (AJAX)
     */

    public function obtener_datos_reserva($id)
    {
        header('Content-Type: application/json');
        $datos = $this->rutaModel->obtenerTicketPorId($id);
        if ($datos) {
            echo json_encode(['success' => true, 'data' => $datos]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No encontrado']);
        }
    }

    /**
     * Obtener Manifiesto Detallado (JSON para Modal Reporte)
     */
    public function obtener_manifiesto($viajeId)
    {
        // Headers Anti-Cache
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Content-Type: application/json');

        try {
            // 1. Obtener datos de cabecera del viaje
            $viaje = $this->rutaModel->obtenerViajePorId($viajeId);

            if (!$viaje) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Viaje no encontrado'
                ]);
                return;
            }

            // 2. Obtener lista de pasajeros (usa la misma query que listar_manifiesto)
            $pasajeros = $this->rutaModel->obtenerPasajerosPorViaje($viajeId);

            // 3. Estructurar cabecera con datos seguros
            $cabecera = [
                'ruta' => ($viaje->origen ?? 'N/A') . ' -> ' . ($viaje->destino ?? 'N/A'),
                'origen' => $viaje->origen ?? 'N/A',
                'destino' => $viaje->destino ?? 'N/A',
                'fecha_salida' => date('d/m/Y', strtotime($viaje->fecha_salida ?? 'now')),
                'hora_salida' => substr($viaje->hora_salida ?? '00:00', 0, 5),
                'placa_bus' => $viaje->bus_placa ?? 'Sin asignar',
                'numero_unidad' => $viaje->bus_numero ?? 'N/A',
                'conductor' => $viaje->chofer_nombre ?? 'No asignado',
                'tipo_bus' => $viaje->tipo_bus ?? 'Estándar',
                'capacidad_total' => $viaje->capacidad ?? 40
            ];

            // 4. Formatear lista de pasajeros y calcular totales
            $pasajerosFormateados = [];
            $totalRecaudado = 0;
            $totalPasajeros = 0;

            foreach ($pasajeros as $p) {
                $pasajerosFormateados[] = [
                    'id' => $p->id,
                    'nro_asiento' => $p->numero_asiento,
                    'nombre_pasajero' => $p->nombre_pasajero,
                    'nro_documento' => $p->numero_documento ?? 'S/N',
                    'telefono_contacto' => $p->telefono ?? '-',
                    'estado_boleto' => $p->estado, // 'vendido' o 'reservado'
                    'destino_especifico' => $p->destino ?? $viaje->destino,
                    'precio' => number_format((float)$p->precio, 2, '.', ''),
                    'fecha_venta' => date('d/m/Y H:i', strtotime($p->fecha_venta ?? 'now'))
                ];

                if ($p->estado === 'vendido') {
                    $totalRecaudado += (float)$p->precio;
                }
                $totalPasajeros++;
            }

            // 5. Respuesta JSON estructurada
            echo json_encode([
                'success' => true,
                'cabecera' => $cabecera,
                'pasajeros' => $pasajerosFormateados,
                'resumen' => [
                    'total_pasajeros' => $totalPasajeros,
                    'total_recaudado' => number_format($totalRecaudado, 2, '.', ''),
                    'asientos_disponibles' => ($cabecera['capacidad_total'] - $totalPasajeros)
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error del servidor: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Listar manifiesto de pasajeros (AJAX)
     * ✅ SOLUCIÓN DEFINITIVA: Backend robusto con validación completa y manejo de errores
     */
    public function listar_manifiesto($viajeId)
    {
        header('Content-Type: application/json');

        try {
            // 1. Validación estricta de entrada
            if (empty($viajeId) || !is_numeric($viajeId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'ID de viaje inválido',
                    'pasajeros' => [],
                    'debug' => ['viaje_id_recibido' => $viajeId]
                ]);
                return;
            }

            // 2. Verificar que el viaje existe
            $viajeExiste = $this->rutaModel->obtenerViajePorId($viajeId);
            if (!$viajeExiste) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'El viaje no existe en la base de datos',
                    'pasajeros' => [],
                    'debug' => ['viaje_id' => $viajeId]
                ]);
                return;
            }

            // 3. Obtener pasajeros
            $pasajeros = $this->rutaModel->obtenerPasajerosPorViaje($viajeId);

            // 4. Respuesta exitosa
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'pasajeros' => $pasajeros,
                'total' => count($pasajeros),
                'viaje_id' => $viajeId,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            // Error de base de datos
            error_log("ERROR SQL en listar_manifiesto (viaje_id: $viajeId): " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error de base de datos',
                'pasajeros' => [],
                'debug' => [
                    'sql_error' => $e->getMessage(),
                    'viaje_id' => $viajeId
                ]
            ]);
        } catch (Exception $e) {
            // Error general
            error_log("ERROR GENERAL en listar_manifiesto (viaje_id: $viajeId): " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor',
                'pasajeros' => [],
                'debug' => [
                    'message' => $e->getMessage(),
                    'viaje_id' => $viajeId
                ]
            ]);
        }
    }

    /**
     * Cancelar boleto / Liberar reserva (AJAX)
     */
    public function cancelar_boleto($id)
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            // SEGURIDAD: CSRF
            $token = $_POST['csrf_token'] ?? '';
            if (!$this->sessionManager->verifyCsrfToken($token)) {
                echo json_encode(['success' => false, 'message' => 'Error de Seguridad: Token inválido.']);
                exit;
            }
            if ($this->rutaModel->cancelarBoleto($id)) {
                echo json_encode([
                    'status' => 'success',
                    'success' => true,
                    'mensaje' => 'Reserva eliminada'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'mensaje' => 'Error al cancelar'
                ]);
            }
        }
    }

    /**
     * Generar Reporte PDF del Manifiesto
     */
    public function imprimir_manifiesto($viajeId)
    {
        // 1. Obtener Datos
        $viaje = $this->rutaModel->obtenerViajePorId($viajeId);
        $pasajeros = $this->rutaModel->obtenerPasajerosPorViaje($viajeId);

        if (!$viaje) {
            die("Viaje no encontrado");
        }

        // 2. Cargar FPDF
        // Intentar varias rutas comunes
        $fpdfPath = APPROOT . '/libraries/fpdf/fpdf.php';
        if (!file_exists($fpdfPath)) {
            // Ruta alternativa indicada por el usuario (/libs desde root, simulado)
            $fpdfPath = APPROOT . '/../libs/fpdf.php';
            if (!file_exists($fpdfPath)) {
                $fpdfPath = APPROOT . '/libraries/fpdf.php';
            }
        }

        if (file_exists($fpdfPath)) {
            require_once $fpdfPath;
        } else {
            // Fallback: Clase Dummy para no romper si no está la lib (o morir con mensaje claro)
            die("Error: No se encuentra la librería FPDF. Buscada en: " . APPROOT . '/libraries/fpdf/fpdf.php');
        }

        // 3. Configuración del PDF
        $pdf = new FPDF('L', 'mm', 'Letter');
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        // 4. Encabezado
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'MANIFIESTO DE PASAJEROS', 0, 1, 'C');
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 10);
        $fecha = date('d/m/Y', strtotime($viaje->fecha_salida));
        $hora = substr($viaje->hora_salida, 0, 5);
        $rutaStr = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $viaje->origen . ' - ' . $viaje->destino);

        // Línea 2: Ruta | Fecha | Hora
        // Formato: "Origen: X - Destino: Y | Fecha: F | Hora: H"
        $linea2 = "Ruta: $rutaStr   |   Fecha: $fecha   |   Hora: $hora";
        $pdf->Cell(0, 7, $linea2, 0, 1, 'C');

        $pdf->Ln(2);

        // Línea 3: Datos del Vehículo y Conductor
        // "UNIDAD / BUS N°: [Nro]" ... "PLACA: [PLACA]" ... "CONDUCTOR RESPONSABLE: [NOMBRE CHOFER]"

        $busNumero = $viaje->bus_numero ?? '---';
        $placa = $viaje->bus_placa ?? '---';
        $conductor = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $viaje->chofer_nombre ?? 'No Asignado');

        $pdf->SetFont('Arial', 'B', 10);
        // Distribuir en una sola línea usando celdas fijas
        $pdf->Cell(45, 7, iconv('UTF-8', 'ISO-8859-1', "UNIDAD N°: $busNumero"), 1, 0, 'L');
        $pdf->Cell(45, 7, "PLACA: $placa", 1, 0, 'L');
        $pdf->Cell(0, 7, "CONDUCTOR: $conductor", 1, 1, 'L');

        $pdf->Ln(5);

        // 5. Tabla
        // Anchos: 10, 25, 80, 25, 15, 30, 20 = 205 (Letter Landscape width ~279 - 30 margins = 249 available. OK)
        $w = array(10, 25, 80, 25, 15, 30, 20);
        $header = array('N', 'Estado', 'Nombre Completo', 'CI / DNI', 'As.', 'Destino', 'Monto');

        // Header Tabla
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('Arial', 'B', 9);
        for ($i = 0; $i < count($header); $i++)
            $pdf->Cell($w[$i], 8, iconv('UTF-8', 'ISO-8859-1', $header[$i]), 1, 0, 'C', true);
        $pdf->Ln();

        // Data
        $pdf->SetFont('Arial', '', 9);
        $totalPasajeros = 0;
        $totalRecaudado = 0;

        foreach ($pasajeros as $index => $row) {
            $estado = ucfirst($row->estado);
            $nombre = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row->nombre_pasajero);
            $dni = $row->numero_documento;
            $asiento = $row->numero_asiento;
            $destino = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row->destino);

            // Solo contar montos de 'vendido'. 'reservado' es 0 visualmente o pendiente?
            // Generalmente en manifiesto se muestra el precio pactado igual.
            $monto = ($row->estado == 'vendido') ? $row->precio : 0.00;

            // Si es reservado, mostrar monto pero no sumar al recaudado "Pagado"?
            // El usuario pidió "Total Recaudado". Asumiré solo PAGADOS (vendidos).
            // Pero en la tabla mostraré el precio del boleto.

            $estiloFila = ($row->estado == 'reservado') ? [255, 252, 235] : [255, 255, 255]; // Amarillo muy claro si reservado?
            // FPDF no soporta row style facil, dejamos blanco.

            $pdf->Cell($w[0], 7, $index + 1, 1, 0, 'C');
            $pdf->Cell($w[1], 7, $estado, 1, 0, 'C');
            $pdf->Cell($w[2], 7, $nombre, 1, 0, 'L');
            $pdf->Cell($w[3], 7, $dni, 1, 0, 'C');
            $pdf->Cell($w[4], 7, $asiento, 1, 0, 'C');
            $pdf->Cell($w[5], 7, $destino, 1, 0, 'L');
            $pdf->Cell($w[6], 7, number_format($row->precio, 2), 1, 0, 'R');
            $pdf->Ln();

            $totalPasajeros++;
            if ($row->estado == 'vendido') {
                $totalRecaudado += $row->precio;
            }
        }

        // Resumen
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(array_sum($w) - 50, 7, 'Total Pasajeros: ' . $totalPasajeros . '      Total Recaudado (Pagado): Bs. ' . number_format($totalRecaudado, 2), 0, 0, 'R');
        $pdf->Ln(20);

        // Firmas
        $pdf->Cell(10, 5, '', 0, 0);
        $pdf->Cell(80, 5, '................................................', 0, 0, 'C');
        $pdf->Cell(60, 5, '', 0, 0);
        $pdf->Cell(80, 5, '................................................', 0, 1, 'C');

        $pdf->Cell(10, 5, '', 0, 0);
        $pdf->Cell(80, 5, 'Firma Conductor', 0, 0, 'C');
        $pdf->Cell(60, 5, '', 0, 0);
        $pdf->Cell(80, 5, 'Firma Despacho', 0, 1, 'C');

        $pdf->Output('I', 'Manifiesto_Viaje_' . $viajeId . '.pdf');
    }

    /**
     * Imprimir Manifiesto en Formato HTML (Horizontal)
     */
    public function imprimir_manifiesto_html($viajeId)
    {
        // 1. Obtener Datos
        $viaje = $this->rutaModel->obtenerViajePorId($viajeId);
        $pasajeros = $this->rutaModel->obtenerPasajerosPorViaje($viajeId);

        if (!$viaje) {
            die("Viaje no encontrado");
        }

        $data = [
            'viaje' => $viaje,
            'pasajeros' => $pasajeros
        ];

        // 2. Cargar Vista
        $this->view('ventas/imprimir_manifiesto', $data);
    }

    /**
     * Gestión de Boleto (Editar, Confirmar/Vender, Eliminar)
     * Responde a modalGestionBoleto
     */
    /** Estado de un cobro (para la pantalla del pasajero y el modal del vendedor). */
    public function estado_cobro($id)
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $this->rutaModel->liberarCobrosQrVencidos();
        $cobro = $this->rutaModel->estadoCobroBoleto($id);
        echo json_encode($cobro ? ['success' => true, 'data' => $cobro] : ['success' => false], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Pantalla para el pasajero (segundo monitor): QR grande, monto y estado del
     * pago. Se actualiza sola cuando el vendedor confirma o cancela el cobro.
     */
    public function pantalla_qr($id)
    {
        $config = $this->model('ConfiguracionModel')->obtenerConfiguracion();
        $cobro = $this->rutaModel->estadoCobroBoleto($id);

        $this->view('ventas/pantalla_qr', [
            'cobro' => $cobro,
            'config' => $config,
        ]);
    }

    public function gestion_boleto()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
            return;
        }

        $id = $_POST['id'] ?? null;
        $accion = $_POST['accion'] ?? null;
        $nombres = $_POST['nombres'] ?? '';
        $apellidos = $_POST['apellidos'] ?? '';
        $documento = $_POST['documento'] ?? '';
        $precio = $_POST['precio'] ?? 0;

        if (!$id || !$accion) {
            echo json_encode(['success' => false, 'mensaje' => 'Faltan datos']);
            return;
        }

        try {
            switch ($accion) {
                case 'eliminar':
                    if ($this->rutaModel->cancelarBoleto($id)) {
                        echo json_encode(['success' => true, 'mensaje' => 'Boleto eliminado correctamente']);
                    } else {
                        throw new Exception("Error al eliminar");
                    }
                    break;

                case 'guardar_nuevo':
                    // ✅ Usar SessionManager
                    $session = SessionManager::getInstance();
                    $datosNuevo = [
                        'viaje_id' => $_POST['viaje_id'],
                        'asiento' => $_POST['asiento'], // Debe venir del modal
                        'documento' => $documento,
                        'nombres' => $nombres,
                        'apellidos' => $apellidos,
                        'celular' => $_POST['celular'] ?? '', // Nuevo campo
                        'precio' => $precio,
                        'estado' => $_POST['estado_nuevo'] ?? 'reservado', // Vendido o reservado
                        'usuario_id' => $session->getUserId() ?? 1 // Fallback 1
                    ];

                    $ticket = $this->rutaModel->registrarVentaTransaccion($datosNuevo);
                    if ($ticket) {
                        $msg = ($datosNuevo['estado'] == 'vendido') ? 'Venta registrada' : 'Reserva registrada';
                        echo json_encode(['success' => true, 'mensaje' => $msg, 'ticket' => $ticket]);
                    } else {
                        throw new Exception("No se pudo registrar el boleto");
                    }
                    break;

                case 'confirmar_venta':
                case 'actualizar_datos':
                    // 1. Actualizar Datos del Cliente y Precio
                    // Necesitamos una funcion en modelo que actualice cliente asociado y precio en boleto
                    // Reutilizamos o creamos logica ad-hoc

                    // Actualizar Cliente/Boleto Logic
                    $res = $this->rutaModel->actualizarDatosBoleto($id, $nombres, $apellidos, $documento, $precio);

                    if ($accion == 'confirmar_venta') {
                        // Cobro en efectivo registrado en la caja abierta (antes solo cambiaba el estado)
                        $ticketData = $this->rutaModel->confirmarPagoBoleto($id, SessionManager::getInstance()->getUserId(), 'EFECTIVO');
                        $msg = 'Venta confirmada exitosamente';
                        echo json_encode(['success' => true, 'mensaje' => $msg, 'ticket' => $ticketData]);
                    } else {
                        echo json_encode(['success' => true, 'mensaje' => 'Datos actualizados correctamente']);
                    }
                    break;

                default:
                    echo json_encode(['success' => false, 'mensaje' => 'Acción no válida']);
                    break;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Obtener contadores de asientos para dashboard
     */
    public function obtener_conteo_asientos($id)
    {
        header('Content-Type: application/json');
        // No cache
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

        try {
            $conteos = $this->rutaModel->obtenerConteoAsientos($id);
            echo json_encode(['success' => true, 'data' => $conteos]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'data' => ['libres' => 0, 'reservados' => 0, 'vendidos' => 0]]);
        }
    }
}
