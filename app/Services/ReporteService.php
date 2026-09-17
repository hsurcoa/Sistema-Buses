<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Reescritura Eloquent de `legacy/app/models/ReporteModel.php`. */
class ReporteService
{
    public function obtenerRutas()
    {
        return DB::table('rutas')->where('estado', 1)->orderBy('origen')->orderBy('destino')->select('id', 'origen', 'destino')->get();
    }

    public function obtenerBuses()
    {
        return DB::table('vehiculos')->orderBy('placa')->select('id', 'placa')->get();
    }

    public function buscarViajesConConteo(?string $fecha, $rutaId = null, $busId = null)
    {
        $q = DB::table('viajes as v')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->leftJoin('vehiculos as b', 'v.bus_id', '=', 'b.id')
            ->leftJoin('tipos_buses as tb', 'v.tipo_bus_id', '=', 'tb.id')
            ->leftJoin('personal as p', 'v.chofer_id', '=', 'p.id')
            ->select('v.id', 'v.fecha_salida', 'v.hora_salida',
                DB::raw("CONCAT(r.origen, ' - ', r.destino) as nombre_ruta"),
                DB::raw("COALESCE(b.placa, 'Sin Asignar') as placa"),
                DB::raw("COALESCE(CONCAT(p.nombres, ' ', p.apellidos), 'Sin Conductor') as chofer"),
                DB::raw('COALESCE(tb.capacidad, 40) as capacidad'),
                DB::raw("(SELECT COUNT(*) FROM boletos WHERE viaje_id = v.id AND estado IN ('vendido', 'reservado')) as total_pasajeros"),
                DB::raw("(SELECT MIN(DATE(fecha_reserva)) FROM boletos WHERE viaje_id = v.id AND estado IN ('vendido', 'reservado')) as primera_venta"),
                DB::raw("(SELECT MAX(DATE(fecha_reserva)) FROM boletos WHERE viaje_id = v.id AND estado IN ('vendido', 'reservado')) as ultima_venta"));

        if (! empty($fecha)) {
            $q->where(function ($w) use ($fecha) {
                $w->where('v.fecha_salida', $fecha)
                    ->orWhereExists(function ($sub) use ($fecha) {
                        $sub->selectRaw(1)->from('boletos')
                            ->whereColumn('viaje_id', 'v.id')
                            ->whereRaw('DATE(fecha_reserva) = ?', [$fecha])
                            ->whereIn('estado', ['vendido', 'reservado']);
                    });
            });
        }
        if (! empty($rutaId)) {
            $q->where('v.ruta_id', $rutaId);
        }
        if (! empty($busId)) {
            $q->where('v.bus_id', $busId);
        }

        return $q->orderBy('v.fecha_salida')->orderBy('v.hora_salida')->get();
    }

    public function obtenerPasajerosPorViaje(int $viajeId)
    {
        return DB::table('boletos as b')
            ->join('viajes as v', 'b.viaje_id', '=', 'v.id')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->join('clientes as c', 'b.cliente_id', '=', 'c.id')
            ->where('b.viaje_id', $viajeId)
            ->whereIn('b.estado', ['vendido', 'reservado'])
            ->select('b.numero_asiento', DB::raw('c.numero_documento as documento'), 'c.nombres', 'c.apellidos',
                DB::raw("COALESCE(c.celular, '-') as telefono"), DB::raw('r.destino as destino'), 'b.estado')
            ->orderByRaw('CAST(b.numero_asiento AS UNSIGNED) ASC')
            ->get();
    }

    public function obtenerInfoViaje(int $viajeId): ?object
    {
        return DB::table('viajes as v')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->leftJoin('vehiculos as b', 'v.bus_id', '=', 'b.id')
            ->leftJoin('personal as p', 'v.chofer_id', '=', 'p.id')
            ->where('v.id', $viajeId)
            ->select('v.id', 'v.fecha_salida', 'v.hora_salida',
                DB::raw("CONCAT(r.origen, ' - ', r.destino) as nombre_ruta"),
                DB::raw("COALESCE(b.placa, 'Sin Asignar') as placa"),
                DB::raw("COALESCE(CONCAT(p.nombres, ' ', p.apellidos), 'Sin Conductor') as chofer"))
            ->first();
    }

    public function obtenerVentasHoy(): ?object
    {
        return DB::table('boletos')->where('estado', 'vendido')->whereRaw('DATE(fecha_reserva) = CURDATE()')
            ->selectRaw('SUM(precio_final) as total, COUNT(id) as cantidad')->first();
    }

    public function obtenerVentasMes(): array
    {
        $actual = DB::table('boletos')->where('estado', 'vendido')
            ->whereRaw('MONTH(fecha_reserva) = MONTH(CURRENT_DATE())')
            ->whereRaw('YEAR(fecha_reserva) = YEAR(CURRENT_DATE())')
            ->sum('precio_final');

        $anterior = DB::table('boletos')->where('estado', 'vendido')
            ->whereRaw('MONTH(fecha_reserva) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)')
            ->whereRaw('YEAR(fecha_reserva) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)')
            ->sum('precio_final');

        return ['actual' => $actual ?: 0, 'anterior' => $anterior ?: 0];
    }

    public function obtenerMejorRutaMes(): ?object
    {
        return DB::table('boletos as b')
            ->join('viajes as v', 'b.viaje_id', '=', 'v.id')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->where('b.estado', 'vendido')
            ->whereRaw('MONTH(b.fecha_reserva) = MONTH(CURRENT_DATE())')
            ->groupBy('r.id')
            ->orderByDesc('total_ventas')
            ->select('r.origen', 'r.destino', DB::raw('SUM(b.precio_final) as total_ventas'), DB::raw('COUNT(b.id) as cantidad_pasajes'))
            ->limit(1)
            ->first();
    }

    public function obtenerTendenciaSemanal()
    {
        return DB::table('boletos')->where('estado', 'vendido')
            ->whereRaw('fecha_reserva >= DATE(NOW()) - INTERVAL 7 DAY')
            ->groupBy(DB::raw('DATE(fecha_reserva)'))
            ->orderBy('fecha')
            ->select(DB::raw('DATE(fecha_reserva) as fecha'), DB::raw('SUM(precio_final) as total'))
            ->get();
    }

    public function obtenerDesgloseFinanciero(?string $fechaInicio = null, ?string $fechaFin = null)
    {
        $q = DB::table('boletos as b')
            ->join('viajes as v', 'b.viaje_id', '=', 'v.id')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->leftJoin('vehiculos as veh', 'v.bus_id', '=', 'veh.id')
            ->where('b.estado', 'vendido')
            ->select(DB::raw('DATE(b.fecha_reserva) as fecha'), DB::raw('v.id as viaje_id'),
                DB::raw("CONCAT(r.origen, ' - ', r.destino) as ruta"), DB::raw("COALESCE(veh.placa, 'Sin Bus') as bus"),
                DB::raw('veh.id as bus_numero'), DB::raw('COUNT(b.id) as pasajes_vendidos'), DB::raw('SUM(b.precio_final) as total_ingresos'))
            ->groupBy('v.id')
            ->orderByDesc('b.fecha_reserva');

        if ($fechaInicio && $fechaFin) {
            $q->whereRaw('DATE(b.fecha_reserva) BETWEEN ? AND ?', [$fechaInicio, $fechaFin]);
        } else {
            $q->whereRaw('b.fecha_reserva >= DATE(NOW()) - INTERVAL 30 DAY');
        }

        return $q->get();
    }

    public function buscarHistorico(string $fechaInicio, string $fechaFin, $rutaId = null)
    {
        $q = DB::table('viajes as v')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->leftJoin('vehiculos as b', 'v.bus_id', '=', 'b.id')
            ->leftJoin('tipos_buses as tb', 'v.tipo_bus_id', '=', 'tb.id')
            ->leftJoin('personal as p', 'v.chofer_id', '=', 'p.id')
            ->whereBetween('v.fecha_salida', [$fechaInicio, $fechaFin])
            ->select('v.id', 'v.fecha_salida', 'v.hora_salida',
                DB::raw("CONCAT(r.origen, ' - ', r.destino) as nombre_ruta"),
                DB::raw("COALESCE(b.placa, 'Sin Asignar') as placa"),
                DB::raw("COALESCE(CONCAT(p.nombres, ' ', p.apellidos), 'Sin Conductor') as chofer"),
                DB::raw('COALESCE(tb.capacidad, 40) as capacidad'),
                DB::raw("(SELECT COUNT(*) FROM boletos WHERE viaje_id = v.id AND estado IN ('vendido', 'reservado')) as total_pasajeros"));

        if (! empty($rutaId)) {
            $q->where('v.ruta_id', $rutaId);
        }

        return $q->orderByDesc('v.fecha_salida')->orderBy('v.hora_salida')->get();
    }

    public function buscarPasajero(string $criterio)
    {
        return DB::table('clientes as c')
            ->join('boletos as b', 'c.id', '=', 'b.cliente_id')
            ->join('viajes as v', 'b.viaje_id', '=', 'v.id')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->where(function ($q) use ($criterio) {
                $like = '%'.$criterio.'%';
                $q->where('c.numero_documento', 'like', $like)
                    ->orWhere('c.apellidos', 'like', $like)
                    ->orWhere('c.nombres', 'like', $like);
            })
            ->select('c.id', 'c.nombres', 'c.apellidos', 'c.numero_documento', 'v.fecha_salida', 'v.hora_salida',
                DB::raw("CONCAT(r.origen, ' - ', r.destino) as ruta"), 'b.numero_asiento', 'b.precio_final', 'b.estado', DB::raw('v.id as viaje_id'))
            ->orderByDesc('v.fecha_salida')
            ->get();
    }
}
