<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Parada intermedia de una ruta. Orden de recorrido: origen=0, paradas=orden_index(1..n), destino=TarifaTramo::DESTINO. */
class RutaParada extends Model
{
    protected $table = 'rutas_paradas';

    public $timestamps = false;

    protected $fillable = ['ruta_id', 'nombre_parada', 'orden_index', 'precio_pasaje', 'precio_base_encomienda', 'estado'];

    protected function casts(): array
    {
        return ['precio_pasaje' => 'decimal:2', 'precio_base_encomienda' => 'decimal:2', 'estado' => 'boolean'];
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }
}
