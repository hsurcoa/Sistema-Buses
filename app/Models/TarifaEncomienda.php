<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifaEncomienda extends Model
{
    protected $table = 'tarifas_encomienda';

    public $timestamps = false;

    protected $fillable = ['ruta_id', 'precio_base', 'precio_por_kg', 'porcentaje_seguro', 'estado'];

    protected function casts(): array
    {
        return ['precio_base' => 'decimal:2', 'precio_por_kg' => 'decimal:2', 'porcentaje_seguro' => 'decimal:2', 'estado' => 'boolean'];
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }
}
