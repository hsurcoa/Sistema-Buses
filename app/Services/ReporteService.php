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

    /** `$sucursalId`: viajes que salen desde esa terminal (null = todas, solo Administrador/Supervisor). */
    public function buscarViajesConConteo(?string $fecha, $rutaId = null, $busId = null, ?int $sucursalId = null)
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
        if ($sucursalId) {
            $q->where('v.terminal_origen_id', $sucursalId);
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

    public function obtenerVentasHoy(?int $sucursalId = null): ?object
    {
        $q = DB::table('boletos')->where('estado', 'vendido')->whereRaw('DATE(fecha_reserva) = CURDATE()');
        if ($sucursalId) {
            $q->where('sucursal_id', $sucursalId);
        }

        return $q->selectRaw('SUM(precio_final) as total, COUNT(id) as cantidad')->first();
    }

    public function obtenerVentasMes(?int $sucursalId = null): array
    {
        $actual = DB::table('boletos')->where('estado', 'vendido')
            ->whereRaw('MONTH(fecha_reserva) = MONTH(CURRENT_DATE())')
            ->whereRaw('YEAR(fecha_reserva) = YEAR(CURRENT_DATE())')
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->sum('precio_final');

        $anterior = DB::table('boletos')->where('estado', 'vendido')
            ->whereRaw('MONTH(fecha_reserva) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)')
            ->whereRaw('YEAR(fecha_reserva) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)')
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->sum('precio_final');

        return ['actual' => $actual ?: 0, 'anterior' => $anterior ?: 0];
    }

    public function obtenerMejorRutaMes(?int $sucursalId = null): ?object
    {
        return DB::table('boletos as b')
            ->join('viajes as v', 'b.viaje_id', '=', 'v.id')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->where('b.estado', 'vendido')
            ->whereRaw('MONTH(b.fecha_reserva) = MONTH(CURRENT_DATE())')
            ->when($sucursalId, fn ($q) => $q->where('b.sucursal_id', $sucursalId))
            ->groupBy('r.id')
            ->orderByDesc('total_ventas')
            ->select('r.origen', 'r.destino', DB::raw('SUM(b.precio_final) as total_ventas'), DB::raw('COUNT(b.id) as cantidad_pasajes'))
            ->limit(1)
            ->first();
    }

    public function obtenerTendenciaSemanal(?int $sucursalId = null)
    {
        return DB::table('boletos')->where('estado', 'vendido')
            ->whereRaw('fecha_reserva >= DATE(NOW()) - INTERVAL 7 DAY')
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->groupBy(DB::raw('DATE(fecha_reserva)'))
            ->orderBy('fecha')
            ->select(DB::raw('DATE(fecha_reserva) as fecha'), DB::raw('SUM(precio_final) as total'))
            ->get();
    }

    public function obtenerDesgloseFinanciero(?string $fechaInicio = null, ?string $fechaFin = null, ?int $sucursalId = null)
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
        if ($sucursalId) {
            $q->where('b.sucursal_id', $sucursalId);
        }

        return $q->get();
    }

    /** `$sucursalId`: viajes que salen desde esa terminal (null = todas, solo Administrador/Supervisor). */
    public function buscarHistorico(string $fechaInicio, string $fechaFin, $rutaId = null, ?int $sucursalId = null)
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
        if ($sucursalId) {
            $q->where('v.terminal_origen_id', $sucursalId);
        }

        return $q->orderByDesc('v.fecha_salida')->orderBy('v.hora_salida')->get();
    }

    /** `$sucursalId`: solo boletos vendidos desde esa sucursal (null = todas, solo Administrador/Supervisor). */
    public function buscarPasajero(string $criterio, ?int $sucursalId = null)
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
            ->when($sucursalId, fn ($q) => $q->where('b.sucursal_id', $sucursalId))
            ->select('c.id', 'c.nombres', 'c.apellidos', 'c.numero_documento', 'v.fecha_salida', 'v.hora_salida',
                DB::raw("CONCAT(r.origen, ' - ', r.destino) as ruta"), 'b.numero_asiento', 'b.precio_final', 'b.estado', DB::raw('v.id as viaje_id'))
            ->orderByDesc('v.fecha_salida')
            ->get();
    }

    /** Bitácora de cancelaciones de boletos ya pagados: quién canceló, por qué, y si hubo devolución. */
    public function obtenerCancelaciones(?string $fechaInicio, ?string $fechaFin, ?int $sucursalId = null, bool $soloPendientes = false)
    {
        $q = DB::table('cancelaciones_boletos as cb')
            ->join('boletos as b', 'cb.boleto_id', '=', 'b.id')
            ->join('clientes as c', 'b.cliente_id', '=', 'c.id')
            ->join('usuarios as u', 'cb.usuario_id', '=', 'u.id')
            ->leftJoin('usuarios as ud', 'cb.usuario_devolucion_id', '=', 'ud.id')
            ->leftJoin('viajes as v', 'b.viaje_id', '=', 'v.id')
            ->leftJoin('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->select([
                'cb.id', 'cb.boleto_id', 'cb.monto', 'cb.devuelto', 'cb.metodo_devolucion', 'cb.motivo', 'cb.fecha_creacion', 'cb.fecha_devolucion',
                'b.codigo_boleto', 'b.numero_asiento', 'b.metodo_pago',
                DB::raw("CONCAT(c.nombres, ' ', c.apellidos) as pasajero"),
                DB::raw("CONCAT(u.nombres, ' ', u.apellidos) as cancelado_por"),
                DB::raw("CONCAT(ud.nombres, ' ', ud.apellidos) as devuelto_por"),
                DB::raw("COALESCE(CONCAT(r.origen, ' - ', r.destino), 'N/D') as ruta"),
            ]);

        if ($fechaInicio && $fechaFin) {
            $q->whereRaw('DATE(cb.fecha_creacion) BETWEEN ? AND ?', [$fechaInicio, $fechaFin]);
        }
        if ($sucursalId) {
            $q->where('b.sucursal_id', $sucursalId);
        }
        if ($soloPendientes) {
            $q->where('cb.devuelto', false);
        }

        return $q->orderByDesc('cb.fecha_creacion')->get();
    }
}
