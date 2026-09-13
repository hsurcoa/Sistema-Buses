<?php
class Configuracion extends Controller
{
    private $configModel;

    public function __construct()
    {
        // Verificar sesión usando SessionManager
        SessionManager::getInstance()->requireAuth();
        $this->configModel = $this->model('ConfiguracionModel');
    }

    public function index()
    {
        $datos = [
            'config' => $this->configModel->obtenerConfiguracion()
        ];

        $this->view('configuracion/index', $datos);
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos = [
                'empresa_nombre' => trim($_POST['empresa_nombre']),
                'empresa_slogan' => trim($_POST['empresa_slogan'])
            ];

            // Manejo de subida de Logo
            if (!empty($_FILES['empresa_logo']['name'])) {
                $target_dir = "uploads/logos/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES["empresa_logo"]["name"], PATHINFO_EXTENSION));
                $new_filename = "logo_" . time() . "." . $file_extension;
                $target_file = $target_dir . $new_filename;

                $allowed = ['jpg', 'jpeg', 'png', 'svg'];

                if (in_array($file_extension, $allowed)) {
                    if (move_uploaded_file($_FILES["empresa_logo"]["tmp_name"], $target_file)) {
                        // Guardar ruta relativa
                        $datos['empresa_logo'] = $target_file;
                    }
                }
            }

            if ($this->configModel->guardarConfiguracion($datos)) {
                // Redirigir con éxito y flag para SweetAlert2
                header('Location: ' . URLROOT . '/configuracion?msg=guardado');
                exit;
            } else {
                die('Algo salió mal');
            }
        } else {
            header('Location: ' . URLROOT . '/configuracion/index');
            exit;
        }
    }
}
