<?php
class Series extends Controller
{
    private $serieBoletoModel;

    public function __construct()
    {
        $this->serieBoletoModel = $this->model('SerieBoletoModel');
    }

    public function index()
    {
        // Carga data usando el modelo
        $vendedores = $this->serieBoletoModel->obtenerVendedores();
        $sedes = $this->serieBoletoModel->obtenerSedes();
        $lista_series = $this->serieBoletoModel->listarSeries();

        $data = [
            'title' => 'Registrar Series de Boletos',
            'vendedores' => $vendedores,
            'sedes' => $sedes,
            'lista_series' => $lista_series
        ];

        // Pasa todo a la vista
        // Using the existing view structure but rendering through this controller
        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('admin/registrar_serie_boletos', $data); // We reuse the view file
        $this->view('layouts/footer', $data);
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Recibe vendedor_id, sede_id, nro_serie
            $id = $_POST['id'] ?? '';
            $vendedor_id = $_POST['vendedor_id'] ?? ''; // Mapped from form name="vendedor_id" or "id_vendedor"
            $sede_id = $_POST['sede_id'] ?? '';
            $nro_serie = trim($_POST['nro_serie'] ?? '');

            // Valida que no estén vacíos
            if (empty($vendedor_id) || empty($sede_id) || empty($nro_serie)) {
                echo "<script>alert('Todos los campos son obligatorios'); window.location.href='" . URLROOT . "/series';</script>";
                return;
            }

            $datos = [
                'id' => $id,
                'usuario_id' => $vendedor_id,
                'sede_id' => $sede_id,
                'numero_serie' => $nro_serie
            ];

            if ($this->serieBoletoModel->registrarSerie($datos)) {
                echo "<script>alert('Operación exitosa'); window.location.href='" . URLROOT . "/series';</script>";
            } else {
                echo "<script>alert('Error al guardar'); window.location.href='" . URLROOT . "/series';</script>";
            }
        } else {
            header('Location: ' . URLROOT . '/series');
        }
    }

    public function cambiar_estado($id)
    {
        $serie = $this->serieBoletoModel->obtenerSerie($id);
        if ($serie) {
            $nuevo_estado = $serie->estado ? 0 : 1;
            $this->serieBoletoModel->cambiarEstado($id, $nuevo_estado);
        }
        header('Location: ' . URLROOT . '/series');
    }
}
