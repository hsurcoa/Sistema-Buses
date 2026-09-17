<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Configuracion clave/valor (tabla `configuracion_sistema`). */
class ConfiguracionSistema extends Model
{
    protected $table = 'configuracion_sistema';

    public $timestamps = false;

    protected $fillable = ['clave', 'valor', 'updated_at'];
}
