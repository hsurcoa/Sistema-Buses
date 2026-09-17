<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bus real de la flota (tabla `vehiculos`) — es la tabla que usa el negocio
 * hoy (`viajes.bus_id -> vehiculos.id`). `buses` es una tabla paralela sin
 * uso real (ver auditoria de normalizacion); se preserva su modelo (`Bus`)
 * pero no se escribe en ella.
 */
class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    public $timestamps = false;
    const CREATED_AT = null;

    protected $fillable = [
        'propietario_nombres', 'propietario_apellidos', 'tarjeta_circulacion', 'placa', 'tipo_bus_id',
        'soat_numero', 'soat_vencimiento', 'itv_numero', 'itv_vencimiento',
        'clase', 'marca', 'anio', 'modelo', 'tipo_combustible', 'carroceria', 'ejes', 'color',
        'nro_motor', 'cilindros', 'nro_serie', 'ruedas', 'peso_seco', 'peso_bruto', 'longitud',
        'altura', 'ancho', 'pasajeros', 'asientos', 'tipo_servicio', 'estado', 'fecha_registro',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecha_registro' => 'datetime',
            'soat_vencimiento' => 'date:Y-m-d',
            'itv_vencimiento' => 'date:Y-m-d',
        ];
    }

    public function tipoBus()
    {
        return $this->belongsTo(TipoBus::class, 'tipo_bus_id');
    }

    public function asignacionActiva()
    {
        return $this->hasOne(AsignacionBus::class, 'bus_id')->where('estado', 1);
    }

    public function viajes()
    {
        return $this->hasMany(Viaje::class, 'bus_id');
    }
}
