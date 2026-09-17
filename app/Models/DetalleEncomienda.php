<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleEncomienda extends Model
{
    protected $table = 'detalles_encomienda';

    public $timestamps = false;

    protected $fillable = ['encomienda_id', 'descripcion', 'peso_kg', 'tipo_carga', 'valor_declarado', 'precio_calculado'];

    protected function casts(): array
    {
        return ['peso_kg' => 'decimal:2', 'valor_declarado' => 'decimal:2', 'precio_calculado' => 'decimal:2'];
    }

    public function encomienda()
    {
        return $this->belongsTo(Encomienda::class, 'encomienda_id');
    }
}
