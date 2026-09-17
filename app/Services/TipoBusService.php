<?php

namespace App\Services;

use App\Models\TipoBus;
use Illuminate\Support\Facades\DB;

/** Reescritura Eloquent de `legacy/app/models/TipoBusModel.php`. */
class TipoBusService
{
    public function listarTiposBuses()
    {
        return TipoBus::where('estado', 1)->orderBy('nombre')->get();
    }

    /** Incluye tipos inactivos que algun bus real sigue usando (para no perderlos del mapa de asientos). */
    public function listarTiposParaFlota()
    {
        return TipoBus::where('estado', 1)
            ->orWhereIn('id', DB::table('vehiculos')->whereNotNull('tipo_bus_id')->distinct()->pluck('tipo_bus_id'))
            ->orderBy('nombre')
            ->get();
    }

    public function obtenerTipoBus(int $id): ?TipoBus
    {
        return TipoBus::find($id);
    }

    public function agregarTipoBus(array $data): bool
    {
        return (bool) TipoBus::create([
            'nombre' => $data['nombre'],
            'capacidad' => $data['capacidad'],
            'pisos' => $data['pisos'],
            'configuracion_asientos' => $data['configuracion_asientos'],
            'estado' => 1,
        ]);
    }

    public function actualizarTipoBus(array $data): bool
    {
        return (bool) TipoBus::where('id', $data['id'])->update([
            'nombre' => $data['nombre'],
            'capacidad' => $data['capacidad'],
            'pisos' => $data['pisos'],
            'configuracion_asientos' => $data['configuracion_asientos'],
        ]);
    }

    public function cambiarEstado(int $id, int $estado): bool
    {
        return (bool) TipoBus::where('id', $id)->update(['estado' => $estado]);
    }

    public function eliminarTipoBus(int $id): bool
    {
        return (bool) TipoBus::where('id', $id)->update(['estado' => 0]);
    }
}
