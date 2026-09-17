<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Gestion de cuentas de usuario (Administrador > Usuarios del sistema) y
 * alta de cuentas desde el modulo Personal. Reescritura Eloquent de
 * `legacy/app/models/UsuarioModel.php`.
 */
class UsuarioAdminService
{
    /** OJO: el original busca duplicados en `personal`, no en `usuarios` (bug preexistente, se preserva). */
    public function existeEmail(string $email, ?int $id = null): bool
    {
        $q = DB::table('personal')->where('email', $email);
        if ($id) {
            $q->where('id', '<>', $id);
        }

        return $q->exists();
    }

    public function registrarUsuario(array $datos): bool
    {
        $rolId = DB::table('roles')->where('nombre', $datos['perfil'])->value('id') ?? 2;

        return (bool) Usuario::create([
            'username' => $datos['username'],
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'email' => $datos['email'],
            'password' => $datos['password_hash'],
            'rol_id' => $rolId,
            'estado' => 'activo',
        ]);
    }

    public function actualizarCredenciales(string $oldEmail, string $newEmail, string $newPasswordHash): bool
    {
        return (bool) Usuario::where('email', $oldEmail)->update(['email' => $newEmail, 'password' => $newPasswordHash]);
    }

    /**
     * Usuarios con su rol y cuanto historial tienen (boletos vendidos, cajas,
     * encomiendas): con historial no se pueden borrar, solo desactivar.
     * "automatica" marca las cuentas creadas por el sistema para choferes.
     */
    public function listarUsuarios()
    {
        return DB::table('usuarios as u')
            ->leftJoin('roles as r', 'r.id', '=', 'u.rol_id')
            ->leftJoin('terminales as t', 't.id', '=', 'u.sucursal_id')
            ->select([
                'u.id', 'u.username', 'u.nombres', 'u.apellidos', 'u.email', 'u.rol_id', 'u.estado', 'u.created_at',
                'u.nro_documento', 'u.celular', 'u.sucursal_id', DB::raw('t.nombre_sede AS sucursal'), DB::raw('r.nombre AS rol'),
                DB::raw('(SELECT COUNT(*) FROM boletos b WHERE b.usuario_vendedor_id = u.id) AS total_boletos'),
                DB::raw('(SELECT COUNT(*) FROM cajas_sesiones c WHERE c.usuario_id = u.id) AS total_cajas'),
                DB::raw('(SELECT COUNT(*) FROM encomiendas e WHERE e.usuario_creacion_id = u.id) AS total_encomiendas'),
                DB::raw("(u.username LIKE 'chofer\\_%' OR u.email LIKE '%.sistema.temp' OR u.email LIKE '%@test.com') AS automatica"),
            ])
            ->orderByRaw("u.estado = 'activo' DESC")->orderBy('r.nombre')->orderBy('u.apellidos')->orderBy('u.nombres')
            ->get();
    }

    public function obtenerUsuario(int $id): ?object
    {
        return DB::table('usuarios as u')->leftJoin('roles as r', 'r.id', '=', 'u.rol_id')
            ->where('u.id', $id)
            ->select('u.id', 'u.username', 'u.nombres', 'u.apellidos', 'u.email', 'u.rol_id', 'u.estado', 'u.nro_documento', 'u.celular', 'u.sucursal_id', DB::raw('r.nombre AS rol'))
            ->first();
    }

    public function listarRolesActivos()
    {
        return DB::table('roles')->where('activo', 1)->orderBy('nombre')->select('id', 'nombre', 'descripcion')->get();
    }

    public function rolActivo(int $rolId): ?object
    {
        return DB::table('roles')->where('id', $rolId)->where('activo', 1)->select('id', 'nombre')->first();
    }

    public function campoEnUso(string $campo, string $valor, int $excluirId = 0): bool
    {
        $columna = $campo === 'username' ? 'username' : 'email';

        return DB::table('usuarios')->where($columna, $valor)->where('id', '<>', $excluirId)->exists();
    }

    /** Administradores activos, sin contar opcionalmente a un usuario. */
    public function contarAdministradoresActivos(int $excluirId = 0): int
    {
        return DB::table('usuarios as u')->join('roles as r', 'r.id', '=', 'u.rol_id')
            ->where('r.nombre', 'Administrador')->where('u.estado', 'activo')->where('u.id', '<>', $excluirId)
            ->count();
    }

    public function crearUsuario(array $d): int
    {
        $id = DB::table('usuarios')->insertGetId([
            'username' => $d['username'] !== '' ? $d['username'] : null,
            'nombres' => $d['nombres'],
            'apellidos' => $d['apellidos'],
            'email' => $d['email'],
            'password' => $d['password_hash'],
            'rol_id' => $d['rol_id'],
            'estado' => $d['activo'] ? 'activo' : 'inactivo',
            'nro_documento' => $d['nro_documento'] !== '' ? $d['nro_documento'] : null,
            'celular' => $d['celular'] !== '' ? $d['celular'] : null,
            'sucursal_id' => ! empty($d['sucursal_id']) ? (int) $d['sucursal_id'] : null,
        ]);

        return (int) $id;
    }

    public function actualizarUsuario(array $d): bool
    {
        $campos = [
            'username' => $d['username'] !== '' ? $d['username'] : null,
            'nombres' => $d['nombres'],
            'apellidos' => $d['apellidos'],
            'email' => $d['email'],
            'rol_id' => $d['rol_id'],
            'estado' => $d['activo'] ? 'activo' : 'inactivo',
            'nro_documento' => $d['nro_documento'] !== '' ? $d['nro_documento'] : null,
            'celular' => $d['celular'] !== '' ? $d['celular'] : null,
            'sucursal_id' => ! empty($d['sucursal_id']) ? (int) $d['sucursal_id'] : null,
        ];
        if (! empty($d['password_hash'])) {
            $campos['password'] = $d['password_hash'];
        }

        return (bool) DB::table('usuarios')->where('id', $d['id'])->update($campos);
    }

    public function cambiarEstadoUsuario(int $id, bool $activo): bool
    {
        return (bool) Usuario::where('id', $id)->update(['estado' => $activo ? 'activo' : 'inactivo']);
    }

    /** Borra la cuenta solo si no tiene historial (si lo tiene, la FK lo impediria igual). */
    public function eliminarUsuarioSinHistorial(int $id): bool
    {
        $tieneHistorial = DB::table('boletos')->where('usuario_vendedor_id', $id)->exists()
            || DB::table('cajas_sesiones')->where('usuario_id', $id)->exists()
            || DB::table('encomiendas')->where('usuario_creacion_id', $id)->exists();

        if ($tieneHistorial) {
            return false;
        }

        return (bool) Usuario::where('id', $id)->delete();
    }

    public function estaActivo(int $id): bool
    {
        return Usuario::where('id', $id)->value('estado') === 'activo';
    }
}
