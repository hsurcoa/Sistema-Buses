<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoBus extends Model
{
    protected $table = 'tipos_buses';

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    protected $fillable = ['nombre', 'capacidad', 'pisos', 'configuracion_asientos', 'estado'];

    protected function casts(): array
    {
        return ['estado' => 'boolean'];
    }

    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'tipo_bus_id');
    }
}
