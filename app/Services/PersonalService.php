<?php

namespace App\Services;

use App\Models\Personal;
use Illuminate\Support\Facades\DB;

/** Reescritura Eloquent de `legacy/app/models/Personal.php`. */
class PersonalService
{
    public function obtenerPersonal()
    {
        return Personal::orderByDesc('created_at')->get();
    }

    public function agregarPersonal(array $datos): int|false
    {
        $p = Personal::create([
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'tipo_documento' => $datos['tipo_documento'],
            'numero_documento' => $datos['numero_documento'],
            'genero' => $datos['genero'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'celular' => $datos['celular'],
            'email' => $datos['email'],
            'direccion_domicilio' => $datos['direccion_domicilio'],
            'departamento' => $datos['departamento'],
            'provincia' => $datos['provincia'],
            'distrito' => $datos['distrito'],
            'perfil' => $datos['perfil'],
            'foto' => $datos['foto'],
        ]);

        return $p->id ?: false;
    }

    public function actualizarPersonal(array $datos): bool
    {
        return (bool) Personal::where('id', $datos['id'])->update([
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'tipo_documento' => $datos['tipo_documento'],
            'numero_documento' => $datos['numero_documento'],
            'genero' => $datos['genero'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'celular' => $datos['celular'],
            'email' => $datos['email'],
            'direccion_domicilio' => $datos['direccion_domicilio'],
            'departamento' => $datos['departamento'],
            'provincia' => $datos['provincia'],
            'distrito' => $datos['distrito'],
            'perfil' => $datos['perfil'],
            'foto' => $datos['foto'],
        ]);
    }

    public function eliminarPersonal(int $id): bool
    {
        return (bool) Personal::where('id', $id)->delete();
    }

    public function obtenerPersonalPorId(int $id): ?Personal
    {
        return Personal::find($id);
    }

    public function cambiarEstado(int $id, int $estado): bool
    {
        return (bool) Personal::where('id', $id)->update(['estado' => $estado]);
    }

    public function obtenerDepartamentos()
    {
        return DB::table('departamentos')->orderBy('nombre')->get();
    }
}
