<?php
/**
 * Rutas por tramo: puntos de una ruta, tarifas entre puntos y ocupacion de
 * asientos por tramo.
 *
 * Convenciones (ver migracion 2026_09_14_110000):
 * - Punto "origen": parada id 0 (en boletos, parada_subida_id NULL).
 * - Punto "destino final": parada id 0 en la columna "hasta" (en boletos, parada_id NULL).
 * - Orden de recorrido: origen = 0, paradas = orden_index (1..n), destino = DESTINO.
 * - Un asiento esta ocupado para el tramo [a, b) si algun boleto activo del
 *   mismo asiento cubre [a2, b2) con a < b2 y a2 < b.
 */
class TramoModel
{
    public const DESTINO = 100000;

    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /** Puntos de la ruta en orden: origen, paradas activas y destino final. */
    public function puntos($rutaId)
    {
        $this->db->query('SELECT origen, destino FROM rutas WHERE id = :id');
        $this->db->bind(':id', $rutaId);
        $ruta = $this->db->single();
        if (!$ruta) {
            return [];
        }

        $this->db->query('SELECT id, nombre_parada, orden_index, precio_base_encomienda
                          FROM rutas_paradas WHERE ruta_id = :id AND estado = 1 ORDER BY orden_index, id');
        $this->db->bind(':id', $rutaId);

        $puntos = [['id' => 0, 'nombre' => $ruta->origen, 'orden' => 0, 'tipo' => 'origen']];
        foreach ($this->db->resultSet() as $p) {
            $puntos[] = ['id' => (int) $p->id, 'nombre' => $p->nombre_parada, 'orden' => (int) $p->orden_index, 'tipo' => 'parada'];
        }
        $puntos[] = ['id' => 0, 'nombre' => $ruta->destino, 'orden' => self::DESTINO, 'tipo' => 'destino'];
        return $puntos;
    }

    /** Orden de recorrido de una subida (0 = origen) o bajada (0 = destino). */
    public function orden($rutaId, $paradaId, $esBajada)
    {
        $paradaId = (int) $paradaId;
        if ($paradaId === 0) {
            return $esBajada ? self::DESTINO : 0;
        }
        $this->db->query('SELECT orden_index FROM rutas_paradas WHERE id = :id AND ruta_id = :ruta AND estado = 1');
        $this->db->bind(':id', $paradaId);
        $this->db->bind(':ruta', $rutaId);
        $fila = $this->db->single();
        return $fila ? (int) $fila->orden_index : null;
    }

    /** Tarifas de la ruta como mapa "desde-hasta" => precio. */
    public function matriz($rutaId)
    {
        $this->db->query('SELECT desde_parada_id, hasta_parada_id, precio, precio_sugerido FROM tarifas_tramo WHERE ruta_id = :id');
        $this->db->bind(':id', $rutaId);
        $mapa = [];
        foreach ($this->db->resultSet() as $t) {
            $mapa[$t->desde_parada_id . '-' . $t->hasta_parada_id] = ['precio' => (float) $t->precio, 'sugerido' => (bool) $t->precio_sugerido];
        }
        return $mapa;
    }

    public function tarifa($rutaId, $desde, $hasta)
    {
        $this->db->query('SELECT precio FROM tarifas_tramo WHERE ruta_id = :r AND desde_parada_id = :d AND hasta_parada_id = :h');
        $this->db->bind(':r', $rutaId);
        $this->db->bind(':d', (int) $desde);
        $this->db->bind(':h', (int) $hasta);
        $fila = $this->db->single();
        return $fila ? (float) $fila->precio : null;
    }

    /**
     * Asientos ocupados del viaje para el tramo [ordenSubida, ordenBajada).
     * Sin tramo se considera la ruta completa (cualquier boleto ocupa).
     */
    public function asientosOcupados($viajeId, $ordenSubida = 0, $ordenBajada = self::DESTINO)
    {
        $this->db->query('SELECT b.id, b.numero_asiento, b.estado,
                                 COALESCE(ps.orden_index, 0) AS orden_sube,
                                 COALESCE(pb.orden_index, ' . self::DESTINO . ') AS orden_baja,
                                 COALESCE(ps.nombre_parada, r.origen) AS sube,
                                 COALESCE(pb.nombre_parada, r.destino) AS baja
                          FROM boletos b
                          JOIN viajes v ON v.id = b.viaje_id
                          JOIN rutas r ON r.id = v.ruta_id
                          LEFT JOIN rutas_paradas ps ON ps.id = b.parada_subida_id
                          LEFT JOIN rutas_paradas pb ON pb.id = b.parada_id
                          WHERE b.viaje_id = :vid AND b.estado IN (\'vendido\', \'reservado\')');
        $this->db->bind(':vid', $viajeId);

        $ocupados = [];
        foreach ($this->db->resultSet() as $b) {
            if ((int) $b->orden_sube < $ordenBajada && $ordenSubida < (int) $b->orden_baja) {
                $ocupados[] = [
                    'id' => (int) $b->id,
                    'numero' => (int) $b->numero_asiento,
                    'estado' => $b->estado,
                    'tramo' => $b->sube . ' → ' . $b->baja,
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
    public function guardarParadas($rutaId, array $paradas)
    {
        $this->db->beginTransaction();
        try {
            $this->db->query('SELECT id FROM rutas_paradas WHERE ruta_id = :r');
            $this->db->bind(':r', $rutaId);
            $existentes = array_map(fn($p) => (int) $p->id, $this->db->resultSet());

            $conservadas = [];
            foreach (array_values($paradas) as $i => $p) {
                $orden = $i + 1;
                $id = (int) ($p['id'] ?? 0);
                if ($id && in_array($id, $existentes, true)) {
                    $this->db->query('UPDATE rutas_paradas SET nombre_parada = :n, orden_index = :o, precio_base_encomienda = :e, estado = 1 WHERE id = :id');
                    $this->db->bind(':id', $id);
                } else {
                    $this->db->query('INSERT INTO rutas_paradas (ruta_id, nombre_parada, orden_index, precio_pasaje, precio_base_encomienda, estado)
                                      VALUES (:r, :n, :o, 0, :e, 1)');
                    $this->db->bind(':r', $rutaId);
                }
                $this->db->bind(':n', $p['nombre']);
                $this->db->bind(':o', $orden);
                $this->db->bind(':e', $p['precio_encomienda']);
                $this->db->execute();
                $conservadas[] = $id ?: (int) $this->db->lastInsertId();
            }

            foreach (array_diff($existentes, $conservadas) as $id) {
                // Boletos que suben o bajan en esta parada (las encomiendas no guardan parada)
                $this->db->query('SELECT COUNT(*) AS usos FROM boletos WHERE parada_id = :a OR parada_subida_id = :b');
                $this->db->bind(':a', $id);
                $this->db->bind(':b', $id);
                $usos = (int) $this->db->single()->usos;

                if ($usos > 0) {
                    $this->db->query('UPDATE rutas_paradas SET estado = 0, orden_index = 0 WHERE id = :id');
                } else {
                    $this->db->query('DELETE FROM rutas_paradas WHERE id = :id');
                }
                $this->db->bind(':id', $id);
                $this->db->execute();

                $this->db->query('DELETE FROM tarifas_tramo WHERE ruta_id = :r AND (desde_parada_id = :d OR hasta_parada_id = :h)');
                $this->db->bind(':r', $rutaId);
                $this->db->bind(':d', $id);
                $this->db->bind(':h', $id);
                $this->db->execute();
            }

            $this->db->commit();
            return $conservadas;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Guarda la matriz completa de tarifas. $tarifas: [['desde' => id, 'hasta' => id, 'precio' => float]]
     * Tambien mantiene precio_pasaje de cada parada (tarifa desde el origen) por compatibilidad.
     */
    public function guardarTarifas($rutaId, array $tarifas)
    {
        $this->db->beginTransaction();
        try {
            $this->db->query('DELETE FROM tarifas_tramo WHERE ruta_id = :r');
            $this->db->bind(':r', $rutaId);
            $this->db->execute();

            foreach ($tarifas as $t) {
                $this->db->query('INSERT INTO tarifas_tramo (ruta_id, desde_parada_id, hasta_parada_id, precio, precio_sugerido) VALUES (:r, :d, :h, :p, 0)');
                $this->db->bind(':r', $rutaId);
                $this->db->bind(':d', (int) $t['desde']);
                $this->db->bind(':h', (int) $t['hasta']);
                $this->db->bind(':p', round((float) $t['precio'], 2));
                $this->db->execute();

                if ((int) $t['desde'] === 0 && (int) $t['hasta'] > 0) {
                    $this->db->query('UPDATE rutas_paradas SET precio_pasaje = :p WHERE id = :id AND ruta_id = :r');
                    $this->db->bind(':p', round((float) $t['precio'], 2));
                    $this->db->bind(':id', (int) $t['hasta']);
                    $this->db->bind(':r', $rutaId);
                    $this->db->execute();
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
