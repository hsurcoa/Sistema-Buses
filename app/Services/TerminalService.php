<?php

namespace App\Services;

use App\Models\Terminal;
use Illuminate\Support\Facades\DB;

/** Reescritura Eloquent de `legacy/app/models/TerminalModel.php`. */
class TerminalService
{
    public function listarTerminales()
    {
        return Terminal::orderBy('nombre_sede')->get();
    }

    public function obtenerTerminal(int $id): ?Terminal
    {
        return Terminal::find($id);
    }

    public function cambiarEstado(int $id, int $estado): bool
    {
        return (bool) Terminal::where('id', $id)->update(['estado' => $estado]);
    }

    /** Sucursales con cuantos usuarios, cajas, boletos y encomiendas tienen. */
    public function listarSucursales()
    {
        return DB::table('terminales as t')
            ->select('t.*',
                DB::raw("(SELECT COUNT(*) FROM usuarios u WHERE u.sucursal_id = t.id AND u.estado = 'activo') AS usuarios"),
                DB::raw('(SELECT COUNT(*) FROM cajas_sesiones c WHERE c.sucursal_id = t.id) AS cajas'),
                DB::raw('(SELECT COUNT(*) FROM boletos b WHERE b.sucursal_id = t.id) AS boletos'),
                DB::raw('(SELECT COUNT(*) FROM encomiendas e WHERE e.sucursal_origen_id = t.id OR e.sucursal_destino_id = t.id) AS encomiendas'),
                DB::raw('(SELECT COUNT(*) FROM viajes v WHERE v.terminal_origen_id = t.id OR v.terminal_destino_id = t.id) AS viajes'))
            ->orderByDesc('t.estado')->orderBy('t.nombre_sede')
            ->get();
    }

    public function prefijoEnUso(string $prefijo, int $excluirId = 0): bool
    {
        return Terminal::where('prefijo_boleto', $prefijo)->where('id', '<>', $excluirId)->exists();
    }

    public function guardarSucursal(array $d): bool
    {
        $campos = [
            'nombre_sede' => $d['nombre_sede'],
            'direccion' => $d['direccion'],
            'numero_oficina' => $d['numero_oficina'],
            'telefono' => $d['telefono'] !== '' ? $d['telefono'] : null,
            'prefijo_boleto' => $d['prefijo_boleto'],
            'pago_qr_titular' => $d['pago_qr_titular'] !== '' ? $d['pago_qr_titular'] : null,
            'pago_qr_entidad' => $d['pago_qr_entidad'] !== '' ? $d['pago_qr_entidad'] : null,
        ];
        if (array_key_exists('pago_qr_imagen', $d)) {
            $campos['pago_qr_imagen'] = $d['pago_qr_imagen'];
        }

        if (! empty($d['id'])) {
            return (bool) Terminal::where('id', $d['id'])->update($campos);
        }

        return (bool) Terminal::create($campos + ['estado' => 1]);
    }

    /** Solo se borran sucursales sin ningun historial; con historial se desactivan. */
    public function eliminarSucursalSinHistorial(int $id): bool
    {
        $tieneHistorial = DB::table('usuarios')->where('sucursal_id', $id)->exists()
            || DB::table('cajas_sesiones')->where('sucursal_id', $id)->exists()
            || DB::table('boletos')->where('sucursal_id', $id)->exists()
            || DB::table('encomiendas')->where('sucursal_origen_id', $id)->orWhere('sucursal_destino_id', $id)->exists()
            || DB::table('viajes')->where('terminal_origen_id', $id)->orWhere('terminal_destino_id', $id)->exists()
            || DB::table('series_boletos')->where('sede_id', $id)->exists();

        if ($tieneHistorial) {
            return false;
        }

        return (bool) Terminal::where('id', $id)->delete();
    }
}
