<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaSesion extends Model
{
    protected $table = 'cajas_sesiones';

    public $timestamps = false;

    protected $fillable = ['usuario_id', 'sucursal_id', 'fecha_apertura', 'fecha_cierre', 'monto_inicial', 'monto_final_sistema', 'monto_final_real', 'diferencia', 'estado'];

    protected function casts(): array
    {
        return [
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
            'monto_inicial' => 'decimal:2',
            'monto_final_sistema' => 'decimal:2',
            'monto_final_real' => 'decimal:2',
            'diferencia' => 'decimal:2',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Terminal::class, 'sucursal_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'sesion_id');
    }
}
