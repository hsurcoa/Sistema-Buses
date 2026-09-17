<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tripulacion (chofer + copiloto opcional) asignada permanentemente a un bus. */
class AsignacionBus extends Model
{
    protected $table = 'asignaciones_buses';

    public $timestamps = false;

    protected $fillable = ['chofer_id', 'bus_id', 'copiloto_id', 'fecha_asignacion', 'estado'];

    protected function casts(): array
    {
        return ['fecha_asignacion' => 'datetime', 'estado' => 'boolean'];
    }

    public function bus()
    {
        return $this->belongsTo(Vehiculo::class, 'bus_id');
    }

    public function chofer()
    {
        return $this->belongsTo(Personal::class, 'chofer_id');
    }

    public function copiloto()
    {
        return $this->belongsTo(Personal::class, 'copiloto_id');
    }
}
