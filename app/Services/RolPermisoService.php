<?php

namespace App\Services;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;

/** Reescritura Eloquent de `legacy/app/models/RolPermiso.php`. */
class RolPermisoService
{
    public function obtenerRoles()
    {
        return Rol::where('activo', 1)->orderBy('nombre')->get();
    }

    public function obtenerRolPorId(int $id): ?Rol
    {
        return Rol::find($id);
    }

    public function obtenerPermisosAgrupados(): array
    {
        $orden = ['Dashboard', 'Procesos', 'Reportes', 'Registros', 'Administrador'];
        $permisos = Permiso::where('activo', 1)
            ->orderByRaw('FIELD(grupo, "'.implode('","', $orden).'")')
            ->orderBy('orden')
            ->get();

        $agrupados = [];
        foreach ($permisos as $permiso) {
            $agrupados[$permiso->grupo][] = $permiso;
        }

        return $agrupados;
    }

    public function obtenerPermisosPorRol(int $rolId): array
    {
        return DB::table('permisos as p')
            ->join('rol_permiso as rp', 'p.id', '=', 'rp.permiso_id')
            ->where('rp.rol_id', $rolId)->where('p.activo', 1)
            ->pluck('p.id')->all();
    }

    public function actualizarPermisosRol(int $rolId, array $permisosIds): bool
    {
        try {
            DB::transaction(function () use ($rolId, $permisosIds) {
                DB::table('rol_permiso')->where('rol_id', $rolId)->delete();
                if ($permisosIds) {
                    DB::table('rol_permiso')->insert(array_map(
                        fn ($permisoId) => ['rol_id' => $rolId, 'permiso_id' => $permisoId],
                        $permisosIds
                    ));
                }
            });

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('RolPermisoService::actualizarPermisosRol: '.$e->getMessage());

            return false;
        }
    }

    public function crearRol(array $datos): int|false
    {
        $rol = Rol::create(['nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion'] ?? '', 'activo' => $datos['activo'] ?? 1]);

        return $rol->id ?: false;
    }

    public function actualizarRol(int $id, array $datos): bool
    {
        return (bool) Rol::where('id', $id)->update(['nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion'] ?? '', 'activo' => $datos['activo'] ?? 1]);
    }

    public function eliminarRol(int $id): bool
    {
        return (bool) Rol::where('id', $id)->update(['activo' => 0]);
    }

    /**
     * NOTA: el original (`RolPermiso::tienePermiso()`) filtra `u.activo = 1`,
     * columna que no existe en `usuarios` (la columna real es `estado`,
     * enum activo/inactivo) — ya estaba roto en el legacy (nunca se llama
     * desde ningun controlador migrado). Se preserva la misma consulta
     * rota en vez de "arreglarla" en silencio, igual que con otros
     * metodos muertos detectados en esta migracion (ver AsientoModel).
     */
    public function tienePermiso(int $usuarioId, string $clavePermiso): bool
    {
        return DB::table('usuarios as u')
            ->join('roles as r', 'u.rol_id', '=', 'r.id')
            ->join('rol_permiso as rp', 'r.id', '=', 'rp.rol_id')
            ->join('permisos as p', 'rp.permiso_id', '=', 'p.id')
            ->where('u.id', $usuarioId)->where('p.clave', $clavePermiso)
            ->where('u.activo', 1)->where('r.activo', 1)->where('p.activo', 1)
            ->exists();
    }

    public function obtenerResumenRoles()
    {
        return DB::table('v_resumen_roles')->get();
    }
}
