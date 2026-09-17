<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    protected $table = 'rutas';

    public $timestamps = false;

    protected $fillable = ['origen', 'destino', 'estado'];

    protected function casts(): array
    {
        return ['fecha_creacion' => 'datetime', 'estado' => 'boolean'];
    }

    public function paradas()
    {
        return $this->hasMany(RutaParada::class, 'ruta_id');
    }

    public function tarifasTramo()
    {
        return $this->hasMany(TarifaTramo::class, 'ruta_id');
    }

    public function tarifaEncomienda()
    {
        return $this->hasOne(TarifaEncomienda::class, 'ruta_id');
    }

    public function viajes()
    {
        return $this->hasMany(Viaje::class, 'ruta_id');
    }
}
