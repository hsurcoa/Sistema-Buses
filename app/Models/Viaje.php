<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Viaje extends Model
{
    protected $table = 'viajes';

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    protected $fillable = [
        'ruta_id', 'tipo_bus_id', 'terminal_origen_id', 'terminal_destino_id', 'bus_id', 'chofer_id',
        'fecha_salida', 'hora_salida', 'fecha_llegada_estimada', 'hora_llegada', 'precio_base',
        'tipo_servicio', 'servicios_incluidos', 'notas', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_salida' => 'datetime',
            'fecha_llegada_estimada' => 'datetime',
            'precio_base' => 'decimal:2',
        ];
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }

    public function tipoBus()
    {
        return $this->belongsTo(TipoBus::class, 'tipo_bus_id');
    }

    public function bus()
    {
        return $this->belongsTo(Vehiculo::class, 'bus_id');
    }

    public function chofer()
    {
        return $this->belongsTo(Personal::class, 'chofer_id');
    }

    public function terminalOrigen()
    {
        return $this->belongsTo(Terminal::class, 'terminal_origen_id');
    }

    public function terminalDestino()
    {
        return $this->belongsTo(Terminal::class, 'terminal_destino_id');
    }

    public function boletos()
    {
        return $this->hasMany(Boleto::class, 'viaje_id');
    }
}
