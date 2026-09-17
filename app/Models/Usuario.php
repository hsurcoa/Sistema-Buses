<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuario del sistema (tabla `usuarios`, ya existente).
 *
 * Mapea la tabla tal cual esta hoy: un rol por usuario (rol_id), sin
 * remember_token ni updated_at. El RBAC real (roles/permisos/rol_permiso)
 * se sincroniza hacia spatie/laravel-permission - ver App\Services\LegacyRbacSync
 * y el comando `php artisan rbac:sync` (Fase 1, Tarea 9).
 */
class Usuario extends Authenticatable
{
    use HasRoles;

    protected $guard_name = 'web';

    protected $table = 'usuarios';

    // La tabla no tiene columna updated_at.
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'username',
        'nombres',
        'apellidos',
        'email',
        'password',
        'rol_id',
        'estado',
        'sucursal_asignada',
        'tipo_documento',
        'nro_documento',
        'genero',
        'fecha_nacimiento',
        'celular',
        'direccion_domicilio',
        'foto',
        'departamento',
        'provincia',
        'distrito',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    /** Sucursal asignada (columna agregada por la migracion multisucursal). */
    public function sucursal()
    {
        return $this->belongsTo(Terminal::class, 'sucursal_id');
    }

    public function nombreCompleto(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }
}
