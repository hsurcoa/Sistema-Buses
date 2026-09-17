<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Illuminate\Http\Request;

/**
 * Reportes: pasajeros/manifiesto por viaje, histórico, búsqueda de persona,
 * financiero. Puerto de `legacy/app/controllers/Reportes.php` (Fase 7;
 * reescrito a Eloquent al cerrar la sesion). Todo de solo lectura.
 */
class ReportesController extends Controller
{
    public function __construct(private ReporteService $reportes) {}

    public function pasajeros()
    {
        return view('reportes.pasajeros', ['data' => [
            'titulo' => 'Reporte de Pasajeros',
            'menu_activo' => 'reportes',
            'rutas' => $this->reportes->obtenerRutas(),
            'buses' => $this->reportes->obtenerBuses(),
        ]]);
    }

    public function buscarViajesAjax(Request $request)
    {
        $viajes = $this->reportes->buscarViajesConConteo(
            $request->input('fecha', ''),
            $request->input('ruta'),
            $request->input('bus')
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
            $request->input('ruta_hist')
        );

        return response()->json(['status' => 'success', 'data' => $resultados]);
    }

    public function buscarPersonaAjax(Request $request)
    {
        $resultados = $this->reportes->buscarPasajero($request->input('criterio', ''));

        return response()->json(['status' => $resultados->isEmpty() ? 'info' : 'success', 'data' => $resultados]);
    }

    public function imprimirManifiesto(int $viaje_id)
    {
        return view('reportes.imprimir_manifiesto', ['data' => [
            'viaje' => $this->reportes->obtenerInfoViaje($viaje_id),
            'pasajeros' => $this->reportes->obtenerPasajerosPorViaje($viaje_id),
        ]]);
    }

    public function financiero()
    {
        $labels = [];
        $dataChart = [];
        foreach ($this->reportes->obtenerTendenciaSemanal() as $t) {
            $labels[] = date('d/m', strtotime($t->fecha));
            $dataChart[] = $t->total;
        }

        return view('reportes.financiero', ['data' => [
            'titulo' => 'Reporte Financiero',
            'menu_activo' => 'reportes',
            'kpi_hoy' => $this->reportes->obtenerVentasHoy(),
            'kpi_mes' => $this->reportes->obtenerVentasMes(),
            'mejor_ruta' => $this->reportes->obtenerMejorRutaMes(),
            'chart_labels' => json_encode($labels),
            'chart_data' => json_encode($dataChart),
            'detalle' => $this->reportes->obtenerDesgloseFinanciero(),
        ]]);
    }

    public function financieroAjax(Request $request)
    {
        $data = $this->reportes->obtenerDesgloseFinanciero(
            $request->input('fecha_inicio'),
            $request->input('fecha_fin')
        );

        return response()->json(['status' => 'success', 'data' => $data]);
    }
}
