<?php
class Vehiculos extends Controller
{

    private $vehiculoModel;

    public function __construct()
    {
        $this->vehiculoModel = $this->model('VehiculoModel');
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            // Limpiar cualquier salida previa para evitar corrupción del JSON
            ob_clean();

            // Establecer headers para respuesta JSON
            header('Content-Type: application/json; charset=utf-8');

            // 1. Recoger datos del formulario
            $datos = [
                'propietario_nombres' => trim($_POST['propietario_nombres'] ?? ''),
                'propietario_apellidos' => trim($_POST['propietario_apellidos'] ?? ''),
                'tarjeta_circulacion' => trim($_POST['tarjeta_circulacion'] ?? ''),
                'placa' => trim($_POST['placa'] ?? ''),
                'tipo_bus_id' => trim($_POST['tipo_bus_id'] ?? ''),  // ⭐ NUEVO
                'clase' => trim($_POST['clase'] ?? ''),
                'marca' => trim($_POST['marca'] ?? ''),
                'anio' => trim($_POST['anio'] ?? ''),
                'modelo' => trim($_POST['modelo'] ?? ''),
                'tipo_combustible' => trim($_POST['tipo_combustible'] ?? ''),
                'carroceria' => trim($_POST['carroceria'] ?? ''),
                'ejes' => trim($_POST['ejes'] ?? ''),
                'color' => trim($_POST['color'] ?? ''),
                'nro_motor' => trim($_POST['nro_motor'] ?? ''),
                'cilindros' => trim($_POST['cilindros'] ?? ''),
                'nro_serie' => trim($_POST['nro_serie'] ?? ''),
                'ruedas' => trim($_POST['ruedas'] ?? ''),
                'peso_seco' => trim($_POST['peso_seco'] ?? ''),
                'peso_bruto' => trim($_POST['peso_bruto'] ?? ''),
                'longitud' => trim($_POST['longitud'] ?? ''),
                'altura' => trim($_POST['altura'] ?? ''),
                'ancho' => trim($_POST['ancho'] ?? ''),
                'pasajeros' => trim($_POST['pasajeros'] ?? ''),
                'asientos' => trim($_POST['asientos'] ?? ''),
                'tipo_servicio' => trim($_POST['tipo_servicio'] ?? '')
            ];

            // 2. Validación Básica
            if (empty($datos['placa']) || empty($datos['tarjeta_circulacion']) || empty($datos['tipo_bus_id'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Los campos Placa, Tarjeta de Circulación y Tipo de Bus son obligatorios.'
                ]);
                exit;
            }

            // 3. Intentar guardar en la base de datos
            try {
                if ($this->vehiculoModel->registrarVehiculo($datos)) {
                    // Éxito
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'El bus ha sido registrado correctamente en el sistema.',
                        'placa' => $datos['placa']
                    ]);
                } else {
                    // Fallo genérico
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Ocurrió un error al registrar el bus. Por favor, intente nuevamente.'
                    ]);
                }
            } catch (Exception $e) {
                // Capturar error de duplicados (ej: SQLSTATE[23000])
                $errorMessage = 'Error al registrar el bus.';

                // Detectar si es error de duplicado
                if (strpos($e->getMessage(), '23000') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
                    $errorMessage = 'La placa ingresada ya se encuentra registrada en el sistema.';
                }

                echo json_encode([
                    'status' => 'error',
                    'message' => $errorMessage
                ]);
            }
            exit;
        } else {
            // Si intentan entrar por GET a guardar
            header('Location: ' . URLROOT . '/admin/registrar_buses');
        }
    }

    public function generarPDF()
    {
        // Verificar si existe el autoloader de Composer
        $composerAutoload = APPROOT . '/../vendor/autoload.php';

        if (!file_exists($composerAutoload)) {
            // Fallback amigable si no se ha instalado dompdf
            die('
                <div style="font-family: sans-serif; padding: 20px; border: 1px solid #f5c6cb; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;">
                    <h3>¡Error de Dependencia!</h3>
                    <p>La librería <strong>DomPDF</strong> no está instalada en el sistema.</p>
                    <p>Por favor, ejecuta el siguiente comando en la raíz del proyecto para instalarla:</p>
                    <code style="background: #eee; padding: 5px; border-radius: 3px; display: block; margin: 10px 0;">composer require dompdf/dompdf</code>
                </div>
            ');
        }

        require_once $composerAutoload;

        $vehiculos = $this->vehiculoModel->obtenerVehiculos();

        $data = [
            'vehiculos' => $vehiculos,
            'fecha_generacion' => date('d/m/Y H:i:s')
        ];

        // 1. Renderizar la vista a una variable
        ob_start();
        // Nota: Asegúrate de que este archivo exista en la ruta correcta
        require APPROOT . '/views/admin/pdf_buses.php';
        $html = ob_get_clean();

        // 2. Instanciar DomPDF
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);

        // 3. Configurar Papel (Carta Horizontal)
        $dompdf->setPaper('letter', 'landscape');

        // 4. Renderizar PDF
        $dompdf->render();

        // 5. Enviar al navegador
        $dompdf->stream('reporte_flota_' . date('Ymd_His') . '.pdf', ["Attachment" => true]);
    }

    /**
     * Método para obtener los datos de un bus específico (AJAX)
     */
    public function obtener_bus()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Limpiar cualquier salida previa
            ob_clean();

            // Establecer headers para respuesta JSON
            header('Content-Type: application/json; charset=utf-8');

            // Obtener el ID del bus
            $id = isset($_POST['id']) ? trim($_POST['id']) : '';

            // Validar que se recibió el ID
            if (empty($id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se proporcionó el ID del bus.'
                ]);
                exit;
            }

            // Obtener los datos del bus desde el modelo
            $bus = $this->vehiculoModel->obtenerBusPorId($id);

            if ($bus) {
                echo json_encode([
                    'status' => 'success',
                    'bus' => $bus
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se encontró el bus con el ID proporcionado.'
                ]);
            }
            exit;
        } else {
            // Si intentan entrar por GET
            header('Location: ' . URLROOT . '/admin/registrar_buses');
        }
    }

    /**
     * Método para actualizar los datos de un bus (AJAX)
     */
    public function editar_bus()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Limpiar cualquier salida previa
            ob_clean();

            // Establecer headers para respuesta JSON
            header('Content-Type: application/json; charset=utf-8');

            // Recoger datos del formulario
            $datos = [
                'id' => trim($_POST['id'] ?? ''),
                'propietario_nombres' => trim($_POST['propietario_nombres'] ?? ''),
                'propietario_apellidos' => trim($_POST['propietario_apellidos'] ?? ''),
                'tarjeta_circulacion' => trim($_POST['tarjeta_circulacion'] ?? ''),
                'placa' => trim($_POST['placa'] ?? ''),
                'clase' => trim($_POST['clase'] ?? ''),
                'marca' => trim($_POST['marca'] ?? ''),
                'anio' => trim($_POST['anio'] ?? ''),
                'modelo' => trim($_POST['modelo'] ?? ''),
                'tipo_combustible' => trim($_POST['tipo_combustible'] ?? ''),
                'carroceria' => trim($_POST['carroceria'] ?? ''),
                'ejes' => trim($_POST['ejes'] ?? ''),
                'color' => trim($_POST['color'] ?? ''),
                'nro_motor' => trim($_POST['nro_motor'] ?? ''),
                'cilindros' => trim($_POST['cilindros'] ?? ''),
                'nro_serie' => trim($_POST['nro_serie'] ?? ''),
                'ruedas' => trim($_POST['ruedas'] ?? ''),
                'peso_seco' => trim($_POST['peso_seco'] ?? ''),
                'peso_bruto' => trim($_POST['peso_bruto'] ?? ''),
                'longitud' => trim($_POST['longitud'] ?? ''),
                'altura' => trim($_POST['altura'] ?? ''),
                'ancho' => trim($_POST['ancho'] ?? ''),
                'pasajeros' => trim($_POST['pasajeros'] ?? ''),
                'asientos' => trim($_POST['asientos'] ?? ''),
                'tipo_servicio' => trim($_POST['tipo_servicio'] ?? '')
            ];

            // Validación básica
            if (empty($datos['id']) || empty($datos['placa']) || empty($datos['tarjeta_circulacion'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Los campos ID, Placa y Tarjeta de Circulación son obligatorios.'
                ]);
                exit;
            }

            // Intentar actualizar en la base de datos
            try {
                if ($this->vehiculoModel->actualizarBus($datos)) {
                    // Éxito
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'El bus ha sido actualizado correctamente.',
                        'placa' => $datos['placa']
                    ]);
                } else {
                    // Fallo genérico
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Ocurrió un error al actualizar el bus. Por favor, intente nuevamente.'
                    ]);
                }
            } catch (Exception $e) {
                // Capturar errores
                $errorMessage = 'Error al actualizar el bus.';

                // Detectar si es error de duplicado (por si cambiaron la placa a una existente)
                if (strpos($e->getMessage(), '23000') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
                    $errorMessage = 'La placa ingresada ya se encuentra registrada en el sistema.';
                }

                echo json_encode([
                    'status' => 'error',
                    'message' => $errorMessage
                ]);
            }
            exit;
        } else {
            // Si intentan entrar por GET
            header('Location: ' . URLROOT . '/admin/registrar_buses');
        }
    }

    /**
     * Método para eliminar (desactivar) un bus (AJAX)
     */
    public function eliminar_bus()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Limpiar cualquier salida previa
            ob_clean();

            // Establecer headers para respuesta JSON
            header('Content-Type: application/json; charset=utf-8');

            // Obtener el ID del bus
            $id = isset($_POST['id']) ? trim($_POST['id']) : '';

            // Validar que se recibió el ID
            if (empty($id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se proporcionó el ID del bus.'
                ]);
                exit;
            }

            // Intentar eliminar (desactivar) el bus
            try {
                if ($this->vehiculoModel->eliminarBus($id)) {
                    // Éxito
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'El bus ha sido eliminado correctamente del sistema.'
                    ]);
                } else {
                    // Fallo genérico
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No se pudo eliminar el bus. Por favor, intente nuevamente.'
                    ]);
                }
            } catch (Exception $e) {
                // Capturar errores
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al eliminar el bus: ' . $e->getMessage()
                ]);
            }
            exit;
        } else {
            // Si intentan entrar por GET
            header('Location: ' . URLROOT . '/admin/registrar_buses');
        }
    }
}
