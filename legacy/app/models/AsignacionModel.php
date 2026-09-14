<?php
/**
 * Asignacion permanente de tripulacion a un bus (chofer + copiloto opcional).
 *
 * Reglas (antes no existian y la BD tenia buses con 2 choferes activos y
 * choferes en 2 buses a la vez):
 * - Un bus tiene como maximo UNA asignacion activa.
 * - Un chofer/copiloto esta activo en UN solo bus.
 * - El chofer debe tener perfil Chofer y el copiloto perfil Copiloto, ambos activos.
 * - Chofer y copiloto no pueden ser la misma persona.
 * - Finalizar una asignacion no la borra (queda en el historial, estado = 0).
 *
 * La asignacion es la tripulacion POR DEFECTO del bus: al programar un viaje se
 * propone automaticamente, pero el viaje puede llevar otro chofer sin cambiarla.
 */
class AsignacionModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function obtenerChoferes()
    {
        $this->db->query("SELECT p.id, p.nombres, p.apellidos, p.numero_documento,
                                 v.placa AS bus_actual
                          FROM personal p
                          LEFT JOIN asignaciones_buses ab ON ab.chofer_id = p.id AND ab.estado = 1
                          LEFT JOIN vehiculos v ON v.id = ab.bus_id
                          WHERE p.perfil = 'Chofer' AND p.estado = 1
                          ORDER BY p.apellidos, p.nombres");
        return $this->db->resultSet();
    }

    public function obtenerCopilotos()
    {
        $this->db->query("SELECT p.id, p.nombres, p.apellidos, p.numero_documento,
                                 v.placa AS bus_actual
                          FROM personal p
                          LEFT JOIN asignaciones_buses ab ON ab.copiloto_id = p.id AND ab.estado = 1
                          LEFT JOIN vehiculos v ON v.id = ab.bus_id
                          WHERE p.perfil = 'Copiloto' AND p.estado = 1
                          ORDER BY p.apellidos, p.nombres");
        return $this->db->resultSet();
    }

    /** Buses activos con su tipo real y la tripulacion actual (si tiene). */
    public function obtenerBuses()
    {
        $this->db->query("SELECT v.id, v.placa, v.marca, v.modelo, v.asientos,
                                 tb.id AS tipo_bus_id, tb.nombre AS tipo_nombre, tb.capacidad AS tipo_capacidad, tb.pisos,
                                 CONCAT(c.nombres, ' ', c.apellidos) AS chofer_actual
                          FROM vehiculos v
                          LEFT JOIN tipos_buses tb ON tb.id = v.tipo_bus_id
                          LEFT JOIN asignaciones_buses ab ON ab.bus_id = v.id AND ab.estado = 1
                          LEFT JOIN personal c ON c.id = ab.chofer_id
                          WHERE v.estado = 1
                          ORDER BY v.placa");
        return $this->db->resultSet();
    }

    public function listarAsignaciones($incluirHistorial = false)
    {
        $this->db->query("SELECT
                            ab.id, ab.fecha_asignacion, ab.estado,
                            ab.chofer_id, ab.copiloto_id, ab.bus_id,
                            CONCAT(c.nombres, ' ', c.apellidos) AS nombre_chofer,
                            c.numero_documento AS documento_chofer,
                            CONCAT(cp.nombres, ' ', cp.apellidos) AS nombre_copiloto,
                            v.placa AS placa_bus, v.marca, v.modelo, v.asientos,
                            tb.nombre AS tipo_nombre, tb.capacidad AS tipo_capacidad, tb.pisos
                          FROM asignaciones_buses ab
                          INNER JOIN personal c ON ab.chofer_id = c.id
                          LEFT JOIN personal cp ON ab.copiloto_id = cp.id
                          INNER JOIN vehiculos v ON ab.bus_id = v.id
                          LEFT JOIN tipos_buses tb ON tb.id = v.tipo_bus_id
                          " . ($incluirHistorial ? '' : 'WHERE ab.estado = 1') . "
                          ORDER BY ab.estado DESC, ab.fecha_asignacion DESC, ab.id DESC");
        return $this->db->resultSet();
    }

    public function obtenerAsignacionPorId($id)
    {
        $this->db->query('SELECT * FROM asignaciones_buses WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Valida los datos y detecta conflictos con otras asignaciones activas.
     * @return array ['errores' => string[], 'conflictos' => string[]]
     */
    public function validar($datos)
    {
        $errores = [];
        $conflictos = [];
        $id = !empty($datos['id']) ? (int) $datos['id'] : 0;

        $chofer = $this->persona($datos['chofer_id']);
        if (!$chofer || $chofer->estado != 1 || $chofer->perfil !== 'Chofer') {
            $errores[] = 'El chofer seleccionado no existe, está inactivo o no tiene perfil de Chofer.';
        }

        if (!empty($datos['copiloto_id'])) {
            if ((int) $datos['copiloto_id'] === (int) $datos['chofer_id']) {
                $errores[] = 'El chofer y el copiloto no pueden ser la misma persona.';
            }
            $copiloto = $this->persona($datos['copiloto_id']);
            if (!$copiloto || $copiloto->estado != 1 || $copiloto->perfil !== 'Copiloto') {
                $errores[] = 'El copiloto seleccionado no existe, está inactivo o no tiene perfil de Copiloto.';
            }
        }

        $this->db->query('SELECT v.id, v.placa, v.estado, v.tipo_bus_id FROM vehiculos v WHERE v.id = :id');
        $this->db->bind(':id', $datos['bus_id']);
        $bus = $this->db->single();
        if (!$bus || $bus->estado != 1) {
            $errores[] = 'El bus seleccionado no existe o está dado de baja.';
        }

        if ($errores) {
            return ['errores' => $errores, 'conflictos' => []];
        }

        // Conflictos con asignaciones activas (excluyendo la que se edita)
        $this->db->query("SELECT ab.id, v.placa, CONCAT(c.nombres, ' ', c.apellidos) AS chofer,
                                 ab.bus_id, ab.chofer_id, ab.copiloto_id
                          FROM asignaciones_buses ab
                          JOIN vehiculos v ON v.id = ab.bus_id
                          JOIN personal c ON c.id = ab.chofer_id
                          WHERE ab.estado = 1 AND ab.id <> :id
                            AND (ab.bus_id = :bus OR ab.chofer_id IN (:chofer, :cop1) OR ab.copiloto_id IN (:chofer2, :cop2))");
        $this->db->bind(':id', $id);
        $this->db->bind(':bus', $datos['bus_id']);
        $this->db->bind(':chofer', $datos['chofer_id']);
        $this->db->bind(':chofer2', $datos['chofer_id']);
        $cop = !empty($datos['copiloto_id']) ? $datos['copiloto_id'] : 0;
        $this->db->bind(':cop1', $cop);
        $this->db->bind(':cop2', $cop);

        foreach ($this->db->resultSet() as $otra) {
            if ((int) $otra->bus_id === (int) $datos['bus_id']) {
                $conflictos[] = "El bus {$otra->placa} ya tiene como chofer a {$otra->chofer}.";
            }
            if (in_array((int) $datos['chofer_id'], [(int) $otra->chofer_id, (int) $otra->copiloto_id], true)) {
                $conflictos[] = "{$chofer->nombres} {$chofer->apellidos} ya está asignado al bus {$otra->placa}.";
            }
            if ($cop && in_array((int) $cop, [(int) $otra->chofer_id, (int) $otra->copiloto_id], true)) {
                $conflictos[] = "El copiloto ya está asignado al bus {$otra->placa}.";
            }
        }

        if (!$bus->tipo_bus_id) {
            // No bloquea la asignacion, pero se avisa: sin tipo no se puede programar viajes con este bus.
            $conflictos[] = "Aviso: el bus {$bus->placa} no tiene tipo de bus (asientos) definido. Complételo en Gestión de Flota antes de programar viajes.";
        }

        return ['errores' => $errores, 'conflictos' => array_values(array_unique($conflictos))];
    }

    /**
     * Crea o actualiza una asignacion. Con $reemplazar = true, finaliza primero las
     * asignaciones activas que chocan (mismo bus, chofer o copiloto).
     */
    public function guardar($datos, $reemplazar = false)
    {
        $id = !empty($datos['id']) ? (int) $datos['id'] : 0;
        $copiloto = !empty($datos['copiloto_id']) ? $datos['copiloto_id'] : null;

        $this->db->beginTransaction();
        try {
            if ($reemplazar) {
                $this->db->query("UPDATE asignaciones_buses SET estado = 0
                                  WHERE estado = 1 AND id <> :id
                                    AND (bus_id = :bus OR chofer_id IN (:chofer, :cop1) OR copiloto_id IN (:chofer2, :cop2))");
                $this->db->bind(':id', $id);
                $this->db->bind(':bus', $datos['bus_id']);
                $this->db->bind(':chofer', $datos['chofer_id']);
                $this->db->bind(':chofer2', $datos['chofer_id']);
                $this->db->bind(':cop1', $copiloto ?? 0);
                $this->db->bind(':cop2', $copiloto ?? 0);
                $this->db->execute();
            }

            if ($id) {
                $this->db->query('UPDATE asignaciones_buses SET chofer_id = :chofer, bus_id = :bus, copiloto_id = :cop, estado = 1 WHERE id = :id');
                $this->db->bind(':id', $id);
            } else {
                $this->db->query('INSERT INTO asignaciones_buses (chofer_id, bus_id, copiloto_id, fecha_asignacion, estado) VALUES (:chofer, :bus, :cop, NOW(), 1)');
            }
            $this->db->bind(':chofer', $datos['chofer_id']);
            $this->db->bind(':bus', $datos['bus_id']);
            $this->db->bind(':cop', $copiloto);
            $this->db->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Compatibilidad con llamadas existentes
    public function crearAsignacion($choferId, $busId, $copilotoId)
    {
        return $this->guardar(['chofer_id' => $choferId, 'bus_id' => $busId, 'copiloto_id' => $copilotoId]);
    }

    public function actualizarAsignacion($datos)
    {
        return $this->guardar($datos);
    }

    /** Finaliza la asignacion (queda en el historial). */
    public function finalizarAsignacion($id)
    {
        $this->db->query('UPDATE asignaciones_buses SET estado = 0 WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function eliminarAsignacion($id)
    {
        return $this->finalizarAsignacion($id);
    }

    private function persona($id)
    {
        if (empty($id)) {
            return null;
        }
        $this->db->query('SELECT id, nombres, apellidos, perfil, estado FROM personal WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single() ?: null;
    }
}
