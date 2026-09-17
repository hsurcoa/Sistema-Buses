<?php

namespace App\Http\Controllers;

use App\Models\Terminal;
use App\Services\CajaService;
use App\Services\ConfiguracionService;
use App\Services\RutaService;
use App\Services\TipoBusService;
use App\Services\TramoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Venta de pasajes, creacion de rutas/viajes y gestion de boletos (Fase 3
 * de la migracion; reescrito a Eloquent al cerrar la sesion — ver informe
 * de fin de sesion). Puerto de `legacy/app/controllers/Ventas.php`.
 */
class VentasController extends Controller
{
    public function __construct(
        private RutaService $rutas,
        private TramoService $tramos,
        private TipoBusService $tiposBus,
        private CajaService $caja,
        private ConfiguracionService $config,
    ) {}

    private function noCache(Response $response): Response
    {
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Vista de venta de pasajes (mapa de asientos).
     */
    public function ventaPasajes(Request $request)
    {
        $viajesProgramados = $this->rutas->listarViajesProgramados(50, 0);

        // Sucursal donde se vende: la de la caja abierta o, si no hay, la del usuario.
        $caja = $this->caja->verificarCajaAbierta($request->user()->id);
        $sucursalVenta = $caja->sucursal_id ?? $request->user()->sucursal_id;

        $cfg = $this->config->obtenerConfiguracion();

        $data = [
            'title' => 'Venta de Pasajes',
            'viajesProgramados' => $viajesProgramados,
            'pago_qr' => $this->config->obtenerPagoQr($sucursalVenta),
            'sucursal_venta' => $sucursalVenta ? Terminal::find($sucursalVenta) : null,
            'empresa' => ['nombre' => $cfg['empresa_nombre'] ?? SITENAME, 'nit' => $cfg['empresa_nit'] ?? ''],
        ];

        return $this->noCache(response(view('ventas.venta_pasajes', ['data' => $data])));
    }

    /**
     * Vista para crear rutas de viaje.
     */
    public function crearRuta()
    {
        $data = [
            'title' => 'Crear Rutas de Viaje',
            'rutas' => $this->rutas->listarRutas(100, 0),
            'tiposBuses' => $this->tiposBus->listarTiposParaFlota(),
            'terminales' => Terminal::orderBy('nombre_sede')->get(),
            'viajesProgramados' => $this->rutas->listarViajesProgramados(50, 0),
            'choferes' => $this->rutas->listarChoferes(),
        ];

        return view('ventas.crear_ruta', ['data' => $data]);
    }

    /**
     * Guardar nueva ruta de viaje (o editar una existente). Responde con una
     * pagina HTML autonoma (SweetAlert2 + redirect), igual que el legacy: el
     * formulario objetivo es un iframe/submit clasico, no AJAX.
     */
    public function guardarRutaViaje(Request $request)
    {
        $data = [
            'id' => $request->input('id', ''),
            'ruta_id' => $request->input('ruta_id', ''),
            'tipo_bus_id' => $request->input('tipo_bus_id', ''),
            'terminal_origen_id' => $request->input('terminal_origen_id', ''),
            'terminal_destino_id' => $request->input('terminal_destino_id', ''),
            'bus_id' => $request->input('bus_id', ''),
            'chofer_id' => $request->input('chofer_id', ''),
            'fecha_salida' => $request->input('fecha_salida', ''),
            'hora_salida' => $request->input('hora_salida', ''),
            'hora_llegada' => $request->input('hora_llegada', ''),
            'precio_base' => (float) $request->input('precio_base', 0),
            'tipo_servicio' => $request->input('tipo_servicio', 'Ejecutivo'),
            'servicios' => $request->input('servicios', []),
            'notas' => $request->input('notas', ''),
            'estado' => $request->input('estado', 'Programado'),
        ];

        $render = fn (string $icon, string $title, string $text) => response()->view('ventas.respuesta_swal', [
            'icon' => $icon, 'title' => $title, 'text' => $text,
            'redirect' => URLROOT.'/ventas/crear_ruta',
        ]);

        if (empty($data['ruta_id']) || empty($data['tipo_bus_id']) || empty($data['fecha_salida'])) {
            return $render('error', 'Error de Validación', 'Complete todos los campos obligatorios');
        }

        try {
            $resultado = $this->rutas->guardarRutaViaje($data);

            return $resultado === true
                ? $render('success', '¡Éxito!', 'Ruta de viaje guardada exitosamente')
                : $render('error', 'Error', (string) $resultado);
        } catch (\Exception $e) {
            return $render('error', 'Error del Sistema', $e->getMessage());
        }
    }

    /**
     * Datos de un viaje programado para edicion/venta (AJAX): configuracion
     * del bus, asientos ocupados en el tramo pedido, tarifas y paradas.
     */
    public function obtenerRutaViaje(int $id, Request $request)
    {
        $viaje = $this->rutas->obtenerViajePorId($id);

        if (! $viaje) {
            return $this->noCache(response()->json(['status' => 'error', 'msg' => 'Viaje no encontrado'], 404));
        }

        if (is_string($viaje->servicios_incluidos)) {
            $viaje->servicios_incluidos = json_decode($viaje->servicios_incluidos, true);
        }
        $viaje->fecha_salida_date = date('Y-m-d', strtotime($viaje->fecha_salida));

        $tipoBus = $this->tiposBus->obtenerTipoBus((int) $viaje->tipo_bus_id);

        if ($tipoBus) {
            $viaje->layout_config = ! empty($tipoBus->configuracion_asientos)
                ? json_decode($tipoBus->configuracion_asientos, true)
                : ['pisos' => $tipoBus->pisos ?? 1, 'columnas' => 4, 'posicion_pasillo' => 2, 'asientos_total' => $tipoBus->capacidad ?? 40];
            $viaje->asientos_total = $tipoBus->capacidad ?? 40;
            $viaje->pisos = $tipoBus->pisos ?? 1;
            $viaje->nombre_tipo_bus = $tipoBus->nombre ?? 'Bus Estándar';
        } else {
            $viaje->layout_config = ['pisos' => 1, 'columnas' => 4, 'posicion_pasillo' => 2, 'asientos_total' => 40];
            $viaje->asientos_total = 40;
            $viaje->pisos = 1;
            $viaje->nombre_tipo_bus = 'Bus Estándar';
        }

        $subida = (int) $request->query('subida', 0);
        $bajada = (int) $request->query('bajada', 0);
        $viaje->asientos_ocupados = $this->rutas->obtenerAsientosOcupados((int) $viaje->id, $subida, $bajada);

        $viaje->puntos = $this->tramos->puntos((int) $viaje->ruta_id);
        $viaje->tarifas = $this->tramos->matriz((int) $viaje->ruta_id);
        $viaje->tramo = ['subida' => $subida, 'bajada' => $bajada];

        $viaje->lista_rutas = $this->rutas->listarRutasActivas();
        $viaje->paradas = $this->rutas->obtenerParadasPorRuta((int) $viaje->ruta_id);

        return $this->noCache(response()->json(['success' => true, 'data' => $viaje], 200, [], JSON_UNESCAPED_UNICODE));
    }

    /** AJAX: buses activos de un tipo. */
    public function obtenerBusesTipo(Request $request)
    {
        $tipoId = $request->input('tipo_id') ?? (json_decode($request->getContent(), true)['tipo_id'] ?? null);

        if (! $tipoId) {
            return response()->json(['success' => false, 'message' => 'Falta el ID del tipo de bus'], 400);
        }

        $buses = $this->rutas->obtenerBusesPorTipo((int) $tipoId);

        return response()->json([
            'success' => true,
            'buses' => $buses,
            'count' => count($buses),
            'tipo_bus_id' => $tipoId,
        ]);
    }

    /** AJAX: tripulacion asignada a un bus. */
    public function obtenerTripulacionBus(Request $request)
    {
        $busId = $request->input('bus_id') ?? (json_decode($request->getContent(), true)['bus_id'] ?? null);

        if (! $busId) {
            return response()->json(['success' => false, 'message' => 'Falta el ID del bus'], 400);
        }

        $tripulacion = $this->rutas->obtenerTripulacionBus((int) $busId);

        return $tripulacion
            ? response()->json(['success' => true, 'data' => $tripulacion])
            : response()->json(['success' => false, 'message' => 'No hay tripulación asignada para este bus']);
    }

    /** Despachar bus: finaliza el viaje actual y crea uno nuevo (preserva historial). */
    public function despacharRuta(int $id)
    {
        $nuevoViajeId = $this->rutas->despacharYCrearNuevo($id);

        return $nuevoViajeId
            ? response()->json([
                'success' => true,
                'nuevo_viaje_id' => $nuevoViajeId,
                'redirect' => URLROOT.'/ventas/venta_pasajes?viaje_id='.$nuevoViajeId,
                'message' => 'Bus despachado exitosamente. Nuevo viaje creado.',
            ])
            : response()->json(['success' => false, 'message' => 'Error al crear nuevo viaje. Revise los logs del servidor.']);
    }

    /** Eliminar viaje programado. */
    public function eliminarRutaViaje(int $id)
    {
        try {
            $resultado = $this->rutas->eliminarViaje($id);

            return $resultado === true
                ? response()->json(['success' => true])
                : response()->json(['success' => false, 'message' => is_string($resultado) ? $resultado : 'No se pudo eliminar el viaje por reglas de negocio.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /** Procesar venta/reserva de un asiento (usado por crear_ruta.php). */
    public function procesarVenta(Request $request)
    {
        $input = json_decode($request->getContent(), true);
        $datos = $input ?? $request->all();

        if (empty($datos['viaje_id']) || empty($datos['numero_asiento'])) {
            return response()->json(['status' => 'error', 'msg' => 'Faltan datos (Viaje o Asiento).']);
        }

        $estado = 'reservado';
        if (isset($datos['accion'])) {
            $estado = ($datos['accion'] === 'venta') ? 'vendido' : 'reservado';
        } elseif (isset($datos['tipo_venta'])) {
            $estado = ($datos['tipo_venta'] == 1) ? 'vendido' : 'reservado';
        }

        $ventaData = [
            'viaje_id' => $datos['viaje_id'],
            'asiento' => $datos['numero_asiento'],
            'nombres' => $datos['nombre_pasajero'] ?? 'Anónimo',
            'apellidos' => '',
            'documento' => $datos['documento_pasajero'] ?? '0000000',
            'celular' => $datos['celular'] ?? '',
            'precio' => $datos['precio'] ?? 0,
            'estado' => $estado,
            'usuario_id' => $request->user()->id,
            'parada_id' => $datos['parada_id'] ?? null,
        ];

        if (empty($ventaData['apellidos']) && str_contains($ventaData['nombres'], ' ')) {
            [$ventaData['nombres'], $ventaData['apellidos']] = explode(' ', $ventaData['nombres'], 2);
        }

        try {
            $ticket = $this->rutas->registrarVentaTransaccion($ventaData);

            return $ticket
                ? response()->json(['status' => 'success', 'msg' => 'Operación exitosa', 'ticket' => $ticket], 200, [], JSON_UNESCAPED_UNICODE)
                : response()->json(['status' => 'error', 'msg' => 'No se pudo guardar la venta (Asiento ocupado o error DB).']);
        } catch (\Exception $e) {
            Log::error('VentasController::procesarVenta: '.$e->getMessage());

            return response()->json(['status' => 'error', 'msg' => 'Ocurrió un error interno al procesar la venta. Intente nuevamente.']);
        }
    }

    /** Datos crudos de una reserva/boleto para editar. */
    public function obtenerDatosReserva(int $id)
    {
        $datos = $this->rutas->obtenerTicketPorId($id);

        return $datos
            ? response()->json(['success' => true, 'data' => $datos])
            : response()->json(['success' => false, 'message' => 'No encontrado']);
    }

    /** Manifiesto detallado de un viaje (JSON, para el modal de reporte). */
    public function obtenerManifiesto(int $viajeId)
    {
        try {
            $viaje = $this->rutas->obtenerViajePorId($viajeId);
            if (! $viaje) {
                return response()->json(['success' => false, 'error' => 'Viaje no encontrado']);
            }

            $pasajeros = $this->rutas->obtenerPasajerosPorViaje($viajeId);

            $cabecera = [
                'ruta' => ($viaje->origen ?? 'N/A').' -> '.($viaje->destino ?? 'N/A'),
                'origen' => $viaje->origen ?? 'N/A',
                'destino' => $viaje->destino ?? 'N/A',
                'fecha_salida' => date('d/m/Y', strtotime($viaje->fecha_salida ?? 'now')),
                'hora_salida' => substr($viaje->hora_salida ?? '00:00', 0, 5),
                'placa_bus' => $viaje->bus_placa ?? 'Sin asignar',
                'numero_unidad' => $viaje->bus_numero ?? 'N/A',
                'conductor' => $viaje->chofer_nombre ?? 'No asignado',
                'tipo_bus' => $viaje->tipo_bus ?? 'Estándar',
                'capacidad_total' => $viaje->capacidad ?? 40,
            ];

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
                    'estado_boleto' => $p->estado,
                    'destino_especifico' => $p->destino ?? $viaje->destino,
                    'precio' => number_format((float) $p->precio, 2, '.', ''),
                    'fecha_venta' => date('d/m/Y H:i', strtotime($p->fecha_venta ?? 'now')),
                ];

                if ($p->estado === 'vendido') {
                    $totalRecaudado += (float) $p->precio;
                }
                $totalPasajeros++;
            }

            return response()->json([
                'success' => true,
                'cabecera' => $cabecera,
                'pasajeros' => $pasajerosFormateados,
                'resumen' => [
                    'total_pasajeros' => $totalPasajeros,
                    'total_recaudado' => number_format($totalRecaudado, 2, '.', ''),
                    'asientos_disponibles' => $cabecera['capacidad_total'] - $totalPasajeros,
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Error del servidor: '.$e->getMessage()]);
        }
    }

    /** Listado plano de pasajeros de un viaje. */
    public function listarManifiesto(int $viajeId)
    {
        try {
            $viajeExiste = $this->rutas->obtenerViajePorId($viajeId);
            if (! $viajeExiste) {
                return response()->json(['success' => false, 'error' => 'El viaje no existe en la base de datos', 'pasajeros' => []], 404);
            }

            $pasajeros = $this->rutas->obtenerPasajerosPorViaje($viajeId);

            return response()->json([
                'success' => true,
                'pasajeros' => $pasajeros,
                'total' => count($pasajeros),
                'viaje_id' => $viajeId,
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            Log::error("VentasController::listarManifiesto (viaje_id: $viajeId): ".$e->getMessage());

            return response()->json(['success' => false, 'error' => 'Error interno del servidor', 'pasajeros' => []], 500);
        }
    }

    /** Cancelar boleto / liberar reserva. */
    public function cancelarBoleto(int $id)
    {
        return $this->rutas->cancelarBoleto($id)
            ? response()->json(['status' => 'success', 'success' => true, 'mensaje' => 'Reserva eliminada'])
            : response()->json(['status' => 'error', 'success' => false, 'mensaje' => 'Error al cancelar']);
    }

    /**
     * Reporte PDF del manifiesto. Antes usaba FPDF (`legacy/app/libraries/fpdf`,
     * dibujo celda por celda); reescrito con Dompdf + HTML (misma libreria
     * que ya usan los exports de Caja) para no depender de una libreria
     * vendida a mano dentro de `legacy/`.
     */
    public function imprimirManifiesto(int $viajeId)
    {
        $viaje = $this->rutas->obtenerViajePorId($viajeId);
        $pasajeros = $this->rutas->obtenerPasajerosPorViaje($viajeId);

        if (! $viaje) {
            abort(404, 'Viaje no encontrado');
        }

        $fecha = date('d/m/Y', strtotime($viaje->fecha_salida));
        $hora = substr($viaje->hora_salida, 0, 5);
        $busNumero = $viaje->bus_numero ?? '---';
        $placa = $viaje->bus_placa ?? '---';
        $conductor = $viaje->chofer_nombre ?? 'No Asignado';

        $totalPasajeros = 0;
        $totalRecaudado = 0;
        $filas = '';
        foreach ($pasajeros as $index => $row) {
            $filas .= '<tr>
                <td class="c">'.($index + 1).'</td>
                <td class="c">'.e(ucfirst($row->estado)).'</td>
                <td>'.e($row->nombre_pasajero).'</td>
                <td class="c">'.e($row->numero_documento).'</td>
                <td class="c">'.e($row->numero_asiento).'</td>
                <td>'.e($row->destino).'</td>
                <td class="r">'.number_format($row->precio, 2).'</td>
            </tr>';
            $totalPasajeros++;
            if ($row->estado == 'vendido') {
                $totalRecaudado += $row->precio;
            }
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body { font-family: Arial, sans-serif; font-size: 9pt; }
            h1 { text-align: center; font-size: 16pt; margin-bottom: 2pt; }
            .meta { text-align: center; font-size: 10pt; margin-bottom: 10pt; }
            .datos td { border: 1px solid #000; padding: 4pt 6pt; font-weight: bold; }
            table.manifiesto { width: 100%; border-collapse: collapse; margin-top: 10pt; }
            table.manifiesto th { background-color: #e6e6e6; border: 1px solid #000; padding: 4pt; font-size: 9pt; }
            table.manifiesto td { border: 1px solid #000; padding: 4pt; }
            .c { text-align: center; } .r { text-align: right; }
            .totales { text-align: right; font-weight: bold; margin-top: 6pt; }
            .firmas { margin-top: 40pt; width: 100%; }
            .firmas td { text-align: center; padding-top: 4pt; border-top: 1px solid #000; }
        </style></head><body>
            <h1>MANIFIESTO DE PASAJEROS</h1>
            <div class="meta">Ruta: '.e($viaje->origen.' - '.$viaje->destino).'&nbsp;&nbsp;|&nbsp;&nbsp;Fecha: '.$fecha.'&nbsp;&nbsp;|&nbsp;&nbsp;Hora: '.$hora.'</div>
            <table class="datos" style="width:100%;"><tr>
                <td>UNIDAD N&deg;: '.e($busNumero).'</td>
                <td>PLACA: '.e($placa).'</td>
                <td>CONDUCTOR: '.e($conductor).'</td>
            </tr></table>
            <table class="manifiesto">
                <thead><tr><th style="width:4%">N</th><th style="width:10%">Estado</th><th style="width:30%">Nombre Completo</th><th style="width:12%">CI</th><th style="width:6%">As.</th><th style="width:26%">Destino</th><th style="width:12%">Monto</th></tr></thead>
                <tbody>'.$filas.'</tbody>
            </table>
            <div class="totales">Total Pasajeros: '.$totalPasajeros.'      Total Recaudado (Pagado): Bs. '.number_format($totalRecaudado, 2).'</div>
            <table class="firmas"><tr><td style="width:45%">Firma Conductor</td><td style="width:10%"></td><td style="width:45%">Firma Despacho</td></tr></table>
        </body></html>';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();
        $dompdf->stream('Manifiesto_Viaje_'.$viajeId.'.pdf', ['Attachment' => false]);
        exit;
    }

    /** Manifiesto en HTML horizontal (para imprimir desde el navegador). */
    public function imprimirManifiestoHtml(int $viajeId)
    {
        $viaje = $this->rutas->obtenerViajePorId($viajeId);

        if (! $viaje) {
            abort(404, 'Viaje no encontrado');
        }

        return view('ventas.imprimir_manifiesto', [
            'viaje' => $viaje,
            'pasajeros' => $this->rutas->obtenerPasajerosPorViaje($viajeId),
        ]);
    }

    /** Estado de un cobro QR (para la pantalla del pasajero y el modal del vendedor). */
    public function estadoCobro(int $id)
    {
        $this->rutas->liberarCobrosQrVencidos();
        $cobro = $this->rutas->estadoCobroBoleto($id);

        return $this->noCache(response()->json(
            $cobro ? ['success' => true, 'data' => $cobro] : ['success' => false],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        ));
    }

    /** Pantalla del pasajero (segundo monitor): QR grande, monto y estado del pago. */
    public function pantallaQr(int $id)
    {
        $cobro = $this->rutas->estadoCobroBoleto($id);

        return view('ventas.pantalla_qr', [
            'data' => [
                'cobro' => $cobro,
                'config' => $this->config->obtenerConfiguracion(),
                'qr' => $this->config->obtenerPagoQr($cobro->sucursal_id ?? null),
            ],
        ]);
    }

    /** Gestion de boleto: editar, confirmar/vender, eliminar (modal desde el manifiesto). */
    public function gestionBoleto(Request $request)
    {
        $id = $request->input('id');
        $accion = $request->input('accion');
        $nombres = $request->input('nombres', '');
        $apellidos = $request->input('apellidos', '');
        $documento = $request->input('documento', '');
        $precio = $request->input('precio', 0);

        if (! $id || ! $accion) {
            return response()->json(['success' => false, 'mensaje' => 'Faltan datos']);
        }

        try {
            switch ($accion) {
                case 'eliminar':
                    if ($this->rutas->cancelarBoleto($id)) {
                        return response()->json(['success' => true, 'mensaje' => 'Boleto eliminado correctamente']);
                    }
                    throw new \Exception('Error al eliminar');

                case 'guardar_nuevo':
                    $datosNuevo = [
                        'viaje_id' => $request->input('viaje_id'),
                        'asiento' => $request->input('asiento'),
                        'documento' => $documento,
                        'nombres' => $nombres,
                        'apellidos' => $apellidos,
                        'celular' => $request->input('celular', ''),
                        'precio' => $precio,
                        'estado' => $request->input('estado_nuevo', 'reservado'),
                        'usuario_id' => $request->user()->id,
                    ];

                    $ticket = $this->rutas->registrarVentaTransaccion($datosNuevo);
                    if ($ticket) {
                        $msg = $datosNuevo['estado'] == 'vendido' ? 'Venta registrada' : 'Reserva registrada';

                        return response()->json(['success' => true, 'mensaje' => $msg, 'ticket' => $ticket]);
                    }
                    throw new \Exception('No se pudo registrar el boleto');

                case 'confirmar_venta':
                case 'actualizar_datos':
                    $this->rutas->actualizarDatosBoleto($id, $nombres, $apellidos, $precio, $documento);

                    if ($accion === 'confirmar_venta') {
                        $ticketData = $this->rutas->confirmarPagoBoleto($id, $request->user()->id, 'EFECTIVO');

                        return response()->json(['success' => true, 'mensaje' => 'Venta confirmada exitosamente', 'ticket' => $ticketData]);
                    }

                    return response()->json(['success' => true, 'mensaje' => 'Datos actualizados correctamente']);

                default:
                    return response()->json(['success' => false, 'mensaje' => 'Acción no válida']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensaje' => 'Error: '.$e->getMessage()]);
        }
    }

    /** Contadores de asientos (libres/reservados/vendidos) para el dashboard del viaje. */
    public function obtenerConteoAsientos(int $id)
    {
        try {
            $conteos = $this->rutas->obtenerConteoAsientos($id);

            return $this->noCache(response()->json(['success' => true, 'data' => $conteos]));
        } catch (\Exception $e) {
            return $this->noCache(response()->json(['success' => false, 'data' => ['libres' => 0, 'reservados' => 0, 'vendidos' => 0]]));
        }
    }
}
