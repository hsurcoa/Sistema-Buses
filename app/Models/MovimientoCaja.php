<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    protected $table = 'movimientos_caja';

    public $timestamps = false;
    const CREATED_AT = 'fecha_creacion';

    protected $fillable = ['sesion_id', 'tipo_movimiento', 'origen_modulo', 'referencia_id', 'monto', 'metodo_pago', 'descripcion'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2'];
    }

    public function sesion()
    {
        return $this->belongsTo(CajaSesion::class, 'sesion_id');
    }
}
