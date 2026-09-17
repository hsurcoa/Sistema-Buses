<?php

namespace App\Http\Controllers;

use App\Models\LibelulaCobro;
use App\Models\Terminal;
use App\Services\ConfiguracionService;
use App\Services\LibelulaService;
use App\Services\RutaService;
use Illuminate\Http\Request;

/**
 * Configuración general (datos de empresa, QR de cobro). Puerto de
 * `legacy/app/controllers/Configuracion.php` (Fase 5; reescrito a Eloquent
 * al cerrar la sesion).
 */
class ConfiguracionController extends Controller
{
    public function __construct(
        private ConfiguracionService $config,
        private LibelulaService $libelula,
        private RutaService $rutas,
    ) {}

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

    /**
     * AppKey de la pasarela Libélula. Solo Administrador (mismo criterio que
     * el QR de cobro): un vendedor no debe poder cambiar a dónde llegan los
     * pagos. Si el campo del AppKey llega vacío se conserva el que ya había
     * guardado (así no hace falta re-pegarlo cada vez que se toca el resto
     * de la config).
     */
    public function guardarLibelula(Request $request)
    {
        if ($request->user()->rol?->nombre !== 'Administrador') {
            return redirect(URLROOT.'/configuracion?msg=libelula_sin_permiso');
        }

        $appkey = trim($request->input('libelula_appkey', ''));
        $datos = [
            'libelula_activo' => $request->boolean('libelula_activo') ? '1' : '0',
        ];
        if ($appkey !== '') {
            $datos['libelula_appkey'] = $appkey;
        }

        $tieneAppkey = $appkey !== '' || ! empty($this->config->obtenerConfiguracion()['libelula_appkey']);
        if ($request->boolean('libelula_activo') && ! $tieneAppkey) {
            $datos['libelula_activo'] = '0';
        }

        $ok = $this->config->guardarConfiguracion($datos);
        $msg = ! $ok ? 'libelula_error' : (($request->boolean('libelula_activo') && ! $tieneAppkey) ? 'libelula_sin_appkey' : 'libelula_guardado');

        return redirect(URLROOT.'/configuracion?msg='.$msg.'#pasarela-libelula');
    }

    /**
     * Conciliación manual: mientras el sistema corra en un servidor sin URL
     * pública, el callback de Libélula no puede llegarle (ver
     * LibelulaWebhookController), así que este botón consulta directamente
     * "CONSULTAR PAGOS" y aplica los que encuentre pendientes acá.
     */
    public function verificarPagosLibelula(Request $request)
    {
        if (! $this->libelula->estaActivo()) {
            return response()->json(['status' => 'error', 'message' => 'La pasarela Libélula no está activa.']);
        }

        $desde = now()->subHours(6)->format('Y-m-d H:i:s');
        $hasta = now()->format('Y-m-d H:i:s');
        $resultado = $this->libelula->consultarPagos($desde, $hasta);

        if (! $resultado['ok']) {
            return response()->json(['status' => 'error', 'message' => $resultado['mensaje']]);
        }

        $identificadoresPagados = collect($resultado['pagos'])->pluck('identificador')->filter()->all();
        $pendientes = LibelulaCobro::where('estado', 'pendiente')->whereIn('identificador_deuda', $identificadoresPagados)->get();

        $aplicados = 0;
        $sinAplicarCaja = 0;
        foreach ($pendientes as $cobro) {
            $resConfirm = $this->rutas->confirmarPagoLibelula($cobro->boleto_id, $cobro->id_transaccion ?? $cobro->identificador_deuda);
            $cobro->update([
                'estado' => $resConfirm['aplicado_caja'] ? 'pagado' : 'pagado_sin_aplicar',
                'fecha_pago' => now(),
            ]);
            $resConfirm['aplicado_caja'] ? $aplicados++ : $sinAplicarCaja++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "Revisados {$pendientes->count()} cobro(s) pendiente(s): {$aplicados} confirmados".
                ($sinAplicarCaja ? ", {$sinAplicarCaja} pagados pero sin caja abierta para aplicarlos (ábrala y reintente)." : '.'),
        ]);
    }

    /** Prender/apagar las alertas de SOAT/ITV por vencer en Registrar Buses. */
    public function guardarAlertasFlota(Request $request)
    {
        $ok = $this->config->guardarConfiguracion([
            'alertas_flota_documentos_activo' => $request->boolean('alertas_flota_documentos_activo') ? '1' : '0',
        ]);

        return redirect(URLROOT.'/configuracion?msg='.($ok ? 'alertas_guardado' : 'alertas_error').'#alertas-flota');
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

            if (! $archivo->isValid()) {
                return redirect(URLROOT.'/configuracion?msg=logo_invalido');
            }

            $extension = strtolower($archivo->getClientOriginalExtension());
            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'svg'], true)) {
                return redirect(URLROOT.'/configuracion?msg=logo_formato');
            }

            $targetDir = public_path('uploads/logos');
            if (! is_dir($targetDir) && ! mkdir($targetDir, 0777, true) && ! is_dir($targetDir)) {
                return redirect(URLROOT.'/configuracion?msg=logo_carpeta');
            }

            try {
                $nombreArchivo = 'logo_'.time().'.'.$extension;
                $archivo->move($targetDir, $nombreArchivo);
                $datos['empresa_logo'] = 'uploads/logos/'.$nombreArchivo;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('ConfiguracionController::guardar - fallo al mover el logo: '.$e->getMessage());

                return redirect(URLROOT.'/configuracion?msg=logo_error');
            }
        }

        if (! $this->config->guardarConfiguracion($datos)) {
            abort(500, 'Algo salió mal');
        }

        return redirect(URLROOT.'/configuracion?msg=guardado');
    }
}
