<?php

namespace App\Services;

use App\Models\CajaSesion;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/** Reescritura Eloquent de `legacy/app/models/CajaModel.php`. */
class CajaService
{
    public function verificarCajaAbierta(int $usuarioId): ?object
    {
        return DB::table('cajas_sesiones as cs')
            ->leftJoin('terminales as t', 't.id', '=', 'cs.sucursal_id')
            ->where('cs.usuario_id', $usuarioId)->where('cs.estado', 'ABIERTA')
            ->select('cs.*', DB::raw('t.nombre_sede AS sucursal_nombre'))
            ->first();
    }

    public function abrirCaja(int $usuarioId, $montoInicial, ?int $sucursalId = null): bool
    {
        try {
            DB::transaction(function () use ($usuarioId, $montoInicial, $sucursalId) {
                $sesionId = CajaSesion::create([
                    'usuario_id' => $usuarioId,
                    'sucursal_id' => $sucursalId,
                    'monto_inicial' => $montoInicial,
                    'fecha_apertura' => now(),
                ])->id;

                if ($montoInicial > 0) {
                    $this->registrarMovimiento($sesionId, 'INGRESO', 'APERTURA', null, $montoInicial, 'Monto inicial de apertura');
                }
            });

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function registrarMovimiento(int $sesionId, string $tipo, string $origen, ?int $referenciaId, $monto, string $descripcion): bool
    {
        return (bool) DB::table('movimientos_caja')->insert([
            'sesion_id' => $sesionId,
            'tipo_movimiento' => $tipo,
            'origen_modulo' => $origen,
            'referencia_id' => $referenciaId,
            'monto' => $monto,
            'descripcion' => $descripcion,
            'fecha_creacion' => now(),
        ]);
    }

    /**
     * JOIN para distinguir, dentro de los ingresos con metodo_pago='QR', cuales
     * pasaron por la pasarela Libélula (hay una fila en libelula_cobros ya
     * pagada) de los que se cobraron con el QR fijo cargado en Configuración
     * (confirmacion manual, sin fila en libelula_cobros).
     */
    private function joinLibelula($query): void
    {
        $query->leftJoin('libelula_cobros as lc', function ($j) {
            $j->on('lc.boleto_id', '=', 'movimientos_caja.referencia_id')
                ->where('movimientos_caja.origen_modulo', '=', 'PASAJE')
                ->whereIn('lc.estado', ['pagado', 'pagado_sin_aplicar']);
        });
    }

    /**
     * - La apertura se guarda como movimiento INGRESO/APERTURA y ademas en
     *   cajas_sesiones.monto_inicial: sumarla en los ingresos la contaba DOS veces.
     * - El efectivo esperado solo cuenta ingresos en EFECTIVO: los cobros QR van
     *   directo a la cuenta del dueño o de Libélula, y se informan aparte.
     * - Dentro de "QR" se distingue Libélula (confirmacion automatica) del QR
     *   fijo (confirmacion manual por el cajero).
     */
    public function obtenerResumenSesion(int $sesionId): object
    {
        $q = DB::table('movimientos_caja')->where('sesion_id', $sesionId);
        $this->joinLibelula($q);

        return $q->selectRaw("
                COALESCE(SUM(CASE WHEN movimientos_caja.tipo_movimiento = 'INGRESO' AND movimientos_caja.origen_modulo <> 'APERTURA' THEN movimientos_caja.monto ELSE 0 END), 0) as total_ingresos,
                COALESCE(SUM(CASE WHEN movimientos_caja.tipo_movimiento = 'INGRESO' AND movimientos_caja.origen_modulo <> 'APERTURA' AND movimientos_caja.metodo_pago = 'QR' THEN movimientos_caja.monto ELSE 0 END), 0) as total_qr,
                COALESCE(SUM(CASE WHEN movimientos_caja.tipo_movimiento = 'INGRESO' AND movimientos_caja.origen_modulo <> 'APERTURA' AND movimientos_caja.metodo_pago = 'QR' AND lc.id IS NOT NULL THEN movimientos_caja.monto ELSE 0 END), 0) as total_qr_libelula,
                COALESCE(SUM(CASE WHEN movimientos_caja.tipo_movimiento = 'INGRESO' AND movimientos_caja.origen_modulo <> 'APERTURA' AND movimientos_caja.metodo_pago = 'QR' AND lc.id IS NULL THEN movimientos_caja.monto ELSE 0 END), 0) as total_qr_fijo,
                COALESCE(SUM(CASE WHEN movimientos_caja.tipo_movimiento = 'INGRESO' AND movimientos_caja.origen_modulo <> 'APERTURA' AND movimientos_caja.metodo_pago <> 'QR' THEN movimientos_caja.monto ELSE 0 END), 0) as total_efectivo,
                COALESCE(SUM(CASE WHEN movimientos_caja.tipo_movimiento = 'EGRESO' THEN movimientos_caja.monto ELSE 0 END), 0) as total_egresos,
                (SELECT monto_inicial FROM cajas_sesiones WHERE id = ?) as monto_inicial
            ", [$sesionId])
            ->first();
    }

    /**
     * Movimientos de la sesión actual con la "vía" de pago resuelta
     * (Efectivo / QR fijo / Libélula), para la tabla en vivo del dashboard.
     * Filtros opcionales: tipo ('INGRESO'|'EGRESO') y via ('EFECTIVO'|'QR_FIJO'|'LIBELULA').
     */
    public function obtenerMovimientosSesion(int $sesionId, ?string $tipo = null, ?string $via = null)
    {
        $q = DB::table('movimientos_caja')->where('movimientos_caja.sesion_id', $sesionId);
        $this->joinLibelula($q);

        $q->selectRaw("
                movimientos_caja.id, movimientos_caja.tipo_movimiento, movimientos_caja.origen_modulo,
                movimientos_caja.monto, movimientos_caja.descripcion, movimientos_caja.metodo_pago,
                movimientos_caja.fecha_creacion,
                CASE
                    WHEN movimientos_caja.metodo_pago <> 'QR' THEN 'EFECTIVO'
                    WHEN lc.id IS NOT NULL THEN 'LIBELULA'
                    ELSE 'QR_FIJO'
                END as via_pago
            ")
            ->where('movimientos_caja.origen_modulo', '<>', 'APERTURA');

        if ($tipo) {
            $q->where('movimientos_caja.tipo_movimiento', $tipo);
        }
        if ($via) {
            $q->havingRaw('via_pago = ?', [$via]);
        }

        return $q->orderByDesc('movimientos_caja.fecha_creacion')->get();
    }

    public function cerrarCaja(int $sesionId, $montoReal): bool
    {
        $resumen = $this->obtenerResumenSesion($sesionId);
        $sistema = $resumen->monto_inicial + $resumen->total_efectivo - $resumen->total_egresos;
        $diferencia = $montoReal - $sistema;

        return (bool) CajaSesion::where('id', $sesionId)->update([
            'fecha_cierre' => now(),
            'monto_final_sistema' => $sistema,
            'monto_final_real' => $montoReal,
            'diferencia' => $diferencia,
            'estado' => 'CERRADA',
        ]);
    }

    /** Contraseña de algun administrador activo (rol_id = 1). */
    public function validarCredencialesAdmin(string $password): bool
    {
        $admins = Usuario::where('rol_id', 1)->where('estado', 1)->pluck('password');
        foreach ($admins as $hash) {
            if (password_verify($password, $hash)) {
                return true;
            }
        }

        return false;
    }

    public function obtenerSesionPorId(int $id): ?object
    {
        return DB::table('cajas_sesiones as cs')
            ->join('usuarios as u', 'cs.usuario_id', '=', 'u.id')
            ->where('cs.id', $id)
            ->select('cs.*', DB::raw("CONCAT(u.nombres, ' ', u.apellidos) as cajero_nombre"),
                DB::raw("(SELECT COALESCE(SUM(monto), 0) FROM movimientos_caja WHERE sesion_id = cs.id AND tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA') as total_ingresos"),
                DB::raw("(SELECT COALESCE(SUM(monto), 0) FROM movimientos_caja WHERE sesion_id = cs.id AND tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA' AND metodo_pago = 'QR') as total_qr"),
                DB::raw("(SELECT SUM(monto) FROM movimientos_caja WHERE sesion_id = cs.id AND tipo_movimiento = 'EGRESO') as total_egresos"))
            ->first();
    }

    public function obtenerReporteIngresos(string $fechaInicio, string $fechaFin)
    {
        $q = DB::table('movimientos_caja as m')
            ->join('cajas_sesiones as s', 'm.sesion_id', '=', 's.id')
            ->join('usuarios as u', 's.usuario_id', '=', 'u.id')
            ->leftJoin('libelula_cobros as lc', function ($j) {
                $j->on('lc.boleto_id', '=', 'm.referencia_id')
                    ->where('m.origen_modulo', '=', 'PASAJE')
                    ->whereIn('lc.estado', ['pagado', 'pagado_sin_aplicar']);
            })
            ->where('m.tipo_movimiento', 'INGRESO')
            ->whereBetween('m.fecha_creacion', [$fechaInicio.' 00:00:00', $fechaFin.' 23:59:59']);

        return $q->selectRaw("
                m.id, m.fecha_creacion, m.origen_modulo as tipo, m.descripcion, m.monto, m.metodo_pago,
                CONCAT(u.nombres, ' ', u.apellidos) as usuario,
                CASE
                    WHEN m.metodo_pago <> 'QR' THEN 'EFECTIVO'
                    WHEN lc.id IS NOT NULL THEN 'LIBELULA'
                    ELSE 'QR_FIJO'
                END as via_pago
            ")
            ->orderByDesc('m.fecha_creacion')
            ->get();
    }

    public function obtenerMovimientosPorSesion(int $sesionId)
    {
        return DB::table('movimientos_caja')->where('sesion_id', $sesionId)
            ->select('id', 'tipo_movimiento', 'origen_modulo', 'monto', 'descripcion', 'fecha_creacion')
            ->orderBy('fecha_creacion')->get();
    }

    /** Subquery reutilizable: suma de movimientos INGRESO de una sesión, vía un metodo/origen de pago dado. */
    private function subqueryIngresoPorVia(string $condicionVia): string
    {
        return "(SELECT COALESCE(SUM(m.monto), 0) FROM movimientos_caja m
                LEFT JOIN libelula_cobros lc ON lc.boleto_id = m.referencia_id AND m.origen_modulo = 'PASAJE' AND lc.estado IN ('pagado', 'pagado_sin_aplicar')
                WHERE m.sesion_id = cs.id AND m.tipo_movimiento = 'INGRESO' AND m.origen_modulo <> 'APERTURA' AND {$condicionVia})";
    }

    public function obtenerSesionesCerradas(string $fechaInicio, string $fechaFin, $sucursalFiltro = null)
    {
        $q = DB::table('cajas_sesiones as cs')
            ->join('usuarios as u', 'cs.usuario_id', '=', 'u.id')
            ->leftJoin('terminales as t', 't.id', '=', 'cs.sucursal_id')
            ->where('cs.estado', 'CERRADA')
            ->whereBetween('cs.fecha_cierre', [$fechaInicio.' 00:00:00', $fechaFin.' 23:59:59'])
            ->select('cs.id', 'cs.usuario_id', 'cs.sucursal_id', DB::raw('t.nombre_sede AS sucursal_nombre'),
                DB::raw("CONCAT(u.nombres, ' ', u.apellidos) as cajero_nombre"),
                'cs.fecha_apertura', 'cs.fecha_cierre', 'cs.monto_inicial', 'cs.monto_final_sistema', 'cs.monto_final_real', 'cs.diferencia',
                DB::raw("(SELECT COALESCE(SUM(monto), 0) FROM movimientos_caja WHERE sesion_id = cs.id AND tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA') as total_ingresos"),
                DB::raw("(SELECT SUM(monto) FROM movimientos_caja WHERE sesion_id = cs.id AND tipo_movimiento = 'EGRESO') as total_egresos"),
                DB::raw($this->subqueryIngresoPorVia("m.metodo_pago <> 'QR'").' as total_efectivo'),
                DB::raw($this->subqueryIngresoPorVia("m.metodo_pago = 'QR' AND lc.id IS NULL").' as total_qr_fijo'),
                DB::raw($this->subqueryIngresoPorVia("m.metodo_pago = 'QR' AND lc.id IS NOT NULL").' as total_qr_libelula'));

        if ($sucursalFiltro !== null) {
            $q->where('cs.sucursal_id', $sucursalFiltro);
        }

        return $q->orderByDesc('cs.fecha_cierre')->get();
    }

    public function obtenerEstadisticasPeriodo(string $fechaInicio, string $fechaFin, $sucursalFiltro = null): object
    {
        $q = DB::table('cajas_sesiones as cs')
            ->where('cs.estado', 'CERRADA')
            ->whereBetween('cs.fecha_cierre', [$fechaInicio.' 00:00:00', $fechaFin.' 23:59:59']);

        if ($sucursalFiltro !== null) {
            $q->where('cs.sucursal_id', $sucursalFiltro);
        }

        $stats = $q->selectRaw('COUNT(*) as total_sesiones, SUM(monto_inicial) as suma_inicial, SUM(monto_final_sistema) as suma_sistema,
                               SUM(monto_final_real) as suma_real, SUM(diferencia) as suma_diferencias, AVG(monto_final_sistema) as promedio_sistema')
            ->first();

        $mq = DB::table('movimientos_caja as m')
            ->join('cajas_sesiones as cs', 'cs.id', '=', 'm.sesion_id')
            ->leftJoin('libelula_cobros as lc', function ($j) {
                $j->on('lc.boleto_id', '=', 'm.referencia_id')
                    ->where('m.origen_modulo', '=', 'PASAJE')
                    ->whereIn('lc.estado', ['pagado', 'pagado_sin_aplicar']);
            })
            ->where('cs.estado', 'CERRADA')
            ->where('m.tipo_movimiento', 'INGRESO')
            ->where('m.origen_modulo', '<>', 'APERTURA')
            ->whereBetween('cs.fecha_cierre', [$fechaInicio.' 00:00:00', $fechaFin.' 23:59:59']);

        if ($sucursalFiltro !== null) {
            $mq->where('cs.sucursal_id', $sucursalFiltro);
        }

        $vias = $mq->selectRaw("
                COALESCE(SUM(CASE WHEN m.metodo_pago <> 'QR' THEN m.monto ELSE 0 END), 0) as suma_efectivo,
                COALESCE(SUM(CASE WHEN m.metodo_pago = 'QR' AND lc.id IS NULL THEN m.monto ELSE 0 END), 0) as suma_qr_fijo,
                COALESCE(SUM(CASE WHEN m.metodo_pago = 'QR' AND lc.id IS NOT NULL THEN m.monto ELSE 0 END), 0) as suma_qr_libelula
            ")->first();

        $stats->suma_efectivo = $vias->suma_efectivo;
        $stats->suma_qr_fijo = $vias->suma_qr_fijo;
        $stats->suma_qr_libelula = $vias->suma_qr_libelula;

        return $stats;
    }
}
