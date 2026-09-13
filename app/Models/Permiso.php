<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Permiso del sistema legacy (tabla `permisos`, ya existente).
 */
class Permiso extends Model
{
    protected $table = 'permisos';

    public $timestamps = false;

    protected $fillable = ['grupo', 'vista', 'clave', 'descripcion', 'orden', 'activo'];

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'permiso_id', 'rol_id');
    }
}
