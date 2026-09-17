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
     * - La apertura se guarda como movimiento INGRESO/APERTURA y ademas en
     *   cajas_sesiones.monto_inicial: sumarla en los ingresos la contaba DOS veces.
     * - El efectivo esperado solo cuenta ingresos en EFECTIVO: los cobros QR van
     *   directo a la cuenta del dueño y se informan aparte.
     */
    public function obtenerResumenSesion(int $sesionId): object
    {
        return DB::table('movimientos_caja')
            ->where('sesion_id', $sesionId)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA' THEN monto ELSE 0 END), 0) as total_ingresos,
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA' AND metodo_pago = 'QR' THEN monto ELSE 0 END), 0) as total_qr,
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA' AND metodo_pago <> 'QR' THEN monto ELSE 0 END), 0) as total_efectivo,
                COALESCE(SUM(CASE WHEN tipo_movimiento = 'EGRESO' THEN monto ELSE 0 END), 0) as total_egresos,
                (SELECT monto_inicial FROM cajas_sesiones WHERE id = ?) as monto_inicial
            ", [$sesionId])
            ->first();
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
        return DB::table('movimientos_caja as m')
            ->join('cajas_sesiones as s', 'm.sesion_id', '=', 's.id')
            ->join('usuarios as u', 's.usuario_id', '=', 'u.id')
            ->where('m.tipo_movimiento', 'INGRESO')
            ->whereBetween('m.fecha_creacion', [$fechaInicio.' 00:00:00', $fechaFin.' 23:59:59'])
            ->select('m.id', 'm.fecha_creacion', DB::raw('m.origen_modulo as tipo'), 'm.descripcion', 'm.monto', DB::raw("CONCAT(u.nombres, ' ', u.apellidos) as usuario"))
            ->orderByDesc('m.fecha_creacion')
            ->get();
    }

    public function obtenerMovimientosPorSesion(int $sesionId)
    {
        return DB::table('movimientos_caja')->where('sesion_id', $sesionId)
            ->select('id', 'tipo_movimiento', 'origen_modulo', 'monto', 'descripcion', 'fecha_creacion')
            ->orderBy('fecha_creacion')->get();
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
                DB::raw("(SELECT SUM(monto) FROM movimientos_caja WHERE sesion_id = cs.id AND tipo_movimiento = 'EGRESO') as total_egresos"));

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

        return $q->selectRaw('COUNT(*) as total_sesiones, SUM(monto_inicial) as suma_inicial, SUM(monto_final_sistema) as suma_sistema,
                               SUM(monto_final_real) as suma_real, SUM(diferencia) as suma_diferencias, AVG(monto_final_sistema) as promedio_sistema')
            ->first();
    }
}
