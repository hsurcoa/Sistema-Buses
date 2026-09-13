<?php
class Boletos extends Controller
{
    private $rutaModel;

    public function __construct()
    {
        // Usamos RutaModel que ya tiene la lógica de base de datos
        $this->rutaModel = $this->model('RutaModel');
    }

    /**
     * Acción: procesar_nuevo
     * Maneja la creación de nuevas ventas o reservas
     */
    public function procesar_nuevo()
    {
        // Respuesta JSON siempre
        header('Content-Type: application/json');

        // validar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'msg' => 'Método no permitido']);
            return;
        }

        // Validar Sesión
        $this->validarSesion();

        // Obtener datos (JSON o POST Clásico)
        $input = json_decode(file_get_contents('php://input'), true);
        $datos = $input ?? $_POST;

        // Validaciones básicas
        if (empty($datos['viaje_id']) || empty($datos['numero_asiento']) || empty($datos['documento_pasajero'])) {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos (Viaje, Asiento o DNI)']);
            return;
        }

        // Determinar estado según 'tipo_accion' ('cobrar' o 'reservar')
        $estado = ($datos['tipo_accion'] === 'cobrar') ? 'vendido' : 'reservado';

        // Preparar array para el modelo
        $ventaData = [
            'viaje_id' => $datos['viaje_id'],
            'asiento' => $datos['numero_asiento'],
            'nombres' => $datos['nombre_pasajero'] ?? 'Anónimo', // Nombre completo
            'apellidos' => '',
            'documento' => $datos['documento_pasajero'],
            'celular' => $datos['celular'] ?? '',
            'precio' => $datos['precio'] ?? 0,
            'estado' => $estado,
            'usuario_id' => SessionManager::getInstance()->getUserId()
        ];

        // Separación inteligente de nombre/apellido si viene junto
        if (strpos($ventaData['nombres'], ' ') !== false) {
            $parts = explode(' ', $ventaData['nombres'], 2);
            $ventaData['nombres'] = $parts[0];
            $ventaData['apellidos'] = $parts[1];
        } else {
            $ventaData['apellidos'] = ''; // O manejar como prefieras
        }

        try {
            // Llamamos a la transacción del modelo
            // Este método ya maneja: Buscar cliente -> Insertar/Actualizar -> Insertar Boleto
            $ticket = $this->rutaModel->registrarVentaTransaccion($ventaData);

            if ($ticket) {
                echo json_encode([
                    'status' => 'success',
                    'id_boleto' => $ticket->id_boleto, // Asumiendo que retorna obj con id
                    'accion_realizada' => $datos['tipo_accion'],
                    'ticket_data' => $ticket // Datos extra para impresión
                ]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'El asiento ya está ocupado o hubo un error.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Excepción: ' . $e->getMessage()]);
        }
    }

    /**
     * Acción: gestion_reserva
     * Maneja confirmación o eliminación desde el Manifiesto
     */
    public function gestion_reserva()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'msg' => 'Método no permitido']);
            return;
        }

        $this->validarSesion();

        $input = json_decode(file_get_contents('php://input'), true);
        $datos = $input ?? $_POST;

        $idBoleto = $datos['id_boleto'] ?? null;
        $subAccion = $datos['sub_accion'] ?? null; // 'confirmar' o 'eliminar'

        if (!$idBoleto || !$subAccion) {
            echo json_encode(['status' => 'error', 'msg' => 'Falta ID de boleto o acción.']);
            return;
        }

        try {
            if ($subAccion === 'confirmar') {
                // UPDATE boletos SET estado='vendido'
                // Podríamos requerir actualizar el precio o cliente aquí también si se deseara
                // Usamos método simple del modelo, o cambiamos estado
                if ($this->rutaModel->cambiarEstadoBoleto($idBoleto, 'vendido')) {
                    echo json_encode(['status' => 'success', 'msg' => 'Venta confirmada exitosamente']);
                } else {
                    echo json_encode(['status' => 'error', 'msg' => 'No se pudo confirmar la venta.']);
                }
            } elseif ($subAccion === 'eliminar') {
                // DELETE FROM boletos
                if ($this->rutaModel->cancelarBoleto($idBoleto)) {
                    echo json_encode(['status' => 'success', 'msg' => 'Reserva eliminada (Asiento liberado).']);
                } else {
                    echo json_encode(['status' => 'error', 'msg' => 'No se pudo eliminar la reserva.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Acción desconocida']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Método para servir la vista de impresión
     */
    public function imprimir_ticket($id)
    {
        // Validar sesión si es necesario para imprimir
        // $this->validarSesion(); 

        // Obtener datos del ticket
        $ticket = $this->rutaModel->obtenerDatosTicket($id);

        if (!$ticket) {
            die("Ticket no encontrado");
        }

        $data = ['ticket' => $ticket];

        // Cargar vista dedicada
        $this->view('ventas/ticket_impresion', $data);
    }

    private function validarSesion()
    {
        // ✅ Usar SessionManager para validación
        $session = SessionManager::getInstance();
        $session->requireAuthAjax(); // Retorna JSON y sale si no está autenticado
    }
}
