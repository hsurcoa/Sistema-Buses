<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encomienda extends Model
{
    protected $table = 'encomiendas';

    public $timestamps = false;
    const CREATED_AT = 'fecha_registro';
    const UPDATED_AT = 'fecha_actualizacion';

    protected $fillable = [
        'codigo_guia', 'viaje_id', 'sucursal_origen_id', 'sucursal_destino_id', 'remitente_id',
        'remitente_nombre', 'remitente_dni', 'destinatario_id', 'destinatario_nombre', 'destinatario_dni',
        'destinatario_telefono', 'clave_retiro', 'usuario_creacion_id', 'total_pagar', 'estado_pago', 'estado',
    ];

    protected function casts(): array
    {
        return ['total_pagar' => 'decimal:2'];
    }

    public function viaje()
    {
        return $this->belongsTo(Viaje::class, 'viaje_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleEncomienda::class, 'encomienda_id');
    }
}
