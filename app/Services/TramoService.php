<?php

namespace App\Services;

use App\Models\Ruta;
use App\Models\RutaParada;
use App\Models\TarifaTramo;
use Illuminate\Support\Facades\DB;

/**
 * Rutas por tramo: puntos de una ruta, tarifas entre puntos y ocupacion de
 * asientos por tramo. Reescritura Eloquent de `legacy/app/models/TramoModel.php`
 * (eliminacion de PHP puro, ver informe de fin de Fase 8).
 *
 * Convenciones (identicas al original):
 * - Punto "origen": parada id 0 (en boletos, parada_subida_id NULL).
 * - Punto "destino final": parada id 0 en la columna "hasta" (en boletos, parada_id NULL).
 * - Orden de recorrido: origen = 0, paradas = orden_index (1..n), destino = TarifaTramo::DESTINO.
 */
class TramoService
{
    public const DESTINO = TarifaTramo::DESTINO;

    /** Puntos de la ruta en orden: origen, paradas activas y destino final. */
    public function puntos(int $rutaId): array
    {
        $ruta = Ruta::find($rutaId);
        if (! $ruta) {
            return [];
        }

        $puntos = [['id' => 0, 'nombre' => $ruta->origen, 'orden' => 0, 'tipo' => 'origen']];

        foreach (RutaParada::where('ruta_id', $rutaId)->where('estado', 1)->orderBy('orden_index')->orderBy('id')->get() as $p) {
            $puntos[] = ['id' => (int) $p->id, 'nombre' => $p->nombre_parada, 'orden' => (int) $p->orden_index, 'tipo' => 'parada'];
        }

        $puntos[] = ['id' => 0, 'nombre' => $ruta->destino, 'orden' => self::DESTINO, 'tipo' => 'destino'];

        return $puntos;
    }

    /** Orden de recorrido de una subida (0 = origen) o bajada (0 = destino). */
    public function orden(int $rutaId, int $paradaId, bool $esBajada): ?int
    {
        if ($paradaId === 0) {
            return $esBajada ? self::DESTINO : 0;
        }

        $orden = RutaParada::where('id', $paradaId)->where('ruta_id', $rutaId)->where('estado', 1)->value('orden_index');

        return $orden !== null ? (int) $orden : null;
    }

    /** Tarifas de la ruta como mapa "desde-hasta" => ['precio' => float, 'sugerido' => bool]. */
    public function matriz(int $rutaId): array
    {
        $mapa = [];
        foreach (TarifaTramo::where('ruta_id', $rutaId)->get() as $t) {
            $mapa[$t->desde_parada_id.'-'.$t->hasta_parada_id] = ['precio' => (float) $t->precio, 'sugerido' => (bool) $t->precio_sugerido];
        }

        return $mapa;
    }

    public function tarifa(int $rutaId, int $desde, int $hasta): ?float
    {
        $precio = TarifaTramo::where('ruta_id', $rutaId)->where('desde_parada_id', $desde)->where('hasta_parada_id', $hasta)->value('precio');

        return $precio !== null ? (float) $precio : null;
    }

    /**
     * Si ya hay un precio por tramo cargado para el trayecto completo
     * (origen -> destino final, sin paradas intermedias elegidas), el
     * "Precio Base" del viaje (pestaña Servicios y Precio) queda sin efecto:
     * este manda siempre. Sirve para avisarle al usuario en esa pantalla.
     */
    public function tieneTarifaCompleta(int $rutaId): ?float
    {
        return $this->tarifa($rutaId, 0, self::DESTINO);
    }

    /**
     * Asientos ocupados del viaje para el tramo [ordenSubida, ordenBajada).
     * Sin tramo se considera la ruta completa (cualquier boleto ocupa).
     */
    public function asientosOcupados(int $viajeId, int $ordenSubida = 0, int $ordenBajada = self::DESTINO): array
    {
        $filas = DB::table('boletos as b')
            ->join('viajes as v', 'v.id', '=', 'b.viaje_id')
            ->join('rutas as r', 'r.id', '=', 'v.ruta_id')
            ->leftJoin('rutas_paradas as ps', 'ps.id', '=', 'b.parada_subida_id')
            ->leftJoin('rutas_paradas as pb', 'pb.id', '=', 'b.parada_id')
            ->where('b.viaje_id', $viajeId)
            ->whereIn('b.estado', ['vendido', 'reservado'])
            ->select([
                'b.id', 'b.numero_asiento', 'b.estado',
                DB::raw('COALESCE(ps.orden_index, 0) AS orden_sube'),
                DB::raw('COALESCE(pb.orden_index, '.self::DESTINO.') AS orden_baja'),
                DB::raw('COALESCE(ps.nombre_parada, r.origen) AS sube'),
                DB::raw('COALESCE(pb.nombre_parada, r.destino) AS baja'),
            ])
            ->get();

        $ocupados = [];
        foreach ($filas as $b) {
            if ((int) $b->orden_sube < $ordenBajada && $ordenSubida < (int) $b->orden_baja) {
                $ocupados[] = [
                    'id' => (int) $b->id,
                    'numero' => (int) $b->numero_asiento,
                    'estado' => $b->estado,
                    'tramo' => $b->sube.' → '.$b->baja,
                ];
            }
        }

        return $ocupados;
    }

    /**
     * Guarda paradas intermedias en el orden recibido.
     * $paradas: [['id' => int|null, 'nombre' => str, 'precio_encomienda' => float], ...]
     * Las que ya no vienen se eliminan si nunca se usaron; si tienen boletos o
     * encomiendas se desactivan (asi el historial conserva el nombre).
     */
    public function guardarParadas(int $rutaId, array $paradas): array
    {
        return DB::transaction(function () use ($rutaId, $paradas) {
            $existentes = RutaParada::where('ruta_id', $rutaId)->pluck('id')->map(fn ($id) => (int) $id)->all();

            $conservadas = [];
            foreach (array_values($paradas) as $i => $p) {
                $orden = $i + 1;
                $id = (int) ($p['id'] ?? 0);

                if ($id && in_array($id, $existentes, true)) {
                    RutaParada::where('id', $id)->update([
                        'nombre_parada' => $p['nombre'],
                        'orden_index' => $orden,
                        'precio_base_encomienda' => $p['precio_encomienda'],
                        'estado' => 1,
                    ]);
                    $conservadas[] = $id;
                } else {
                    $nueva = RutaParada::create([
                        'ruta_id' => $rutaId,
                        'nombre_parada' => $p['nombre'],
                        'orden_index' => $orden,
                        'precio_pasaje' => 0,
                        'precio_base_encomienda' => $p['precio_encomienda'],
                        'estado' => 1,
                    ]);
                    $conservadas[] = (int) $nueva->id;
                }
            }

            foreach (array_diff($existentes, $conservadas) as $id) {
                $usos = DB::table('boletos')->where('parada_id', $id)->orWhere('parada_subida_id', $id)->count();

                if ($usos > 0) {
                    RutaParada::where('id', $id)->update(['estado' => 0, 'orden_index' => 0]);
                } else {
                    RutaParada::where('id', $id)->delete();
                }

                TarifaTramo::where('ruta_id', $rutaId)->where(function ($q) use ($id) {
                    $q->where('desde_parada_id', $id)->orWhere('hasta_parada_id', $id);
                })->delete();
            }

            return $conservadas;
        });
    }

    /**
     * Guarda la matriz completa de tarifas. $tarifas: [['desde' => id, 'hasta' => id, 'precio' => float]]
     * Tambien mantiene precio_pasaje de cada parada (tarifa desde el origen) por compatibilidad.
     */
    public function guardarTarifas(int $rutaId, array $tarifas): bool
    {
        return DB::transaction(function () use ($rutaId, $tarifas) {
            TarifaTramo::where('ruta_id', $rutaId)->delete();

            foreach ($tarifas as $t) {
                $precio = round((float) $t['precio'], 2);
                TarifaTramo::create([
                    'ruta_id' => $rutaId,
                    'desde_parada_id' => (int) $t['desde'],
                    'hasta_parada_id' => (int) $t['hasta'],
                    'precio' => $precio,
                    'precio_sugerido' => 0,
                ]);

                if ((int) $t['desde'] === 0 && (int) $t['hasta'] > 0) {
                    RutaParada::where('id', (int) $t['hasta'])->where('ruta_id', $rutaId)->update(['precio_pasaje' => $precio]);
                }
            }

            return true;
        });
    }
}
