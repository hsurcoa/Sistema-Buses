<?php

class TipoBusModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Listar todos los tipos de buses activos
     */
    /**
     * Tipos que se pueden asignar a un bus: los activos y ademas los inactivos
     * que algun bus ya usa (si no, al editar ese bus su tipo desapareceria del select).
     */
    public function listarTiposParaFlota()
    {
        $this->db->query("SELECT * FROM tipos_buses
                          WHERE estado = 1 OR id IN (SELECT tipo_bus_id FROM vehiculos WHERE tipo_bus_id IS NOT NULL)
                          ORDER BY capacidad, nombre");
        return $this->db->resultSet();
    }

    public function listarTiposBuses()
    {
        $this->db->query("SELECT * FROM tipos_buses WHERE estado = 1 ORDER BY id DESC");
        return $this->db->resultSet();
    }

    /**
     * Obtener tipo de bus por ID
     */
    public function obtenerTipoBus($id)
    {
        $this->db->query("SELECT * FROM tipos_buses WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Agregar nuevo tipo de bus
     */
    public function agregarTipoBus($data)
    {
        $this->db->query("INSERT INTO tipos_buses (nombre, capacidad, pisos, configuracion_asientos, estado) 
                         VALUES (:nombre, :capacidad, :pisos, :configuracion_asientos, 1)");

        $this->db->bind(':nombre', $data['nombre']);
        $this->db->bind(':capacidad', $data['capacidad']);
        $this->db->bind(':pisos', $data['pisos']);
        $this->db->bind(':configuracion_asientos', $data['configuracion_asientos']);

        return $this->db->execute();
    }

    /**
     * Actualizar tipo de bus existente
     */
    public function actualizarTipoBus($data)
    {
        $this->db->query("UPDATE tipos_buses 
                         SET nombre = :nombre, 
                             capacidad = :capacidad, 
                             pisos = :pisos, 
                             configuracion_asientos = :configuracion_asientos 
                         WHERE id = :id");

        $this->db->bind(':id', $data['id']);
        $this->db->bind(':nombre', $data['nombre']);
        $this->db->bind(':capacidad', $data['capacidad']);
        $this->db->bind(':pisos', $data['pisos']);
        $this->db->bind(':configuracion_asientos', $data['configuracion_asientos']);

        return $this->db->execute();
    }

    /**
     * Cambiar estado del tipo de bus
     */
    public function cambiarEstado($id, $estado)
    {
        $this->db->query("UPDATE tipos_buses SET estado = :estado WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':estado', $estado);
        return $this->db->execute();
    }

    /**
     * Eliminar (desactivar) tipo de bus
     */
    public function eliminarTipoBus($id)
    {
        // Cambiar el estado a 0 (inactivo) en lugar de eliminarlo físicamente
        $this->db->query("UPDATE tipos_buses SET estado = 0 WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
