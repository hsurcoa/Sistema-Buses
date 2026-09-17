<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancelacionBoleto extends Model
{
    protected $table = 'cancelaciones_boletos';

    public $timestamps = false;
    const CREATED_AT = 'fecha_creacion';

    protected $fillable = [
        'boleto_id', 'usuario_id', 'sesion_caja_id', 'monto', 'devuelto',
        'metodo_devolucion', 'motivo', 'usuario_devolucion_id', 'fecha_devolucion',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'devuelto' => 'boolean',
            'fecha_devolucion' => 'datetime',
        ];
    }

    public function boleto()
    {
        return $this->belongsTo(Boleto::class, 'boleto_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function usuarioDevolucion()
    {
        return $this->belongsTo(Usuario::class, 'usuario_devolucion_id');
    }

    public function sesionCaja()
    {
        return $this->belongsTo(CajaSesion::class, 'sesion_caja_id');
    }
}
