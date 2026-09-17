<?php

namespace App\Services;

use App\Models\ConfiguracionSistema;
use App\Models\Terminal;
use Illuminate\Support\Facades\DB;

/** Reescritura Eloquent de `legacy/app/models/ConfiguracionModel.php`. */
class ConfiguracionService
{
    public function obtenerConfiguracion(): array
    {
        return ConfiguracionSistema::pluck('valor', 'clave')->all();
    }

    /**
     * Datos del cobro QR para una sucursal: usa el QR propio de la sucursal si lo
     * tiene y, si no, el QR general del dueño. Null si el cobro QR esta
     * desactivado o no hay ninguna imagen cargada.
     */
    public function obtenerPagoQr(?int $sucursalId = null): ?array
    {
        $config = $this->obtenerConfiguracion();
        if (empty($config['pago_qr_activo'])) {
            return null;
        }

        $imagen = $config['pago_qr_imagen'] ?? '';
        $titular = $config['pago_qr_titular'] ?? '';
        $entidad = $config['pago_qr_entidad'] ?? '';
        $origen = 'general';

        if ($sucursalId) {
            $suc = Terminal::find($sucursalId);
            if ($suc && ! empty($suc->pago_qr_imagen)) {
                $imagen = $suc->pago_qr_imagen;
                $titular = $suc->pago_qr_titular ?: $titular;
                $entidad = $suc->pago_qr_entidad ?: $entidad;
                $origen = 'sucursal';
            }
        }

        if ($imagen === '') {
            return null;
        }

        return [
            'imagen_ruta' => $imagen,
            'imagen' => URLROOT.'/'.$imagen,
            'titular' => $titular,
            'entidad' => $entidad,
            'instrucciones' => $config['pago_qr_instrucciones'] ?? '',
            'minutos' => max(3, (int) ($config['pago_qr_minutos'] ?? 15)),
            'origen' => $origen,
        ];
    }

    public function guardarConfiguracion(array $datos): bool
    {
        try {
            DB::transaction(function () use ($datos) {
                foreach ($datos as $clave => $valor) {
                    ConfiguracionSistema::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
                }
            });

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
