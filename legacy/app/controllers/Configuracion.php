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

    /**
     * QR de cobro del dueño. Solo el rol Administrador puede cambiarlo: si un
     * vendedor pudiera reemplazarlo por su propio QR, desviaria los pagos.
     */
    public function guardar_qr()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/configuracion');
            exit;
        }

        $session = SessionManager::getInstance();
        if (!$session->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: ' . URLROOT . '/configuracion?msg=csrf');
            exit;
        }
        if (($_SESSION['rol'] ?? '') !== 'Administrador') {
            header('Location: ' . URLROOT . '/configuracion?msg=qr_sin_permiso');
            exit;
        }

        $actual = $this->configModel->obtenerConfiguracion();
        $datos = [
            'pago_qr_titular' => mb_substr(trim($_POST['pago_qr_titular'] ?? ''), 0, 120),
            'pago_qr_entidad' => mb_substr(trim($_POST['pago_qr_entidad'] ?? ''), 0, 120),
            'pago_qr_instrucciones' => mb_substr(trim($_POST['pago_qr_instrucciones'] ?? ''), 0, 300),
            'pago_qr_minutos' => (string) min(60, max(3, (int) ($_POST['pago_qr_minutos'] ?? 15))),
        ];

        if (!empty($_FILES['pago_qr_imagen']['name'])) {
            $archivo = $_FILES['pago_qr_imagen'];
            $info = @getimagesize($archivo['tmp_name']);
            $tipos = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];

            if ($archivo['error'] !== UPLOAD_ERR_OK || !$info || !isset($tipos[$info[2]])) {
                header('Location: ' . URLROOT . '/configuracion?msg=qr_formato');
                exit;
            }
            if ($archivo['size'] > 2 * 1024 * 1024) {
                header('Location: ' . URLROOT . '/configuracion?msg=qr_tamano');
                exit;
            }

            $dir = 'uploads/pagos/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $destino = $dir . 'qr_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $tipos[$info[2]];
            if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
                header('Location: ' . URLROOT . '/configuracion?msg=qr_error');
                exit;
            }
            $datos['pago_qr_imagen'] = $destino;
        }

        $tieneImagen = !empty($datos['pago_qr_imagen']) || !empty($actual['pago_qr_imagen']);
        $datos['pago_qr_activo'] = (!empty($_POST['pago_qr_activo']) && $tieneImagen) ? '1' : '0';

        $ok = $this->configModel->guardarConfiguracion($datos);
        $msg = !$ok ? 'qr_error' : ((!empty($_POST['pago_qr_activo']) && !$tieneImagen) ? 'qr_sin_imagen' : 'qr_guardado');
        header('Location: ' . URLROOT . '/configuracion?msg=' . $msg);
        exit;
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
