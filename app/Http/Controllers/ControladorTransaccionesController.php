<?php

namespace App\Http\Controllers;

use App\Services\CajaService;
use App\Services\ConfiguracionService;
use App\Services\RutaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Puerto de `legacy/app/controllers/ControladorTransacciones.php`: endpoint
 * unico (switch por `accion`) que usa `ventas/venta_pasajes.php` para nueva
 * venta/reserva, gestion de reserva, confirmacion de pago y cancelacion de
 * cobro QR — y la vista de ticket termico.
 */
class ControladorTransaccionesController extends Controller
{
    public function __construct(
        private RutaService $rutas,
        private CajaService $caja,
        private ConfiguracionService $config,
    ) {}

    public function index(Request $request)
    {
        $input = json_decode($request->getContent(), true);
        $datos = $input ?? $request->all();

        return match ($datos['accion'] ?? '') {
            'nueva_transaccion' => $this->procesarNuevaTransaccion($datos, $request),
            'gestionar_reserva' => $this->procesarGestionReserva($datos, $request),
            'confirmar_pago' => $this->procesarConfirmacionPago($datos, $request),
            'cancelar_qr' => $this->procesarCancelacionQr($datos),
            default => response()->json(['status' => 'error', 'msg' => 'Acción no válida']),
        };
    }

    private function procesarNuevaTransaccion(array $datos, Request $request)
    {
        if (empty($datos['viaje_id']) || empty($datos['asiento']) || empty($datos['documento'])) {
            return response()->json(['status' => 'error', 'msg' => 'Faltan datos obligatorios']);
        }

        $estado = $datos['tipo'] === 'venta' ? 'vendido' : 'reservado';
        $esQr = $datos['tipo'] === 'qr';
        $minutosQr = null;

        if ($esQr) {
            $caja = $this->caja->verificarCajaAbierta($request->user()->id);
            $sucursal = $caja->sucursal_id ?? $request->user()->sucursal_id;
            $qr = $this->config->obtenerPagoQr($sucursal);
            if (! $qr) {
                return response()->json(['status' => 'error', 'msg' => 'El cobro con QR no está configurado. Un administrador debe cargar el QR en Configuración.']);
            }
            $minutosQr = $qr['minutos'];
        }

        $ventaData = [
            'viaje_id' => $datos['viaje_id'],
            'asiento' => $datos['asiento'],
            'nombres' => $datos['nombres'] ?? 'Anónimo',
            'apellidos' => $datos['apellidos'] ?? '',
            'documento' => $datos['documento'],
            'celular' => $datos['celular'] ?? '',
            'precio' => $datos['precio'] ?? 0,
            'estado' => $estado,
            'usuario_id' => $request->user()->id,
            'parada_id' => $datos['parada_id'] ?? null,
            'parada_subida_id' => $datos['parada_subida_id'] ?? null,
            'metodo_pago' => $esQr ? 'QR' : 'EFECTIVO',
            'minutos_reserva' => $minutosQr,
        ];

        if (empty($ventaData['apellidos']) && str_contains($ventaData['nombres'], ' ')) {
            [$ventaData['nombres'], $ventaData['apellidos']] = explode(' ', $ventaData['nombres'], 2);
        }

        try {
            $ticket = $this->rutas->registrarVentaTransaccion($ventaData);

            $response = $ticket
                ? ['status' => 'success', 'id_boleto' => $ticket->id_boleto, 'ticket' => $ticket, 'tipo' => $datos['tipo']]
                : ['status' => 'error', 'msg' => 'No se pudo completar la operación (Asiento ocupado?)'];
        } catch (\Throwable $e) {
            Log::error('ControladorTransaccionesController::procesarNuevaTransaccion: '.$e->getMessage());
            $response = ['status' => 'error', 'msg' => 'Error Interno: '.$e->getMessage()];
        }

        return response()->json($response, 200, [], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    private function procesarGestionReserva(array $datos, Request $request)
    {
        $idBoleto = $datos['id_boleto'] ?? null;
        $subAccion = $datos['sub_accion'] ?? null;

        if (! $idBoleto || ! $subAccion) {
            return response()->json(['status' => 'error', 'msg' => 'Datos incompletos para gestión']);
        }

        try {
            $response = match ($subAccion) {
                'confirmar_pago' => ['status' => 'success', 'tipo' => 'confirmacion', 'ticket' => $this->rutas->confirmarPagoBoleto($idBoleto, $request->user()->id, 'EFECTIVO')],
                'eliminar' => $this->rutas->cancelarBoleto($idBoleto)
                    ? ['status' => 'success', 'tipo' => 'eliminacion']
                    : ['status' => 'error', 'msg' => 'Error al eliminar reserva'],
                default => ['status' => 'error', 'msg' => 'Sub-acción desconocida'],
            };
        } catch (\Throwable $e) {
            Log::error('ControladorTransaccionesController::procesarGestionReserva: '.$e->getMessage());
            $response = ['status' => 'error', 'msg' => 'Error: '.$e->getMessage()];
        }

        return response()->json($response, 200, [], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    private function procesarConfirmacionPago(array $datos, Request $request)
    {
        try {
            $ticket = $this->rutas->confirmarPagoBoleto(
                $datos['id_boleto'] ?? 0,
                $request->user()->id,
                ($datos['metodo'] ?? 'EFECTIVO') === 'QR' ? 'QR' : 'EFECTIVO',
                $datos['referencia'] ?? null
            );
            $response = ['status' => 'success', 'ticket' => $ticket];
        } catch (\Throwable $e) {
            $response = ['status' => 'error', 'msg' => $e->getMessage()];
        }

        return response()->json($response, 200, [], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    private function procesarCancelacionQr(array $datos)
    {
        $ok = $this->rutas->cancelarCobroQr($datos['id_boleto'] ?? 0);

        return response()->json($ok
            ? ['status' => 'success']
            : ['status' => 'error', 'msg' => 'El cobro ya no está pendiente (fue confirmado, cancelado o venció).']);
    }

    public function ticketTermico(int $id)
    {
        $ticket = $this->rutas->obtenerDatosTicket($id);
        if (! $ticket) {
            abort(404, 'Ticket inválido');
        }

        return view('ventas.ticket_termico', ['data' => ['ticket' => $ticket]]);
    }
}
