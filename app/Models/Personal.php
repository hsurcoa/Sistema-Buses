<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Ficha de personal operativo (choferes/copilotos/staff) — tabla `personal`, distinta de `usuarios` (login). */
class Personal extends Model
{
    protected $table = 'personal';

    protected $fillable = [
        'nombres', 'apellidos', 'tipo_documento', 'numero_documento', 'genero', 'fecha_nacimiento',
        'celular', 'email', 'direccion_domicilio', 'departamento', 'provincia', 'distrito',
        'perfil', 'foto', 'estado',
    ];

    protected function casts(): array
    {
        return ['fecha_nacimiento' => 'date', 'estado' => 'boolean'];
    }

    public function asignacionesComoChofer()
    {
        return $this->hasMany(AsignacionBus::class, 'chofer_id');
    }

    public function nombreCompleto(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }
}
