<?php

/**
 * PARTE 1: BACKEND - MODELO
 * Archivo: app/models/RutaModel.php
 * 
 * Esta consulta SQL ya está implementada en tu sistema.
 * Aquí te muestro la versión optimizada con todos los JOINs necesarios.
 */

class RutaModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Listar viajes programados con información completa
     * CONSULTA SQL OPTIMIZADA CON JOINS
     */
    public function listarViajesProgramados($limit = 100, $offset = 0)
    {
        try {
            $sql = "SELECT 
                    v.id,
                    v.fecha_salida,
                    v.hora_salida,
                    v.hora_llegada,
                    v.precio_base,
                    v.tipo_servicio,
                    v.estado,
                    
                    -- Información de la ruta (origen y destino)
                    r.origen,
                    r.destino,
                    
                    -- Información del tipo de bus
                    tb.nombre as tipo_bus,
                    tb.capacidad,
                    
                    -- Información de terminales
                    to_term.nombre_sede as terminal_origen,
                    td_term.nombre_sede as terminal_destino,
                    
                    -- Información del chofer
                    CONCAT(u1.nombres, ' ', u1.apellidos) as chofer_nombre,
                    
                    -- Información del bus asignado
                    b.placa as bus_placa,
                    b.numero_interno as bus_numero
                    
                FROM viajes v
                INNER JOIN rutas r ON v.ruta_id = r.id
                LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
                LEFT JOIN terminales to_term ON v.terminal_origen_id = to_term.id
                LEFT JOIN terminales td_term ON v.terminal_destino_id = td_term.id
                LEFT JOIN usuarios u1 ON v.chofer_id = u1.id
                LEFT JOIN buses b ON v.bus_id = b.id
                ORDER BY v.fecha_salida DESC, v.hora_salida DESC
                LIMIT :limit OFFSET :offset";

            $this->db->query($sql);
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', $offset, PDO::PARAM_INT);

            return $this->db->resultSet();
        } catch (PDOException $e) {
            error_log("Error en listarViajesProgramados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Eliminar viaje programado (Borrado físico)
     */
    public function eliminarViaje($id)
    {
        try {
            // Log para debugging
            error_log("Intentando eliminar viaje con ID: " . $id);

            // Verificar que el viaje existe antes de eliminar
            $this->db->query("SELECT id FROM viajes WHERE id = :id");
            $this->db->bind(':id', $id);
            $viaje = $this->db->single();

            if (!$viaje) {
                error_log("Viaje con ID {$id} no encontrado");
                return "El viaje no existe";
            }

            // Eliminar el viaje (borrado físico)
            $this->db->query("DELETE FROM viajes WHERE id = :id");
            $this->db->bind(':id', $id);

            if ($this->db->execute()) {
                error_log("Viaje con ID {$id} eliminado exitosamente");
                return true;
            } else {
                error_log("Error al ejecutar DELETE para viaje ID {$id}");
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error PDO al eliminar viaje ID {$id}: " . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * ALTERNATIVA: Soft Delete (Borrado lógico)
     * Cambia el estado a 'inactivo' en lugar de eliminar físicamente
     */
    public function desactivarViaje($id)
    {
        try {
            $this->db->query("UPDATE viajes SET estado = 'inactivo' WHERE id = :id");
            $this->db->bind(':id', $id);

            if ($this->db->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error al desactivar viaje: " . $e->getMessage());
            return false;
        }
    }
}
