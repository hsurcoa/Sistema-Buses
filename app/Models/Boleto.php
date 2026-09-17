<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Boleto extends Model
{
    protected $table = 'boletos';

    public $timestamps = false;

    protected $fillable = [
        'viaje_id', 'cliente_id', 'usuario_vendedor_id', 'sesion_caja_id', 'sucursal_id',
        'parada_subida_id', 'parada_id', 'numero_asiento', 'precio_final', 'metodo_pago',
        'referencia_pago', 'fecha_pago', 'estado', 'fecha_reserva', 'fecha_expiracion_reserva', 'codigo_boleto',
    ];

    protected function casts(): array
    {
        return [
            'precio_final' => 'decimal:2',
            'fecha_pago' => 'datetime',
            'fecha_reserva' => 'datetime',
            'fecha_expiracion_reserva' => 'datetime',
        ];
    }

    public function viaje()
    {
        return $this->belongsTo(Viaje::class, 'viaje_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(Usuario::class, 'usuario_vendedor_id');
    }

    public function sesionCaja()
    {
        return $this->belongsTo(CajaSesion::class, 'sesion_caja_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Terminal::class, 'sucursal_id');
    }

    public function paradaSubida()
    {
        return $this->belongsTo(RutaParada::class, 'parada_subida_id');
    }

    public function paradaBajada()
    {
        return $this->belongsTo(RutaParada::class, 'parada_id');
    }
}
