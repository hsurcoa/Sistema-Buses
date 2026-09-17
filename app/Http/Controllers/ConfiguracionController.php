<?php

namespace App\Http\Controllers;

use App\Models\Terminal;
use App\Services\ConfiguracionService;
use Illuminate\Http\Request;

/**
 * Configuración general (datos de empresa, QR de cobro). Puerto de
 * `legacy/app/controllers/Configuracion.php` (Fase 5; reescrito a Eloquent
 * al cerrar la sesion).
 */
class ConfiguracionController extends Controller
{
    public function __construct(private ConfiguracionService $config) {}

    public function index()
    {
        return view('configuracion.index', ['data' => [
            'config' => $this->config->obtenerConfiguracion(),
        ]]);
    }

    /** QR de cobro del dueño. Solo Administrador: evita que un vendedor desvíe pagos a su propio QR. */
    public function guardarQr(Request $request)
    {
        if ($request->user()->rol?->nombre !== 'Administrador') {
            return redirect(URLROOT.'/configuracion?msg=qr_sin_permiso');
        }

        $actual = $this->config->obtenerConfiguracion();
        $datos = [
            'pago_qr_titular' => mb_substr(trim($request->input('pago_qr_titular', '')), 0, 120),
            'pago_qr_entidad' => mb_substr(trim($request->input('pago_qr_entidad', '')), 0, 120),
            'pago_qr_instrucciones' => mb_substr(trim($request->input('pago_qr_instrucciones', '')), 0, 300),
            'pago_qr_minutos' => (string) min(60, max(3, (int) $request->input('pago_qr_minutos', 15))),
        ];

        if ($request->hasFile('pago_qr_imagen')) {
            $archivo = $request->file('pago_qr_imagen');
            $info = $archivo->isValid() ? @getimagesize($archivo->getRealPath()) : false;
            $tipos = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];

            if (! $archivo->isValid() || ! $info || ! isset($tipos[$info[2]])) {
                return redirect(URLROOT.'/configuracion?msg=qr_formato');
            }
            if ($archivo->getSize() > 2 * 1024 * 1024) {
                return redirect(URLROOT.'/configuracion?msg=qr_tamano');
            }

            $dir = public_path('uploads/pagos');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $nombreArchivo = 'qr_'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$tipos[$info[2]];
            if (! $archivo->move($dir, $nombreArchivo)) {
                return redirect(URLROOT.'/configuracion?msg=qr_error');
            }
            $datos['pago_qr_imagen'] = 'uploads/pagos/'.$nombreArchivo;
        }

        $qrSucursales = Terminal::whereNotNull('pago_qr_imagen')->where('pago_qr_imagen', '<>', '')->count();
        $tieneImagen = ! empty($datos['pago_qr_imagen']) || ! empty($actual['pago_qr_imagen']) || $qrSucursales > 0;
        $datos['pago_qr_activo'] = ($request->boolean('pago_qr_activo') && $tieneImagen) ? '1' : '0';

        $ok = $this->config->guardarConfiguracion($datos);
        $msg = ! $ok ? 'qr_error' : (($request->boolean('pago_qr_activo') && ! $tieneImagen) ? 'qr_sin_imagen' : 'qr_guardado');

        return redirect(URLROOT.'/configuracion?msg='.$msg);
    }

    public function guardar(Request $request)
    {
        $datos = [
            'empresa_nombre' => trim($request->input('empresa_nombre', '')),
            'empresa_slogan' => trim($request->input('empresa_slogan', '')),
            'empresa_nit' => mb_substr(trim($request->input('empresa_nit', '')), 0, 30),
        ];

        if ($request->hasFile('empresa_logo')) {
            $archivo = $request->file('empresa_logo');
            $targetDir = public_path('uploads/logos');
            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $extension = strtolower($archivo->getClientOriginalExtension());
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'svg'], true)) {
                $nombreArchivo = 'logo_'.time().'.'.$extension;
                if ($archivo->move($targetDir, $nombreArchivo)) {
                    $datos['empresa_logo'] = 'uploads/logos/'.$nombreArchivo;
                }
            }
        }

        if (! $this->config->guardarConfiguracion($datos)) {
            abort(500, 'Algo salió mal');
        }

        return redirect(URLROOT.'/configuracion?msg=guardado');
    }
}
