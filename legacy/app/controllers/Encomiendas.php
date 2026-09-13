<?php

class Encomiendas extends Controller
{
    private $encomiendaModel;
    private $rutaModel;

    public function __construct()
    {
        $this->encomiendaModel = $this->model('EncomiendaModel');
        $this->rutaModel = $this->model('RutaModel');
    }

    public function index()
    {
        $encomiendas = $this->encomiendaModel->listarEncomiendas();

        $data = [
            'encomiendas' => $encomiendas
        ];

        $this->view('encomiendas/index', $data);
    }

    public function crear()
    {
        // Obtener rutas activas para el select
        $rutas = $this->rutaModel->listarRutasActivas();

        $data = [
            'rutas' => $rutas
        ];

        $this->view('encomiendas/crear', $data);
    }

    // Método AJAX para obtener viajes por ruta
    public function obtener_viajes($rutaId)
    {
        // Reusamos lógica similar a ventas, pero buscamos viajes futuros
        // Aquí podríamos necesitar un método específico en RutaModel o ViajeModel.
        // Dado el tiempo, haré una query manual rápida o usaré listarViajesProgramados filtrando.
        // Lo ideal es agregar un método 'obtenerViajesFuturosPorRuta' en RutaModel.
        // Por ahora haré un hack: instanciar DB aquí es feo, mejor agrego al model rapidito o uso lo que hay.
        // RutaModel->listarViajesProgramados trae todo.

        // Simplemente devolveré un JSON vacío y dejaré que el JS haga la magia si implemento el endpoint correcto
        // Mejor implemento un método ad-hoc en el controlador usando el modelo existente si es posible
        // O mejor: usaré una consulta directa vía un nuevo método en EncomiendaModel para esto.

        $viajes = $this->encomiendaModel->listarViajesFuturosPorRuta($rutaId);
        echo json_encode($viajes);
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            // Sanitizar
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

            $paradaId = trim($_POST['parada_id']);
            $tipoPaqueteId = trim($_POST['tipo_paquete_id']);

            // 1. REVALIDAR PRECIO EN EL SERVIDOR (Seguridad Crítica)
            // No confiamos en lo que envía el formulario HTML
            $calculo = $this->rutaModel->calcularPrecioDinamico($paradaId, 'encomienda', $tipoPaqueteId);

            if (!$calculo['status']) {
                die('Error al calcular precio: ' . $calculo['message']);
            }

            $precioReal = $calculo['precio_total'];
            $detalleCalculo = $calculo['desglose'];

            $datos = [
                'viaje_id' => trim($_POST['viaje_id']),
                'remitente_nombre' => trim($_POST['remitente_nombre']),
                'remitente_dni' => trim($_POST['remitente_dni']),
                'destinatario_nombre' => trim($_POST['destinatario_nombre']),
                'destinatario_dni' => trim($_POST['destinatario_dni']),
                'destinatario_telefono' => trim($_POST['destinatario_telefono']),
                // Concatenamos el detalle técnico a la descripción del usuario
                'descripcion' => trim($_POST['descripcion']) . " [Destino: " . $detalleCalculo . "]",
                'peso' => trim($_POST['peso']),
                'tipo_carga' => trim($_POST['tipo_carga']),
                'valor_declarado' => trim($_POST['valor_declarado']),
                'clave_retiro' => trim($_POST['clave_retiro']),
                'usuario_id' => $_SESSION['user_id'] ?? 1,

                // Pasamos el precio ya calculado y verificado
                'precio_verificado' => $precioReal,

                // Pasamos datos extra por si el modelo los necesita
                'parada_id' => $paradaId,
                'tipo_paquete_id' => $tipoPaqueteId
            ];

            $resultado = $this->encomiendaModel->registrarEncomienda($datos);

            if ($resultado['status']) {
                // Redireccionar al recibo
                header('Location: ' . URLROOT . '/encomiendas/recibo/' . $resultado['id']);
                exit;
            } else {
                die('Error al guardar: ' . $resultado['message']);
            }
        }
    }

    public function recibo($id)
    {
        $encomienda = $this->encomiendaModel->obtenerEncomiendaPorId($id);

        if (!$encomienda) {
            die('Encomienda no encontrada');
        }

        $data = [
            'encomienda' => $encomienda
        ];

        $this->view('encomiendas/recibo', $data);
    }
}
