<?php

namespace App\Services;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sincroniza el RBAC legacy (roles / permisos / rol_permiso, fuente de verdad
 * mientras el modulo Admin siga en legacy) hacia las tablas spatie_* de
 * spatie/laravel-permission. Fase 1, Tarea 9.
 *
 * Semantica replicada (la de RolPermiso::tienePermiso() del legacy): un
 * usuario tiene un permiso si su usuario esta activo, su rol esta activo, el
 * permiso esta activo y el par rol/permiso existe en rol_permiso. Se mantiene
 * "un rol por usuario" (usuarios.rol_id) aunque spatie admita varios.
 *
 * Es idempotente: se puede re-ejecutar cuantas veces sea necesario.
 */
class LegacyRbacSync
{
    private const GUARD = 'web';

    /** Sincroniza todo: permisos, roles, permisos por rol y rol de cada usuario. */
    public function syncAll(): array
    {
        return DB::transaction(function () {
            $permisos = DB::table('permisos')->where('activo', 1)->pluck('clave');
            foreach ($permisos as $clave) {
                Permission::findOrCreate($clave, self::GUARD);
            }
            $permisosBorrados = Permission::where('guard_name', self::GUARD)
                ->whereNotIn('name', $permisos)
                ->delete();

            $roles = Rol::all();
            foreach ($roles as $rol) {
                $this->syncRole($rol);
            }
            $rolesBorrados = Role::where('guard_name', self::GUARD)
                ->whereNotIn('name', $roles->pluck('nombre'))
                ->delete();

            $usuarios = Usuario::with('rol')->get();
            foreach ($usuarios as $usuario) {
                $this->syncUserRole($usuario);
            }

            $this->forgetCache();

            return [
                'permisos' => $permisos->count(),
                'permisos_borrados' => $permisosBorrados,
                'roles' => $roles->count(),
                'roles_borrados' => $rolesBorrados,
                'usuarios' => $usuarios->count(),
            ];
        });
    }

    /**
     * Sincronizacion puntual al iniciar sesion: el rol del usuario y los
     * permisos de ese rol (por si el admin los cambio desde el modulo legacy
     * desde la ultima sincronizacion completa).
     */
    public function syncUser(Usuario $usuario): void
    {
        DB::transaction(function () use ($usuario) {
            $usuario->loadMissing('rol');
            if ($usuario->rol) {
                $this->syncRole($usuario->rol);
            }
            $this->syncUserRole($usuario);
        });

        $this->forgetCache();
    }

    private function syncRole(Rol $rol): void
    {
        $role = Role::findOrCreate($rol->nombre, self::GUARD);

        $claves = $rol->activo
            ? $rol->permisos()->where('permisos.activo', 1)->pluck('clave')->all()
            : [];

        foreach ($claves as $clave) {
            Permission::findOrCreate($clave, self::GUARD);
        }

        $role->syncPermissions($claves);
    }

    private function syncUserRole(Usuario $usuario): void
    {
        $roles = $usuario->estado === 'activo' && $usuario->rol
            ? [$usuario->rol->nombre]
            : [];

        $usuario->syncRoles($roles);
    }

    private function forgetCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
