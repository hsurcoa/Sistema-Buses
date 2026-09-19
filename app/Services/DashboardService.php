<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Datos del tablero principal. Reescritura Eloquent de
 * `legacy/app/models/DashboardModel.php` (Fase 2 original; reescrito a
 * Eloquent al cerrar la sesion — ver informe de fin de sesion).
 *
 * Todo se calcula para un periodo [desde, hasta] y una sucursal (null = todas).
 */
class DashboardService
{
    /** Ingresos y boletos vendidos del periodo, con desglose efectivo / QR fijo / Libélula. */
    public function resumenVentas(string $desde, string $hasta, $sucursal): object
    {
        $q = DB::table('boletos as b')
            ->leftJoin('libelula_cobros as lc', function ($j) {
                $j->on('lc.boleto_id', '=', 'b.id')->whereIn('lc.estado', ['pagado', 'pagado_sin_aplicar']);
            })
            ->where('b.estado', 'vendido')
            ->whereRaw('DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) BETWEEN ? AND ?', [$desde, $hasta])
            ->selectRaw("COUNT(*) AS boletos,
                COALESCE(SUM(b.precio_final), 0) AS ingresos,
                COALESCE(SUM(CASE WHEN b.metodo_pago = 'QR' THEN b.precio_final ELSE 0 END), 0) AS ingresos_qr,
                COALESCE(SUM(CASE WHEN b.metodo_pago = 'QR' AND lc.id IS NOT NULL THEN b.precio_final ELSE 0 END), 0) AS ingresos_qr_libelula,
                COALESCE(SUM(CASE WHEN b.metodo_pago = 'QR' AND lc.id IS NULL THEN b.precio_final ELSE 0 END), 0) AS ingresos_qr_fijo,
                COALESCE(SUM(CASE WHEN b.metodo_pago <> 'QR' THEN b.precio_final ELSE 0 END), 0) AS ingresos_efectivo");
        if ($sucursal) {
            $q->where('b.sucursal_id', (int) $sucursal);
        }

        return $q->first();
    }

    /** Ingresos por dia (efectivo y QR) para el grafico. */
    public function ventasPorDia(string $desde, string $hasta, $sucursal)
    {
        $q = DB::table('boletos as b')
            ->where('b.estado', 'vendido')
            ->whereRaw('DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) BETWEEN ? AND ?', [$desde, $hasta])
            ->selectRaw("DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) AS dia, COUNT(*) AS boletos,
                SUM(CASE WHEN b.metodo_pago = 'QR' THEN b.precio_final ELSE 0 END) AS qr,
                SUM(CASE WHEN b.metodo_pago <> 'QR' THEN b.precio_final ELSE 0 END) AS efectivo")
            ->groupBy('dia')->orderBy('dia');
        if ($sucursal) {
            $q->where('b.sucursal_id', (int) $sucursal);
        }

        return $q->get();
    }

    /** Con todas las sucursales: ingresos por sucursal. Con una sucursal: por vendedor. */
    public function ventasPorGrupo(string $desde, string $hasta, $sucursal)
    {
        if ($sucursal) {
            return DB::table('boletos as b')
                ->join('usuarios as u', 'u.id', '=', 'b.usuario_vendedor_id')
                ->where('b.estado', 'vendido')->where('b.sucursal_id', (int) $sucursal)
                ->whereRaw('DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) BETWEEN ? AND ?', [$desde, $hasta])
                ->groupBy('u.id', 'u.nombres', 'u.apellidos')->orderByDesc('ingresos')
                ->selectRaw("CONCAT(u.nombres, ' ', u.apellidos) AS nombre, COUNT(*) AS boletos, SUM(b.precio_final) AS ingresos")
                ->get();
        }

        return DB::table('boletos as b')
            ->leftJoin('terminales as t', 't.id', '=', 'b.sucursal_id')
            ->where('b.estado', 'vendido')
            ->whereRaw('DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) BETWEEN ? AND ?', [$desde, $hasta])
            ->groupBy('b.sucursal_id', 't.nombre_sede')->orderByDesc('ingresos')
            ->selectRaw("COALESCE(t.nombre_sede, 'Sin sucursal') AS nombre, COUNT(*) AS boletos, SUM(b.precio_final) AS ingresos")
            ->get();
    }

    public function rutasMasVendidas(string $desde, string $hasta, $sucursal, int $limite = 6)
    {
        $q = DB::table('boletos as b')
            ->join('viajes as v', 'v.id', '=', 'b.viaje_id')
            ->join('rutas as r', 'r.id', '=', 'v.ruta_id')
            ->leftJoin('rutas_paradas as rp', 'rp.id', '=', 'b.parada_id')
            ->leftJoin('rutas_paradas as rps', 'rps.id', '=', 'b.parada_subida_id')
            ->where('b.estado', 'vendido')
            ->whereRaw('DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) BETWEEN ? AND ?', [$desde, $hasta])
            ->groupBy('tramo')->orderByDesc('boletos')->limit($limite)
            ->selectRaw("CONCAT(COALESCE(rps.nombre_parada, r.origen), ' → ', COALESCE(rp.nombre_parada, r.destino)) AS tramo,
                COUNT(*) AS boletos, SUM(b.precio_final) AS ingresos");
        if ($sucursal) {
            $q->where('b.sucursal_id', (int) $sucursal);
        }

        return $q->get();
    }

    /** Ocupacion promedio de los viajes que salen en el periodo. */
    public function ocupacion(string $desde, string $hasta, $sucursal): object
    {
        $sub = DB::table('viajes as v')
            ->leftJoin('tipos_buses as tb', 'tb.id', '=', 'v.tipo_bus_id')
            ->whereRaw('DATE(v.fecha_salida) BETWEEN ? AND ?', [$desde, $hasta])
            ->whereRaw("LOWER(v.estado) NOT IN ('cancelado', 'inactivo')")
            ->select('v.id', DB::raw('COALESCE(tb.capacidad, 0) AS capacidad'),
                DB::raw("(SELECT COUNT(*) FROM boletos b WHERE b.viaje_id = v.id AND b.estado = 'vendido') AS ocupados"));
        if ($sucursal) {
            $sub->where('v.terminal_origen_id', (int) $sucursal);
        }

        return DB::query()->fromSub($sub, 'x')
            ->selectRaw('COUNT(*) AS viajes, COALESCE(SUM(x.ocupados), 0) AS asientos_ocupados, COALESCE(SUM(x.capacidad), 0) AS asientos_totales')
            ->first();
    }

    /** Viajes sin finalizar ordenados por salida; los de fecha pasada se marcan como atrasados. */
    public function proximosViajes($sucursal, int $limite = 8)
    {
        $q = DB::table('viajes as v')
            ->join('rutas as r', 'r.id', '=', 'v.ruta_id')
            ->leftJoin('vehiculos as ve', 've.id', '=', 'v.bus_id')
            ->leftJoin('tipos_buses as tb', 'tb.id', '=', 'v.tipo_bus_id')
            ->leftJoin('personal as p', 'p.id', '=', 'v.chofer_id')
            ->leftJoin('terminales as t', 't.id', '=', 'v.terminal_origen_id')
            ->whereRaw("LOWER(v.estado) NOT IN ('finalizado', 'cancelado', 'inactivo')")
            ->orderBy('v.fecha_salida')->limit($limite)
            ->select('v.id', 'v.fecha_salida', 'v.hora_salida', 'v.estado', 'v.precio_base', 'r.origen', 'r.destino',
                've.placa', DB::raw('COALESCE(tb.capacidad, 0) AS capacidad'),
                DB::raw("CONCAT(p.nombres, ' ', p.apellidos) AS chofer"), DB::raw('t.nombre_sede AS terminal_origen'),
                DB::raw("(SELECT COUNT(*) FROM boletos b WHERE b.viaje_id = v.id AND b.estado = 'vendido') AS vendidos"),
                DB::raw("(SELECT COUNT(*) FROM boletos b WHERE b.viaje_id = v.id AND b.estado = 'reservado') AS reservados"),
                DB::raw('(v.fecha_salida < NOW()) AS atrasado'));
        if ($sucursal) {
            $q->where('v.terminal_origen_id', (int) $sucursal);
        }

        return $q->get();
    }

    /** Cajas abiertas ahora con su efectivo esperado. */
    public function cajasAbiertas($sucursal)
    {
        $q = DB::table('cajas_sesiones as cs')
            ->join('usuarios as u', 'u.id', '=', 'cs.usuario_id')
            ->leftJoin('terminales as t', 't.id', '=', 'cs.sucursal_id')
            ->where('cs.estado', 'ABIERTA')
            ->orderBy('cs.fecha_apertura')
            ->select('cs.id', 'cs.fecha_apertura', 'cs.monto_inicial',
                DB::raw("CONCAT(u.nombres, ' ', u.apellidos) AS usuario"),
                DB::raw("COALESCE(t.nombre_sede, 'Sin sucursal') AS sucursal"),
                DB::raw('TIMESTAMPDIFF(HOUR, cs.fecha_apertura, NOW()) AS horas'),
                DB::raw("cs.monto_inicial
                    + COALESCE((SELECT SUM(m.monto) FROM movimientos_caja m WHERE m.sesion_id = cs.id AND m.tipo_movimiento = 'INGRESO' AND m.origen_modulo <> 'APERTURA' AND m.metodo_pago <> 'QR'), 0)
                    - COALESCE((SELECT SUM(m.monto) FROM movimientos_caja m WHERE m.sesion_id = cs.id AND m.tipo_movimiento = 'EGRESO'), 0) AS efectivo_esperado"),
                DB::raw("COALESCE((SELECT SUM(m.monto) FROM movimientos_caja m WHERE m.sesion_id = cs.id AND m.tipo_movimiento = 'INGRESO' AND m.metodo_pago = 'QR'), 0) AS cobrado_qr"));
        if ($sucursal) {
            $q->where('cs.sucursal_id', (int) $sucursal);
        }

        return $q->get();
    }

    public function resumenEncomiendas(string $desde, string $hasta, $sucursal): object
    {
        $condOrigen = $sucursal ? ' AND e.sucursal_origen_id = ?' : '';
        $condDestino = $sucursal ? ' AND e.sucursal_destino_id = ?' : '';

        $bindings = [$desde, $hasta];
        if ($sucursal) {
            $bindings[] = (int) $sucursal;
        }
        $bindings[] = $desde;
        $bindings[] = $hasta;
        if ($sucursal) {
            $bindings[] = (int) $sucursal;
        }
        if ($sucursal) {
            $bindings[] = (int) $sucursal;
        }
        if ($sucursal) {
            $bindings[] = (int) $sucursal;
        }

        $sql = "SELECT
                    (SELECT COUNT(*) FROM encomiendas e WHERE DATE(e.fecha_registro) BETWEEN ? AND ?{$condOrigen}) AS registradas,
                    (SELECT COALESCE(SUM(e.total_pagar), 0) FROM encomiendas e WHERE DATE(e.fecha_registro) BETWEEN ? AND ?{$condOrigen}) AS monto,
                    (SELECT COUNT(*) FROM encomiendas e WHERE e.estado IN ('REGISTRADO', 'EN_ALMACEN_ORIGEN'){$condOrigen}) AS por_despachar,
                    (SELECT COUNT(*) FROM encomiendas e WHERE e.estado = 'EN_DESTINO'{$condDestino}) AS por_entregar";

        return DB::selectOne($sql, $bindings);
    }

    /** Fecha de la ultima venta (para orientar cuando el periodo no tiene datos). */
    public function ultimaVenta($sucursal): ?string
    {
        $q = DB::table('boletos as b')->where('b.estado', 'vendido');
        if ($sucursal) {
            $q->where('b.sucursal_id', (int) $sucursal);
        }

        return $q->selectRaw('MAX(COALESCE(b.fecha_pago, b.fecha_reserva)) AS fecha')->value('fecha');
    }

    /**
     * Cosas que requieren accion, cada una con cantidad, texto y enlace.
     * Solo se devuelven las que tienen algo pendiente.
     */
    public function pendientes($sucursal, bool $esAdmin): array
    {
        $items = [];
        $agregar = function (string $nivel, int $cantidad, string $texto, string $url, string $accion) use (&$items) {
            if ($cantidad > 0) {
                $items[] = compact('nivel', 'cantidad', 'texto', 'url', 'accion');
            }
        };

        $qViajes = DB::table('viajes as v')->where('v.fecha_salida', '<', now())
            ->whereRaw("LOWER(v.estado) NOT IN ('finalizado', 'cancelado', 'inactivo')");
        if ($sucursal) {
            $qViajes->where('v.terminal_origen_id', (int) $sucursal);
        }
        $agregar('critico', $qViajes->count(), 'viaje(s) con fecha de salida pasada siguen abiertos para la venta', URLROOT.'/ventas/crear_ruta', 'Revisar viajes');

        $qReservas = DB::table('boletos as b')->join('viajes as v', 'v.id', '=', 'b.viaje_id')
            ->where('b.estado', 'reservado')
            ->where(function ($q) {
                $q->where('b.metodo_pago', '<>', 'QR')->orWhereNull('b.fecha_expiracion_reserva');
            })
            ->where(function ($q) {
                $q->whereRaw("LOWER(v.estado) IN ('finalizado', 'cancelado')")
                    ->orWhere('b.fecha_reserva', '<', now()->subDays(2));
            });
        if ($sucursal) {
            $qReservas->where('b.sucursal_id', (int) $sucursal);
        }
        $agregar('alto', $qReservas->count(), 'reserva(s) sin cobrar de hace más de 2 días o de viajes ya cerrados bloquean asientos', URLROOT.'/ventas/venta_pasajes', 'Ir a ventas');

        $qCaja = DB::table('cajas_sesiones as cs')->where('cs.estado', 'ABIERTA')->where('cs.fecha_apertura', '<', now()->subHours(14));
        if ($sucursal) {
            $qCaja->where('cs.sucursal_id', (int) $sucursal);
        }
        $agregar('alto', $qCaja->count(), 'caja(s) abiertas hace más de 14 horas (turno sin cerrar)', URLROOT.'/caja', 'Ver caja');

        $qEnc = DB::table('encomiendas')->where('estado', 'EN_DESTINO');
        if ($sucursal) {
            $qEnc->where('sucursal_destino_id', (int) $sucursal);
        }
        $agregar('medio', $qEnc->count(), 'encomienda(s) llegaron y esperan entrega', URLROOT.'/encomiendas', 'Ver encomiendas');

        if ($esAdmin) {
            $sinTipo = DB::table('vehiculos')->where('estado', 1)->whereNull('tipo_bus_id')->count();
            $agregar('medio', $sinTipo, 'bus(es) sin tipo de bus: no se pueden programar viajes con ellos', URLROOT.'/admin/registrar_buses', 'Completar flota');

            $sinSucursal = DB::table('usuarios as u')->join('roles as r', 'r.id', '=', 'u.rol_id')
                ->where('u.estado', 'activo')->whereNull('u.sucursal_id')
                ->whereNotIn('r.nombre', ['Administrador', 'Supervisor', 'Chofer', 'Copiloto'])
                ->count();
            $agregar('medio', $sinSucursal, 'usuario(s) sin sucursal asignada no pueden abrir caja', URLROOT.'/admin/usuarios', 'Asignar sucursal');

            $automaticas = DB::table('usuarios')->where('estado', 'activo')
                ->where(function ($q) {
                    $q->where('username', 'like', 'chofer\\_%')
                        ->orWhere('email', 'like', '%.sistema.temp')
                        ->orWhere('email', 'like', '%@test.com');
                })
                ->count();
            $agregar('medio', $automaticas, 'cuenta(s) automáticas de choferes siguen activas', URLROOT.'/admin/usuarios', 'Revisar usuarios');
        }

        return $items;
    }
}
