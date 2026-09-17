<?php

namespace App\Services;

use App\Models\AsignacionBus;
use Illuminate\Support\Facades\DB;

/**
 * Asignacion permanente de tripulacion a un bus (chofer + copiloto opcional).
 * Reescritura Eloquent de `legacy/app/models/AsignacionModel.php`.
 *
 * Reglas: un bus tiene como maximo una asignacion activa; un chofer/copiloto
 * esta activo en un solo bus; el chofer debe tener perfil Chofer y el
 * copiloto perfil Copiloto, ambos activos; no pueden ser la misma persona;
 * finalizar una asignacion no la borra (queda en el historial, estado = 0).
 */
class AsignacionService
{
    public function obtenerChoferes()
    {
        return DB::table('personal as p')
            ->leftJoin('asignaciones_buses as ab', function ($j) {
                $j->on('ab.chofer_id', '=', 'p.id')->where('ab.estado', 1);
            })
            ->leftJoin('vehiculos as v', 'v.id', '=', 'ab.bus_id')
            ->where('p.perfil', 'Chofer')->where('p.estado', 1)
            ->select('p.id', 'p.nombres', 'p.apellidos', 'p.numero_documento', DB::raw('v.placa AS bus_actual'))
            ->orderBy('p.apellidos')->orderBy('p.nombres')
            ->get();
    }

    public function obtenerCopilotos()
    {
        return DB::table('personal as p')
            ->leftJoin('asignaciones_buses as ab', function ($j) {
                $j->on('ab.copiloto_id', '=', 'p.id')->where('ab.estado', 1);
            })
            ->leftJoin('vehiculos as v', 'v.id', '=', 'ab.bus_id')
            ->where('p.perfil', 'Copiloto')->where('p.estado', 1)
            ->select('p.id', 'p.nombres', 'p.apellidos', 'p.numero_documento', DB::raw('v.placa AS bus_actual'))
            ->orderBy('p.apellidos')->orderBy('p.nombres')
            ->get();
    }

    /** Buses activos con su tipo real y la tripulacion actual (si tiene). */
    public function obtenerBuses()
    {
        return DB::table('vehiculos as v')
            ->leftJoin('tipos_buses as tb', 'tb.id', '=', 'v.tipo_bus_id')
            ->leftJoin('asignaciones_buses as ab', function ($j) {
                $j->on('ab.bus_id', '=', 'v.id')->where('ab.estado', 1);
            })
            ->leftJoin('personal as c', 'c.id', '=', 'ab.chofer_id')
            ->where('v.estado', 1)
            ->select('v.id', 'v.placa', 'v.marca', 'v.modelo', 'v.asientos',
                DB::raw('tb.id AS tipo_bus_id'), DB::raw('tb.nombre AS tipo_nombre'), DB::raw('tb.capacidad AS tipo_capacidad'), 'tb.pisos',
                DB::raw("CONCAT(c.nombres, ' ', c.apellidos) AS chofer_actual"))
            ->orderBy('v.placa')
            ->get();
    }

    public function listarAsignaciones(bool $incluirHistorial = false)
    {
        $q = DB::table('asignaciones_buses as ab')
            ->join('personal as c', 'ab.chofer_id', '=', 'c.id')
            ->leftJoin('personal as cp', 'ab.copiloto_id', '=', 'cp.id')
            ->join('vehiculos as v', 'ab.bus_id', '=', 'v.id')
            ->leftJoin('tipos_buses as tb', 'tb.id', '=', 'v.tipo_bus_id')
            ->select('ab.id', 'ab.fecha_asignacion', 'ab.estado', 'ab.chofer_id', 'ab.copiloto_id', 'ab.bus_id',
                DB::raw("CONCAT(c.nombres, ' ', c.apellidos) AS nombre_chofer"), DB::raw('c.numero_documento AS documento_chofer'),
                DB::raw("CONCAT(cp.nombres, ' ', cp.apellidos) AS nombre_copiloto"),
                DB::raw('v.placa AS placa_bus'), 'v.marca', 'v.modelo', 'v.asientos',
                DB::raw('tb.nombre AS tipo_nombre'), DB::raw('tb.capacidad AS tipo_capacidad'), 'tb.pisos');

        if (! $incluirHistorial) {
            $q->where('ab.estado', 1);
        }

        return $q->orderByDesc('ab.estado')->orderByDesc('ab.fecha_asignacion')->orderByDesc('ab.id')->get();
    }

    public function obtenerAsignacionPorId($id): ?AsignacionBus
    {
        return AsignacionBus::find($id);
    }

    /** @return array{errores: string[], conflictos: string[]} */
    public function validar(array $datos): array
    {
        $errores = [];
        $conflictos = [];
        $id = ! empty($datos['id']) ? (int) $datos['id'] : 0;

        $chofer = $this->persona($datos['chofer_id']);
        if (! $chofer || ! $chofer->estado || $chofer->perfil !== 'Chofer') {
            $errores[] = 'El chofer seleccionado no existe, está inactivo o no tiene perfil de Chofer.';
        }

        $cop = ! empty($datos['copiloto_id']) ? (int) $datos['copiloto_id'] : 0;
        if ($cop) {
            if ($cop === (int) $datos['chofer_id']) {
                $errores[] = 'El chofer y el copiloto no pueden ser la misma persona.';
            }
            $copiloto = $this->persona($cop);
            if (! $copiloto || ! $copiloto->estado || $copiloto->perfil !== 'Copiloto') {
                $errores[] = 'El copiloto seleccionado no existe, está inactivo o no tiene perfil de Copiloto.';
            }
        }

        $bus = DB::table('vehiculos')->where('id', $datos['bus_id'])->select('id', 'placa', 'estado', 'tipo_bus_id')->first();
        if (! $bus || ! $bus->estado) {
            $errores[] = 'El bus seleccionado no existe o está dado de baja.';
        }

        if ($errores) {
            return ['errores' => $errores, 'conflictos' => []];
        }

        $otras = DB::table('asignaciones_buses as ab')
            ->join('vehiculos as v', 'v.id', '=', 'ab.bus_id')
            ->join('personal as c', 'c.id', '=', 'ab.chofer_id')
            ->where('ab.estado', 1)->where('ab.id', '<>', $id)
            ->where(function ($q) use ($datos, $cop) {
                $q->where('ab.bus_id', $datos['bus_id'])
                    ->orWhereIn('ab.chofer_id', [$datos['chofer_id'], $cop])
                    ->orWhereIn('ab.copiloto_id', [$datos['chofer_id'], $cop]);
            })
            ->select('ab.id', 'v.placa', DB::raw("CONCAT(c.nombres, ' ', c.apellidos) AS chofer"), 'ab.bus_id', 'ab.chofer_id', 'ab.copiloto_id')
            ->get();

        foreach ($otras as $otra) {
            if ((int) $otra->bus_id === (int) $datos['bus_id']) {
                $conflictos[] = "El bus {$otra->placa} ya tiene como chofer a {$otra->chofer}.";
            }
            if (in_array((int) $datos['chofer_id'], [(int) $otra->chofer_id, (int) $otra->copiloto_id], true)) {
                $conflictos[] = "{$chofer->nombres} {$chofer->apellidos} ya está asignado al bus {$otra->placa}.";
            }
            if ($cop && in_array($cop, [(int) $otra->chofer_id, (int) $otra->copiloto_id], true)) {
                $conflictos[] = "El copiloto ya está asignado al bus {$otra->placa}.";
            }
        }

        if (! $bus->tipo_bus_id) {
            $conflictos[] = "Aviso: el bus {$bus->placa} no tiene tipo de bus (asientos) definido. Complételo en Gestión de Flota antes de programar viajes.";
        }

        return ['errores' => $errores, 'conflictos' => array_values(array_unique($conflictos))];
    }

    /**
     * Crea o actualiza una asignacion. Con $reemplazar = true, finaliza primero
     * las asignaciones activas que chocan (mismo bus, chofer o copiloto).
     */
    public function guardar(array $datos, bool $reemplazar = false): bool
    {
        $id = ! empty($datos['id']) ? (int) $datos['id'] : 0;
        $copiloto = ! empty($datos['copiloto_id']) ? $datos['copiloto_id'] : null;

        return DB::transaction(function () use ($datos, $id, $copiloto, $reemplazar) {
            if ($reemplazar) {
                DB::table('asignaciones_buses')
                    ->where('estado', 1)->where('id', '<>', $id)
                    ->where(function ($q) use ($datos, $copiloto) {
                        $q->where('bus_id', $datos['bus_id'])
                            ->orWhereIn('chofer_id', [$datos['chofer_id'], $copiloto ?? 0])
                            ->orWhereIn('copiloto_id', [$datos['chofer_id'], $copiloto ?? 0]);
                    })
                    ->update(['estado' => 0]);
            }

            if ($id) {
                AsignacionBus::where('id', $id)->update(['chofer_id' => $datos['chofer_id'], 'bus_id' => $datos['bus_id'], 'copiloto_id' => $copiloto, 'estado' => 1]);
            } else {
                AsignacionBus::create(['chofer_id' => $datos['chofer_id'], 'bus_id' => $datos['bus_id'], 'copiloto_id' => $copiloto, 'fecha_asignacion' => now(), 'estado' => 1]);
            }

            return true;
        });
    }

    /** Finaliza la asignacion (queda en el historial). */
    public function finalizarAsignacion($id): bool
    {
        return (bool) AsignacionBus::where('id', $id)->update(['estado' => 0]);
    }

    private function persona($id): ?object
    {
        if (empty($id)) {
            return null;
        }

        return DB::table('personal')->where('id', $id)->select('id', 'nombres', 'apellidos', 'perfil', 'estado')->first();
    }
}
