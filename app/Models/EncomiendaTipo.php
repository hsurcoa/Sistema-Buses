<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncomiendaTipo extends Model
{
    protected $table = 'encomienda_tipos';

    public $timestamps = false;

    protected $fillable = ['nombre', 'precio_extra', 'descripcion', 'estado'];

    protected function casts(): array
    {
        return ['precio_extra' => 'decimal:2', 'estado' => 'boolean'];
    }
}
