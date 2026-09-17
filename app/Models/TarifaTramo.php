<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tarifa entre dos puntos de una ruta (0 = origen/destino final, id de rutas_paradas = parada intermedia). */
class TarifaTramo extends Model
{
    public const DESTINO = 100000;

    protected $table = 'tarifas_tramo';

    public $timestamps = false;
    public const UPDATED_AT = 'actualizado_en';
    public const CREATED_AT = null;

    protected $fillable = ['ruta_id', 'desde_parada_id', 'hasta_parada_id', 'precio', 'precio_sugerido'];

    protected function casts(): array
    {
        return ['precio' => 'decimal:2', 'precio_sugerido' => 'boolean'];
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }
}
