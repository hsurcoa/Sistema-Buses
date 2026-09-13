<?php
class Dashboard extends Controller
{
    private $dashboardModel;
    private $cajaModel;

    public function __construct()
    {
        $this->dashboardModel = $this->model('DashboardModel');
        $this->cajaModel = $this->model('CajaModel');
    }

    public function index()
    {
        // Obtener datos en tiempo real
        $proximasSalidas = $this->dashboardModel->obtenerProximasSalidas(10);
        $estadisticas = $this->dashboardModel->obtenerEstadisticasDelDia();
        $alertas = $this->dashboardModel->obtenerAlertas();
        $encomiendas = $this->dashboardModel->obtenerEncomiendas();
        $ventasPorHora = $this->dashboardModel->obtenerVentasPorHora();
        $topDestinos = $this->dashboardModel->obtenerTopDestinos(5);

        $data = [
            'title' => 'Dashboard - Venta de Pasajes',
            'proximas_salidas' => $proximasSalidas,
            'stats' => $estadisticas,
            'alertas' => $alertas,
            'encomiendas_pendientes' => $encomiendas,
            'ventas_hora' => $ventasPorHora,
            'top_destinos' => $topDestinos
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('dashboard/index', $data);
        $this->view('layouts/footer', $data);
    }

    // AJAX: Obtener datos actualizados para auto-refresh
    public function obtener_datos_ajax()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');

            $proximasSalidas = $this->dashboardModel->obtenerProximasSalidas(10);
            $estadisticas = $this->dashboardModel->obtenerEstadisticasDelDia();
            $alertas = $this->dashboardModel->obtenerAlertas();
            $encomiendas = $this->dashboardModel->obtenerEncomiendas();
            $ventasPorHora = $this->dashboardModel->obtenerVentasPorHora();
            $topDestinos = $this->dashboardModel->obtenerTopDestinos(5);

            echo json_encode([
                'status' => 'success',
                'proximas_salidas' => $proximasSalidas,
                'stats' => $estadisticas,
                'alertas' => $alertas,
                'encomiendas_pendientes' => $encomiendas,
                'ventas_hora' => $ventasPorHora,
                'top_destinos' => $topDestinos
            ]);
            exit;
        }
    }
}
