<?php

namespace App\Services;

use App\Models\SerieBoleto;
use Illuminate\Support\Facades\DB;

/** Reescritura Eloquent de `legacy/app/models/SerieBoletoModel.php`. */
class SerieBoletoService
{
    public function obtenerVendedores()
    {
        return DB::table('personal')->where('perfil', 'Vendedor')->where('estado', 1)
            ->select('id', DB::raw("CONCAT(nombres, ' ', apellidos) as nombre_completo"))
            ->get();
    }

    public function obtenerSedes()
    {
        return DB::table('terminales')->where('estado', 1)->select('id', 'nombre_sede')->get();
    }

    public function listarSeries()
    {
        return DB::table('series_boletos as s')
            ->join('personal as p', 's.usuario_id', '=', 'p.id')
            ->join('terminales as t', 's.sede_id', '=', 't.id')
            ->select('s.id', 't.nombre_sede', DB::raw("CONCAT(p.nombres, ' ', p.apellidos) as vendedor_nombre"), 's.numero_serie', 's.estado', 's.usuario_id', 's.sede_id')
            ->orderByDesc('s.id')
            ->get();
    }

    public function obtenerSerie(int $id): ?SerieBoleto
    {
        return SerieBoleto::find($id);
    }

    public function registrarSerie(array $datos): bool
    {
        $campos = ['usuario_id' => $datos['usuario_id'], 'sede_id' => $datos['sede_id'], 'numero_serie' => $datos['numero_serie']];

        if (! empty($datos['id'])) {
            return (bool) SerieBoleto::where('id', $datos['id'])->update($campos);
        }

        return (bool) SerieBoleto::create($campos + ['estado' => 1]);
    }

    public function cambiarEstado(int $id, int $estado): bool
    {
        return (bool) SerieBoleto::where('id', $id)->update(['estado' => $estado]);
    }
}
