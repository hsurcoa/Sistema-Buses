<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Reescritura Eloquent de `legacy/app/models/AsientoModel.php`.
 *
 * ADVERTENCIA (preexistente, no introducida por esta migracion): esta clase
 * consulta las tablas `asientos`, `ventas` y `reservas_temporales`, que NO
 * EXISTEN en el esquema actual de `sistema_transportes` (verificado con
 * `SHOW TABLES`). El mapa de asientos que realmente usa Ventas hoy vive en
 * la columna JSON `tipos_buses.configuracion_asientos` (ver `RutaService`),
 * no en estas tablas. Todo metodo de aqui ya fallaba con "Base table or
 * view not found" en el legacy; se preserva el mismo comportamiento en vez
 * de inventar una implementacion nueva sobre tablas que no existen.
 */
class AsientoService
{
    public function obtenerAsientosPorBus(int $busId, ?int $piso = null)
    {
        $q = DB::table('asientos as a')
            ->join('tipos_buses as tb', 'a.tipo_bus_id', '=', 'tb.id')
            ->where('a.tipo_bus_id', $busId)
            ->select('a.id', 'a.numero', 'a.piso', 'a.fila', 'a.columna', 'a.estado', 'a.tipo', 'a.precio_adicional', DB::raw('tb.nombre as tipo_bus'));

        if ($piso !== null) {
            $q->where('a.piso', $piso);
        }

        return $q->orderBy('a.piso')->orderBy('a.fila')->orderBy('a.columna')->get();
    }

    public function obtenerAsientosDisponibles(int $rutaId, string $fecha, int $busId)
    {
        return DB::table('asientos as a')
            ->leftJoin('ventas as v', function ($j) use ($rutaId, $fecha) {
                $j->on('a.id', '=', 'v.asiento_id')
                    ->where('v.ruta_id', $rutaId)
                    ->whereRaw('DATE(v.fecha_viaje) = ?', [$fecha])
                    ->where('v.estado', '<>', 'cancelado');
            })
            ->where('a.tipo_bus_id', $busId)
            ->selectRaw("a.id, a.numero, a.piso, a.fila, a.columna, a.tipo, a.precio_adicional, CASE WHEN v.id IS NOT NULL THEN 'ocupado' ELSE 'disponible' END as estado")
            ->orderBy('a.piso')->orderBy('a.fila')->orderBy('a.columna')
            ->get();
    }

    public function verificarDisponibilidad(int $asientoId, int $rutaId, string $fecha): bool
    {
        $total = DB::table('ventas')->where('asiento_id', $asientoId)->where('ruta_id', $rutaId)
            ->whereRaw('DATE(fecha_viaje) = ?', [$fecha])->where('estado', '<>', 'cancelado')->count();

        return $total == 0;
    }

    public function reservarAsientos(array $asientos, int $usuarioId, int $rutaId, string $fecha): bool
    {
        try {
            return DB::transaction(function () use ($asientos, $usuarioId, $rutaId, $fecha) {
                foreach ($asientos as $asientoId) {
                    if (! $this->verificarDisponibilidad($asientoId, $rutaId, $fecha)) {
                        throw new \Exception("El asiento {$asientoId} ya no está disponible");
                    }
                    DB::table('reservas_temporales')->insert([
                        'asiento_id' => $asientoId, 'usuario_id' => $usuarioId, 'ruta_id' => $rutaId,
                        'fecha_viaje' => $fecha, 'fecha_reserva' => now(), 'expira_en' => now()->addMinutes(15),
                    ]);
                }

                return true;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AsientoService::reservarAsientos: '.$e->getMessage());

            return false;
        }
    }

    public function obtenerAsientoPorId(int $asientoId): ?object
    {
        return DB::table('asientos as a')->join('tipos_buses as tb', 'a.tipo_bus_id', '=', 'tb.id')
            ->where('a.id', $asientoId)->select('a.*', DB::raw('tb.nombre as tipo_bus'), 'tb.capacidad')->first();
    }

    public function crearAsientosPorTipo(int $tipoBusId, array $configuracion): bool
    {
        try {
            return DB::transaction(function () use ($tipoBusId, $configuracion) {
                DB::table('asientos')->where('tipo_bus_id', $tipoBusId)->delete();

                foreach ($configuracion as $asiento) {
                    DB::table('asientos')->insert([
                        'tipo_bus_id' => $tipoBusId, 'numero' => $asiento['numero'], 'piso' => $asiento['piso'],
                        'fila' => $asiento['fila'], 'columna' => $asiento['columna'], 'tipo' => $asiento['tipo'] ?? 'normal',
                        'estado' => 'disponible', 'precio_adicional' => $asiento['precio_adicional'] ?? 0,
                    ]);
                }

                return true;
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AsientoService::crearAsientosPorTipo: '.$e->getMessage());

            return false;
        }
    }

    public function actualizarEstadoAsiento(int $asientoId, string $estado): bool
    {
        return (bool) DB::table('asientos')->where('id', $asientoId)->update(['estado' => $estado]);
    }

    public function obtenerEstadisticasAsientos(int $tipoBusId): ?object
    {
        return DB::table('asientos')->where('tipo_bus_id', $tipoBusId)
            ->selectRaw("COUNT(*) as total_asientos,
                SUM(CASE WHEN tipo = 'premium' THEN 1 ELSE 0 END) as asientos_premium,
                SUM(CASE WHEN tipo = 'normal' THEN 1 ELSE 0 END) as asientos_normales,
                SUM(CASE WHEN piso = 1 THEN 1 ELSE 0 END) as asientos_piso1,
                SUM(CASE WHEN piso = 2 THEN 1 ELSE 0 END) as asientos_piso2")
            ->first();
    }

    public function limpiarReservasExpiradas(): bool
    {
        return (bool) DB::table('reservas_temporales')->where('expira_en', '<', now())->delete();
    }

    /** Generar configuracion de asientos por defecto (calculo puro, no toca BD). */
    public function generarConfiguracionDefecto(int $capacidad, int $pisos): array
    {
        $configuracion = [];
        $asientoNumero = 1;

        if ($pisos == 1) {
            $configuracion[] = ['numero' => $asientoNumero++, 'piso' => 1, 'fila' => 0, 'columna' => 1, 'tipo' => 'premium', 'precio_adicional' => 20];
            $configuracion[] = ['numero' => $asientoNumero++, 'piso' => 1, 'fila' => 0, 'columna' => 2, 'tipo' => 'premium', 'precio_adicional' => 20];

            $filas = (int) ceil(($capacidad - 2) / 4);
            for ($fila = 1; $fila <= $filas; $fila++) {
                for ($col = 0; $col < 4; $col++) {
                    if ($asientoNumero <= $capacidad) {
                        $configuracion[] = ['numero' => $asientoNumero++, 'piso' => 1, 'fila' => $fila, 'columna' => $col, 'tipo' => 'normal', 'precio_adicional' => 0];
                    }
                }
            }
        } else {
            $asientosPiso1 = (int) ceil($capacidad * 0.6);
            $asientosPiso2 = $capacidad - $asientosPiso1;

            $configuracion[] = ['numero' => $asientoNumero++, 'piso' => 1, 'fila' => 0, 'columna' => 1, 'tipo' => 'premium', 'precio_adicional' => 20];
            $configuracion[] = ['numero' => $asientoNumero++, 'piso' => 1, 'fila' => 0, 'columna' => 2, 'tipo' => 'premium', 'precio_adicional' => 20];

            $filas = (int) ceil(($asientosPiso1 - 2) / 4);
            for ($fila = 1; $fila <= $filas; $fila++) {
                for ($col = 0; $col < 4; $col++) {
                    if ($asientoNumero <= $asientosPiso1) {
                        $configuracion[] = ['numero' => $asientoNumero++, 'piso' => 1, 'fila' => $fila, 'columna' => $col, 'tipo' => 'normal', 'precio_adicional' => 0];
                    }
                }
            }

            $filas = (int) ceil($asientosPiso2 / 4);
            for ($fila = 0; $fila < $filas; $fila++) {
                for ($col = 0; $col < 4; $col++) {
                    if ($asientoNumero <= $capacidad) {
                        $configuracion[] = ['numero' => $asientoNumero++, 'piso' => 2, 'fila' => $fila, 'columna' => $col, 'tipo' => 'normal', 'precio_adicional' => 0];
                    }
                }
            }
        }

        return $configuracion;
    }
}
