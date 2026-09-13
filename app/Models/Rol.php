<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Rol del sistema legacy (tabla `roles`, ya existente).
 * Fuente de verdad del RBAC hasta que el modulo Admin se migre (roadmap,
 * paso 5) - se sincroniza hacia spatie/laravel-permission, no se reemplaza.
 */
class Rol extends Model
{
    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'rol_id', 'permiso_id');
    }

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }
}
