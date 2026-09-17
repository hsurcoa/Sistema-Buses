<?php

namespace App\Http\Controllers;

use App\Models\Terminal;
use App\Services\ReporteService;
use App\Services\RutaService;
use Illuminate\Http\Request;

/**
 * Reportes: pasajeros/manifiesto por viaje, histórico, búsqueda de persona,
 * financiero. Puerto de `legacy/app/controllers/Reportes.php` (Fase 7;
 * reescrito a Eloquent al cerrar la sesion). Todo de solo lectura.
 *
 * Hallazgo de la auditoria multisucursal (2026-09-17): ningun metodo filtraba
 * por sucursal, asi que cualquier vendedor -de cualquier sede- veia la plata
 * y los pasajeros de TODA la empresa. Mismo criterio ROLES_GLOBALES que ya
 * usan Dashboard/Caja: solo Administrador/Supervisor ven todas las sucursales.
 */
class ReportesController extends Controller
{
    private const ROLES_GLOBALES = ['Administrador', 'Supervisor'];

    public function __construct(private ReporteService $reportes, private RutaService $rutas) {}

    /** Sucursal a la que se restringe el reporte: null = todas (solo roles globales). */
    private function sucursalId(Request $request): ?int
    {
        $usuario = $request->user();
        $esGlobal = in_array($usuario->rol?->nombre, self::ROLES_GLOBALES, true);

        return $esGlobal ? ((int) $request->query('sucursal', 0) ?: null) : ((int) $usuario->sucursal_id ?: null);
    }

    private function datosSucursal(Request $request): array
    {
        $usuario = $request->user();
        $esGlobal = in_array($usuario->rol?->nombre, self::ROLES_GLOBALES, true);
        $sucursalId = $this->sucursalId($request);

        return [
            'es_global' => $esGlobal,
            'sucursal_id' => $sucursalId,
            'sucursales' => $esGlobal ? Terminal::where('estado', 1)->orderBy('nombre_sede')->get() : collect(),
            'sin_sucursal' => ! $esGlobal && ! $usuario->sucursal_id,
        ];
    }

    public function pasajeros(Request $request)
    {
        return view('reportes.pasajeros', ['data' => array_merge([
            'titulo' => 'Reporte de Pasajeros',
            'menu_activo' => 'reportes',
            'rutas' => $this->reportes->obtenerRutas(),
            'buses' => $this->reportes->obtenerBuses(),
        ], $this->datosSucursal($request))]);
    }

    public function buscarViajesAjax(Request $request)
    {
        $viajes = $this->reportes->buscarViajesConConteo(
            $request->input('fecha', ''),
            $request->input('ruta'),
            $request->input('bus'),
            $this->sucursalId($request)
        );

        return response()->json(['status' => $viajes->isEmpty() ? 'info' : 'success', 'data' => $viajes]);
    }

    public function obtenerManifiestoAjax(int $viaje_id)
    {
        return response()->json([
            'status' => 'success',
            'viaje' => $this->reportes->obtenerInfoViaje($viaje_id),
            'pasajeros' => $this->reportes->obtenerPasajerosPorViaje($viaje_id),
        ]);
    }

    public function buscarHistoricoAjax(Request $request)
    {
        $resultados = $this->reportes->buscarHistorico(
            $request->input('fecha_inicio', ''),
            $request->input('fecha_fin', ''),
            $request->input('ruta_hist'),
            $this->sucursalId($request)
        );

        return response()->json(['status' => 'success', 'data' => $resultados]);
    }

    public function buscarPersonaAjax(Request $request)
    {
        $resultados = $this->reportes->buscarPasajero($request->input('criterio', ''), $this->sucursalId($request));

        return response()->json(['status' => $resultados->isEmpty() ? 'info' : 'success', 'data' => $resultados]);
    }

    public function imprimirManifiesto(int $viaje_id)
    {
        return view('reportes.imprimir_manifiesto', ['data' => [
            'viaje' => $this->reportes->obtenerInfoViaje($viaje_id),
            'pasajeros' => $this->reportes->obtenerPasajerosPorViaje($viaje_id),
        ]]);
    }

    public function financiero(Request $request)
    {
        $sucursalId = $this->sucursalId($request);

        $labels = [];
        $dataChart = [];
        foreach ($this->reportes->obtenerTendenciaSemanal($sucursalId) as $t) {
            $labels[] = date('d/m', strtotime($t->fecha));
            $dataChart[] = $t->total;
        }

        return view('reportes.financiero', ['data' => array_merge([
            'titulo' => 'Reporte Financiero',
            'menu_activo' => 'reportes',
            'kpi_hoy' => $this->reportes->obtenerVentasHoy($sucursalId),
            'kpi_mes' => $this->reportes->obtenerVentasMes($sucursalId),
            'mejor_ruta' => $this->reportes->obtenerMejorRutaMes($sucursalId),
            'chart_labels' => json_encode($labels),
            'chart_data' => json_encode($dataChart),
            'detalle' => $this->reportes->obtenerDesgloseFinanciero(null, null, $sucursalId),
        ], $this->datosSucursal($request))]);
    }

    public function financieroAjax(Request $request)
    {
        $data = $this->reportes->obtenerDesgloseFinanciero(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin'),
            $this->sucursalId($request)
        );

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function cancelaciones(Request $request)
    {
        $fechaInicio = date('Y-m-d', strtotime('-30 days'));
        $fechaFin = date('Y-m-d');

        return view('reportes.cancelaciones', ['data' => array_merge([
            'titulo' => 'Bitácora de Cancelaciones',
            'menu_activo' => 'reportes',
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'cancelaciones' => $this->reportes->obtenerCancelaciones($fechaInicio, $fechaFin, $this->sucursalId($request)),
        ], $this->datosSucursal($request))]);
    }

    public function cancelacionesAjax(Request $request)
    {
        $data = $this->reportes->obtenerCancelaciones(
            $request->input('fecha_inicio', ''),
            $request->input('fecha_fin', ''),
            $this->sucursalId($request),
            $request->boolean('solo_pendientes')
        );

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /** Procesa la devolución de una cancelación que había quedado pendiente. */
    public function procesarDevolucionAjax(Request $request)
    {
        $cancelacionId = (int) $request->input('cancelacion_id');
        $metodo = $request->input('metodo_devolucion') === 'QR' ? 'QR' : ($request->input('metodo_devolucion') === 'OTRO' ? 'OTRO' : 'EFECTIVO');

        if (! $cancelacionId) {
            return response()->json(['status' => 'error', 'message' => 'Falta indicar la cancelación.']);
        }

        try {
            $this->rutas->procesarDevolucionPendiente($cancelacionId, $request->user()->id, $metodo);

            return response()->json(['status' => 'success', 'message' => 'Devolución registrada correctamente.']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
