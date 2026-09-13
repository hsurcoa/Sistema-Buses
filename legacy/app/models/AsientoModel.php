<?php

/**
 * Modelo: AsientoModel
 * Descripción: Maneja todas las operaciones relacionadas con los asientos de los buses
 * Autor: Sistema de Venta de Pasajes
 * Fecha: 2025-12-20
 */

class AsientoModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Obtener todos los asientos de un bus específico
     * @param int $busId ID del bus
     * @param int $piso Número de piso (opcional)
     * @return array Lista de asientos
     */
    public function obtenerAsientosPorBus($busId, $piso = null)
    {
        $sql = "SELECT 
                    a.id,
                    a.numero,
                    a.piso,
                    a.fila,
                    a.columna,
                    a.estado,
                    a.tipo,
                    a.precio_adicional,
                    tb.nombre as tipo_bus
                FROM asientos a
                INNER JOIN tipos_buses tb ON a.tipo_bus_id = tb.id
                WHERE a.tipo_bus_id = :bus_id";

        if ($piso !== null) {
            $sql .= " AND a.piso = :piso";
        }

        $sql .= " ORDER BY a.piso ASC, a.fila ASC, a.columna ASC";

        $this->db->query($sql);
        $this->db->bind(':bus_id', $busId);

        if ($piso !== null) {
            $this->db->bind(':piso', $piso);
        }

        return $this->db->resultSet();
    }

    /**
     * Obtener asientos disponibles para una ruta y fecha específica
     * @param int $rutaId ID de la ruta
     * @param string $fecha Fecha del viaje
     * @param int $busId ID del bus
     * @return array Lista de asientos con su disponibilidad
     */
    public function obtenerAsientosDisponibles($rutaId, $fecha, $busId)
    {
        $sql = "SELECT 
                    a.id,
                    a.numero,
                    a.piso,
                    a.fila,
                    a.columna,
                    a.tipo,
                    a.precio_adicional,
                    CASE 
                        WHEN v.id IS NOT NULL THEN 'ocupado'
                        ELSE 'disponible'
                    END as estado
                FROM asientos a
                LEFT JOIN ventas v ON a.id = v.asiento_id 
                    AND v.ruta_id = :ruta_id 
                    AND DATE(v.fecha_viaje) = :fecha
                    AND v.estado != 'cancelado'
                WHERE a.tipo_bus_id = :bus_id
                ORDER BY a.piso ASC, a.fila ASC, a.columna ASC";

        $this->db->query($sql);
        $this->db->bind(':ruta_id', $rutaId);
        $this->db->bind(':fecha', $fecha);
        $this->db->bind(':bus_id', $busId);

        return $this->db->resultSet();
    }

    /**
     * Verificar si un asiento está disponible
     * @param int $asientoId ID del asiento
     * @param int $rutaId ID de la ruta
     * @param string $fecha Fecha del viaje
     * @return bool True si está disponible, False si está ocupado
     */
    public function verificarDisponibilidad($asientoId, $rutaId, $fecha)
    {
        $sql = "SELECT COUNT(*) as total
                FROM ventas
                WHERE asiento_id = :asiento_id
                AND ruta_id = :ruta_id
                AND DATE(fecha_viaje) = :fecha
                AND estado != 'cancelado'";

        $this->db->query($sql);
        $this->db->bind(':asiento_id', $asientoId);
        $this->db->bind(':ruta_id', $rutaId);
        $this->db->bind(':fecha', $fecha);

        $resultado = $this->db->single();
        return $resultado->total == 0;
    }

    /**
     * Reservar asientos (marcar como ocupados temporalmente)
     * @param array $asientos Array de IDs de asientos
     * @param int $usuarioId ID del usuario que reserva
     * @param int $rutaId ID de la ruta
     * @param string $fecha Fecha del viaje
     * @return bool True si se reservó correctamente
     */
    public function reservarAsientos($asientos, $usuarioId, $rutaId, $fecha)
    {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO reservas_temporales 
                    (asiento_id, usuario_id, ruta_id, fecha_viaje, fecha_reserva, expira_en)
                    VALUES 
                    (:asiento_id, :usuario_id, :ruta_id, :fecha_viaje, NOW(), DATE_ADD(NOW(), INTERVAL 15 MINUTE))";

            foreach ($asientos as $asientoId) {
                // Verificar disponibilidad antes de reservar
                if (!$this->verificarDisponibilidad($asientoId, $rutaId, $fecha)) {
                    throw new Exception("El asiento $asientoId ya no está disponible");
                }

                $this->db->query($sql);
                $this->db->bind(':asiento_id', $asientoId);
                $this->db->bind(':usuario_id', $usuarioId);
                $this->db->bind(':ruta_id', $rutaId);
                $this->db->bind(':fecha_viaje', $fecha);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al reservar asientos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener información de un asiento específico
     * @param int $asientoId ID del asiento
     * @return object|false Datos del asiento o false si no existe
     */
    public function obtenerAsientoPorId($asientoId)
    {
        $sql = "SELECT 
                    a.*,
                    tb.nombre as tipo_bus,
                    tb.capacidad
                FROM asientos a
                INNER JOIN tipos_buses tb ON a.tipo_bus_id = tb.id
                WHERE a.id = :asiento_id";

        $this->db->query($sql);
        $this->db->bind(':asiento_id', $asientoId);

        return $this->db->single();
    }

    /**
     * Crear asientos para un tipo de bus
     * @param int $tipoBusId ID del tipo de bus
     * @param array $configuracion Configuración de asientos
     * @return bool True si se crearon correctamente
     */
    public function crearAsientosPorTipo($tipoBusId, $configuracion)
    {
        try {
            $this->db->beginTransaction();

            // Primero eliminar asientos existentes de este tipo
            $this->db->query("DELETE FROM asientos WHERE tipo_bus_id = :tipo_bus_id");
            $this->db->bind(':tipo_bus_id', $tipoBusId);
            $this->db->execute();

            // Insertar nuevos asientos
            $sql = "INSERT INTO asientos 
                    (tipo_bus_id, numero, piso, fila, columna, tipo, estado, precio_adicional)
                    VALUES 
                    (:tipo_bus_id, :numero, :piso, :fila, :columna, :tipo, 'disponible', :precio_adicional)";

            foreach ($configuracion as $asiento) {
                $this->db->query($sql);
                $this->db->bind(':tipo_bus_id', $tipoBusId);
                $this->db->bind(':numero', $asiento['numero']);
                $this->db->bind(':piso', $asiento['piso']);
                $this->db->bind(':fila', $asiento['fila']);
                $this->db->bind(':columna', $asiento['columna']);
                $this->db->bind(':tipo', $asiento['tipo'] ?? 'normal');
                $this->db->bind(':precio_adicional', $asiento['precio_adicional'] ?? 0);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error al crear asientos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar estado de un asiento
     * @param int $asientoId ID del asiento
     * @param string $estado Nuevo estado (disponible, ocupado, mantenimiento)
     * @return bool True si se actualizó correctamente
     */
    public function actualizarEstadoAsiento($asientoId, $estado)
    {
        $sql = "UPDATE asientos 
                SET estado = :estado
                WHERE id = :asiento_id";

        $this->db->query($sql);
        $this->db->bind(':estado', $estado);
        $this->db->bind(':asiento_id', $asientoId);

        return $this->db->execute();
    }

    /**
     * Obtener estadísticas de asientos por tipo de bus
     * @param int $tipoBusId ID del tipo de bus
     * @return object Estadísticas de asientos
     */
    public function obtenerEstadisticasAsientos($tipoBusId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_asientos,
                    SUM(CASE WHEN tipo = 'premium' THEN 1 ELSE 0 END) as asientos_premium,
                    SUM(CASE WHEN tipo = 'normal' THEN 1 ELSE 0 END) as asientos_normales,
                    SUM(CASE WHEN piso = 1 THEN 1 ELSE 0 END) as asientos_piso1,
                    SUM(CASE WHEN piso = 2 THEN 1 ELSE 0 END) as asientos_piso2
                FROM asientos
                WHERE tipo_bus_id = :tipo_bus_id";

        $this->db->query($sql);
        $this->db->bind(':tipo_bus_id', $tipoBusId);

        return $this->db->single();
    }

    /**
     * Limpiar reservas temporales expiradas
     * @return bool True si se limpiaron correctamente
     */
    public function limpiarReservasExpiradas()
    {
        $sql = "DELETE FROM reservas_temporales 
                WHERE expira_en < NOW()";

        $this->db->query($sql);
        return $this->db->execute();
    }

    /**
     * Generar configuración de asientos por defecto
     * @param int $capacidad Capacidad total del bus
     * @param int $pisos Número de pisos
     * @return array Configuración de asientos
     */
    public function generarConfiguracionDefecto($capacidad, $pisos)
    {
        $configuracion = [];
        $asientoNumero = 1;

        if ($pisos == 1) {
            // Configuración para 1 piso
            // 2 asientos premium en cabina
            $configuracion[] = [
                'numero' => $asientoNumero++,
                'piso' => 1,
                'fila' => 0,
                'columna' => 1,
                'tipo' => 'premium',
                'precio_adicional' => 20
            ];
            $configuracion[] = [
                'numero' => $asientoNumero++,
                'piso' => 1,
                'fila' => 0,
                'columna' => 2,
                'tipo' => 'premium',
                'precio_adicional' => 20
            ];

            // Asientos normales en grid 2-2
            $filas = ceil(($capacidad - 2) / 4);
            for ($fila = 1; $fila <= $filas; $fila++) {
                for ($col = 0; $col < 4; $col++) {
                    if ($asientoNumero <= $capacidad) {
                        $configuracion[] = [
                            'numero' => $asientoNumero++,
                            'piso' => 1,
                            'fila' => $fila,
                            'columna' => $col,
                            'tipo' => 'normal',
                            'precio_adicional' => 0
                        ];
                    }
                }
            }
        } else {
            // Configuración para 2 pisos
            $asientosPiso1 = ceil($capacidad * 0.6); // 60% en piso 1
            $asientosPiso2 = $capacidad - $asientosPiso1;

            // Piso 1 con premium
            $configuracion[] = [
                'numero' => $asientoNumero++,
                'piso' => 1,
                'fila' => 0,
                'columna' => 1,
                'tipo' => 'premium',
                'precio_adicional' => 20
            ];
            $configuracion[] = [
                'numero' => $asientoNumero++,
                'piso' => 1,
                'fila' => 0,
                'columna' => 2,
                'tipo' => 'premium',
                'precio_adicional' => 20
            ];

            // Resto del piso 1
            $filas = ceil(($asientosPiso1 - 2) / 4);
            for ($fila = 1; $fila <= $filas; $fila++) {
                for ($col = 0; $col < 4; $col++) {
                    if ($asientoNumero <= $asientosPiso1) {
                        $configuracion[] = [
                            'numero' => $asientoNumero++,
                            'piso' => 1,
                            'fila' => $fila,
                            'columna' => $col,
                            'tipo' => 'normal',
                            'precio_adicional' => 0
                        ];
                    }
                }
            }

            // Piso 2
            $filas = ceil($asientosPiso2 / 4);
            for ($fila = 0; $fila < $filas; $fila++) {
                for ($col = 0; $col < 4; $col++) {
                    if ($asientoNumero <= $capacidad) {
                        $configuracion[] = [
                            'numero' => $asientoNumero++,
                            'piso' => 2,
                            'fila' => $fila,
                            'columna' => $col,
                            'tipo' => 'normal',
                            'precio_adicional' => 0
                        ];
                    }
                }
            }
        }

        return $configuracion;
    }
}
