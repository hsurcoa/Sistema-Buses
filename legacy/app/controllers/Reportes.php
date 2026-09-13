<?php
class Reportes extends Controller
{
    private $reporteModel;

    public function __construct()
    {
        // Cargar modelo
        $this->reporteModel = $this->model('ReporteModel');
    }

    public function index()
    {
        // Redireccionar al método principal
        $this->pasajeros();
    }

    public function pasajeros()
    {
        // Obtener datos iniciales para los filtros
        $rutas = $this->reporteModel->obtenerRutas();
        $buses = $this->reporteModel->obtenerBuses();

        $data = [
            'titulo' => 'Reporte de Pasajeros',
            'menu_activo' => 'reportes',
            'rutas' => $rutas,
            'buses' => $buses
        ];

        // Cargar vista
        $this->view('reportes/pasajeros', $data);
    }

    // --- API JSON ---

    public function buscar_viajes_ajax()
    {
        // Validar petición POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Obtener filtros
            $fecha = $_POST['fecha'] ?? '';
            $ruta = $_POST['ruta'] ?? null;
            $bus = $_POST['bus'] ?? null;

            // Consultar modelo
            $viajes = $this->reporteModel->buscarViajesConConteo($fecha, $ruta, $bus);

            // Devolver JSON
            header('Content-Type: application/json');
            echo json_encode([
                'status' => empty($viajes) ? 'info' : 'success',
                'data' => $viajes
            ]);
            exit;
        }
    }

    public function obtener_manifiesto_ajax($viaje_id)
    {
        $pasajeros = $this->reporteModel->obtenerPasajerosPorViaje($viaje_id);
        $viajeInfo = $this->reporteModel->obtenerInfoViaje($viaje_id);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'viaje' => $viajeInfo,
            'pasajeros' => $pasajeros
        ]);
        exit;
    }

    // ✅ NUEVO: Endpoint Histórico
    public function buscar_historico_ajax()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $fecha_inicio = $_POST['fecha_inicio'] ?? '';
            $fecha_fin = $_POST['fecha_fin'] ?? '';
            $ruta_hist = $_POST['ruta_hist'] ?? null;

            $resultados = $this->reporteModel->buscarHistorico($fecha_inicio, $fecha_fin, $ruta_hist);

            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $resultados
            ]);
            exit;
        }
    }

    // ✅ NUEVO: Endpoint Búsqueda Individual
    public function buscar_persona_ajax()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $criterio = $_POST['criterio'] ?? '';

            $resultados = $this->reporteModel->buscarPasajero($criterio);

            header('Content-Type: application/json');
            echo json_encode([
                'status' => empty($resultados) ? 'info' : 'success',
                'data' => $resultados
            ]);
            exit;
        }
    }

    // Vista de impresión limpia
    public function imprimir_manifiesto($viaje_id)
    {
        $pasajeros = $this->reporteModel->obtenerPasajerosPorViaje($viaje_id);
        $viajeInfo = $this->reporteModel->obtenerInfoViaje($viaje_id);

        $data = [
            'viaje' => $viajeInfo,
            'pasajeros' => $pasajeros
        ];

        $this->view('reportes/imprimir_manifiesto', $data);
    }
    // --- NUEVO MÓDULO: REPORTE FINANCIERO ---

    public function financiero()
    {
        // 1. Obtener KPIs Iniciales
        $ventasHoy = $this->reporteModel->obtenerVentasHoy();
        $ventasMes = $this->reporteModel->obtenerVentasMes();
        $mejorRuta = $this->reporteModel->obtenerMejorRutaMes();

        // 2. Datos para Gráfico (Últimos 7 días)
        $tendencia = $this->reporteModel->obtenerTendenciaSemanal();

        // Formatear datos para ChartJS
        $labels = [];
        $dataChart = [];
        foreach ($tendencia as $t) {
            $labels[] = date('d/m', strtotime($t->fecha));
            $dataChart[] = $t->total;
        }

        // 3. Tabla Detalle (Últimos 30 días default)
        $detalle = $this->reporteModel->obtenerDesgloseFinanciero();

        $data = [
            'titulo' => 'Reporte Financiero',
            'menu_activo' => 'reportes',
            'kpi_hoy' => $ventasHoy,
            'kpi_mes' => $ventasMes,
            'mejor_ruta' => $mejorRuta,
            'chart_labels' => json_encode($labels),
            'chart_data' => json_encode($dataChart),
            'detalle' => $detalle
        ];

        $this->view('reportes/financiero', $data);
    }

    // API para filtrar tabla financiera por fechas
    public function financiero_ajax()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $inicio = $_POST['fecha_inicio'] ?? null;
            $fin = $_POST['fecha_fin'] ?? null;

            $data = $this->reporteModel->obtenerDesgloseFinanciero($inicio, $fin);

            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $data
            ]);
        }
    }
}
