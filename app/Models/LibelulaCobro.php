<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibelulaCobro extends Model
{
    protected $table = 'libelula_cobros';

    public $timestamps = false;
    const CREATED_AT = 'fecha_creacion';

    protected $fillable = [
        'boleto_id', 'identificador_deuda', 'id_transaccion', 'codigo_recaudacion',
        'url_pasarela_pagos', 'qr_simple_url', 'monto', 'estado', 'respuesta_registro', 'fecha_pago',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_pago' => 'datetime',
        ];
    }

    public function boleto()
    {
        return $this->belongsTo(Boleto::class, 'boleto_id');
    }
}
