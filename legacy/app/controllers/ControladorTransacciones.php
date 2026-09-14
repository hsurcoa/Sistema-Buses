<?php
// FILE: app/Controllers/ControladorTransacciones.php
require_once '../app/core/SessionManager.php';

class ControladorTransacciones extends Controller
{
    private $rutaModel;

    public function __construct()
    {
        $this->rutaModel = $this->model('RutaModel');
        $this->validarSesion();
    }

    public function index()
    {
        // 1. Silenciar errores HTML para que no rompan el JSON
        ini_set('display_errors', 0);
        error_reporting(E_ALL); // Loguear todo internamente, pero no mostrar

        // 2. Limpiar Buffers Agresivamente
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 3. Headers JSON Estrictos
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=utf-8');

        // 4. Capturar errores fatales que escapan al try/catch normal
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR)) {
                // Limpiar cualquier salida parcial
                while (ob_get_level() > 0) ob_end_clean();
                echo json_encode(['status' => 'error', 'msg' => 'Error Fatal PHP: ' . $error['message']]);
            }
        });

        // 5. Iniciar Nuevo Buffer limpio
        ob_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'msg' => 'Método no permitido']);
            ob_end_flush();
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $datos = $input ?? $_POST;

            // ✅ CSRF Protection
            $token = $datos['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!SessionManager::getInstance()->verifyCsrfToken($token)) {
                throw new Exception('Error de seguridad: Token CSRF inválido o expirado');
            }

            $accion = $datos['accion'] ?? '';

            switch ($accion) {
                case 'nueva_transaccion':
                    $this->procesarNuevaTransaccion($datos);
                    break;

                case 'gestionar_reserva':
                    $this->procesarGestionReserva($datos);
                    break;

                case 'confirmar_pago':
                    $this->procesarConfirmacionPago($datos);
                    break;

                case 'cancelar_qr':
                    $this->procesarCancelacionQr($datos);
                    break;

                default:
                    echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
                    break;
            }
        } catch (Throwable $e) {
            // Catch global para el switch
            while (ob_get_level() > 0) ob_end_clean();
            echo json_encode(['status' => 'error', 'msg' => 'Excepción General: ' . $e->getMessage()]);
        }

        // Final flush is handled inside methods, but double check in methods
    }

    private function procesarNuevaTransaccion($datos)
    {
        // Validacion
        if (empty($datos['viaje_id']) || empty($datos['asiento']) || empty($datos['documento'])) {
            echo json_encode(['status' => 'error', 'msg' => 'Faltan datos obligatorios']);
            return;
        }

        $estado = ($datos['tipo'] === 'venta') ? 'vendido' : 'reservado';

        // Cobro con QR: el asiento se reserva unos minutos mientras el pasajero paga
        $esQr = ($datos['tipo'] === 'qr');
        $minutosQr = null;
        if ($esQr) {
            $caja = $this->model('CajaModel')->verificarCajaAbierta(SessionManager::getInstance()->getUserId());
            $qr = $this->model('ConfiguracionModel')->obtenerPagoQr($caja->sucursal_id ?? Sucursal::delUsuario());
            if (!$qr) {
                echo json_encode(['status' => 'error', 'msg' => 'El cobro con QR no está configurado. Un administrador debe cargar el QR en Configuración.']);
                return;
            }
            $minutosQr = $qr['minutos'];
        }

        // Preparar Datos Modelo
        $ventaData = [
            'viaje_id' => $datos['viaje_id'],
            'asiento' => $datos['asiento'],
            'nombres' => $datos['nombres'] ?? 'Anónimo',
            'apellidos' => $datos['apellidos'] ?? '',
            'documento' => $datos['documento'],
            'celular' => $datos['celular'] ?? '',
            'precio' => $datos['precio'] ?? 0,
            'estado' => $estado,
            'usuario_id' => SessionManager::getInstance()->getUserId(),
            'parada_id' => $datos['parada_id'] ?? null, // donde baja (vacio = destino final)
            'parada_subida_id' => $datos['parada_subida_id'] ?? null, // donde sube (vacio = origen)
            'metodo_pago' => $esQr ? 'QR' : 'EFECTIVO',
            'minutos_reserva' => $minutosQr,
        ];

        // Separar nombres y apellidos si viene todo en 'nombres'
        if (empty($ventaData['apellidos']) && strpos($ventaData['nombres'], ' ') !== false) {
            $parts = explode(' ', $ventaData['nombres'], 2);
            $ventaData['nombres'] = $parts[0];
            $ventaData['apellidos'] = $parts[1];
        }

        try {
            // El modelo ya implementa beginTransaction/commit/rollback
            $ticket = $this->rutaModel->registrarVentaTransaccion($ventaData);

            if ($ticket) {
                // Ensure ticket has all required fields for printing
                $response = [
                    'status' => 'success',
                    'id_boleto' => $ticket->id_boleto, // Keep for legacy
                    'ticket' => $ticket, // Return full object for printing
                    'tipo' => $datos['tipo']
                ];
            } else {
                $response = ['status' => 'error', 'msg' => 'No se pudo completar la operación (Asiento ocupado?)'];
            }
        } catch (Throwable $e) {
            // Log real error to file for debugging
            error_log("CRITICAL ERROR ControladorTransacciones: " . $e->getMessage() . "\n" . $e->getTraceAsString(), 3, __DIR__ . '/../../debug_errors.log');

            $response = ['status' => 'error', 'msg' => 'Error Interno: ' . $e->getMessage()];
        }

        // Output final JSON cleanly
        if (ob_get_length()) ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        // No cerrar el buffer aquí si el index lo maneja, pero como index llama a este metodo y este hace echo...
        // Lo mejor es hacer flush aqui y exit para evitar que index siga.
        ob_end_flush();
        exit;
    }

    private function procesarGestionReserva($datos)
    {
        $idBoleto = $datos['id_boleto'] ?? null;
        $subAccion = $datos['sub_accion'] ?? null;

        if (!$idBoleto || !$subAccion) {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos para gestión']);
            return;
        }

        try {
            $response = [];
            if ($subAccion === 'confirmar_pago') {
                // Registra el ingreso en la caja abierta (antes solo cambiaba el estado)
                $ticket = $this->rutaModel->confirmarPagoBoleto($idBoleto, SessionManager::getInstance()->getUserId(), 'EFECTIVO');
                $response = ['status' => 'success', 'tipo' => 'confirmacion', 'ticket' => $ticket];
            } elseif ($subAccion === 'eliminar') {
                if ($this->rutaModel->cancelarBoleto($idBoleto)) {
                    $response = ['status' => 'success', 'tipo' => 'eliminacion'];
                } else {
                    $response = ['status' => 'error', 'msg' => 'Error al eliminar reserva'];
                }
            } else {
                $response = ['status' => 'error', 'msg' => 'Sub-acción desconocida'];
            }
        } catch (Throwable $e) {
            error_log("CRITICAL ERROR GestionReserva: " . $e->getMessage(), 3, __DIR__ . '/../../debug_errors.log');
            $response = ['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()];
        }

        if (ob_get_length()) ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        ob_end_flush();
        exit;
    }

    /** Confirma el cobro de un boleto reservado (QR validado por el vendedor, o efectivo). */
    private function procesarConfirmacionPago($datos)
    {
        try {
            $ticket = $this->rutaModel->confirmarPagoBoleto(
                $datos['id_boleto'] ?? 0,
                SessionManager::getInstance()->getUserId(),
                ($datos['metodo'] ?? 'EFECTIVO') === 'QR' ? 'QR' : 'EFECTIVO',
                $datos['referencia'] ?? null
            );
            $response = ['status' => 'success', 'ticket' => $ticket];
        } catch (Throwable $e) {
            $response = ['status' => 'error', 'msg' => $e->getMessage()];
        }

        if (ob_get_length()) ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        ob_end_flush();
        exit;
    }

    /** Cancela un cobro QR pendiente y libera el asiento. */
    private function procesarCancelacionQr($datos)
    {
        $ok = $this->rutaModel->cancelarCobroQr($datos['id_boleto'] ?? 0);
        if (ob_get_length()) ob_clean();
        echo json_encode($ok ? ['status' => 'success'] : ['status' => 'error', 'msg' => 'El cobro ya no está pendiente (fue confirmado, cancelado o venció).']);
        ob_end_flush();
        exit;
    }

    // Método para servir la vista de impresion (GET)
    public function ticket_termico($id)
    {
        $ticket = $this->rutaModel->obtenerDatosTicket($id);
        if (!$ticket) die("Ticket inválido");
        $this->view('ventas/ticket_termico', ['ticket' => $ticket]);
    }

    private function validarSesion()
    {
        // Skip AJAX check for ticket_termico because it's a window.open (view)
        if (isset($_GET['url']) && strpos($_GET['url'], 'ticket_termico') !== false) {
            // Optional: Standard Auth Check (Non-AJAX) if you want protection
            // SessionManager::getInstance()->requireAuth();
            return;
        }

        // ✅ Usar SessionManager para validación
        $session = SessionManager::getInstance();
        $session->requireAuthAjax(); // Retorna JSON y sale si no está autenticado
    }
}
