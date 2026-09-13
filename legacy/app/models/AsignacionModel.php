<?php
class AsignacionModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function obtenerChoferes()
    {
        $this->db->query("SELECT id, nombres, apellidos FROM personal WHERE perfil = 'Chofer' AND estado = 1 ORDER BY apellidos ASC");
        return $this->db->resultSet();
    }

    public function obtenerCopilotos()
    {
        $this->db->query("SELECT id, nombres, apellidos FROM personal WHERE perfil = 'Copiloto' AND estado = 1 ORDER BY apellidos ASC");
        return $this->db->resultSet();
    }

    public function obtenerBuses()
    {
        $this->db->query("SELECT id, placa, marca FROM vehiculos WHERE estado = 1 ORDER BY placa ASC");
        return $this->db->resultSet();
    }

    public function listarAsignaciones()
    {
        $this->db->query("SELECT 
                            ab.id,
                            ab.fecha_asignacion,
                            ab.estado,
                            CONCAT(c.nombres, ' ', c.apellidos) as nombre_chofer,
                            COALESCE(CONCAT(cp.nombres, ' ', cp.apellidos), 'Sin Copiloto') as nombre_copiloto,
                            v.placa as placa_bus,
                            v.pasajeros as total_pasajeros
                          FROM asignaciones_buses ab
                          INNER JOIN personal c ON ab.chofer_id = c.id
                          LEFT JOIN personal cp ON ab.copiloto_id = cp.id
                          INNER JOIN vehiculos v ON ab.bus_id = v.id
                          ORDER BY ab.id DESC");
        return $this->db->resultSet();
    }

    public function crearAsignacion($choferId, $busId, $copilotoId)
    {
        $this->db->query("INSERT INTO asignaciones_buses (chofer_id, bus_id, copiloto_id) VALUES (:chofer_id, :bus_id, :copiloto_id)");
        $this->db->bind(':chofer_id', $choferId);
        $this->db->bind(':bus_id', $busId);
        $this->db->bind(':copiloto_id', $copilotoId);

        return $this->db->execute();
    }

    public function eliminarAsignacion($id)
    {
        $this->db->query("DELETE FROM asignaciones_buses WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Obtener una asignación por su ID
     * @param int $id - ID de la asignación
     * @return object|false - Objeto con los datos de la asignación o false si no existe
     */
    public function obtenerAsignacionPorId($id)
    {
        $this->db->query('SELECT * FROM asignaciones_buses WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Actualizar los datos de una asignación existente
     * @param array $datos - Array con los datos de la asignación a actualizar
     * @return bool - true si se actualizó correctamente, false en caso contrario
     */
    public function actualizarAsignacion($datos)
    {
        $this->db->query('UPDATE asignaciones_buses SET
            chofer_id = :chofer_id,
            bus_id = :bus_id,
            copiloto_id = :copiloto_id
        WHERE id = :id');

        // Vincular valores
        $this->db->bind(':id', $datos['id']);
        $this->db->bind(':chofer_id', $datos['chofer_id']);
        $this->db->bind(':bus_id', $datos['bus_id']);
        $this->db->bind(':copiloto_id', $datos['copiloto_id']);

        // Ejecutar
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }
}
