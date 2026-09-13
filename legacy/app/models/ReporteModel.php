<?php
class ReporteModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // ✅ CORREGIDO: Consulta directa a tabla rutas
    public function obtenerRutas()
    {
        // Obtenemos solo rutas activas para el filtro
        $this->db->query("SELECT id, origen, destino FROM rutas WHERE estado = 1 ORDER BY origen, destino");
        return $this->db->resultSet();
    }

    // ✅ CORREGIDO: Consulta a tabla vehiculos
    public function obtenerBuses()
    {
        $this->db->query("SELECT id, placa FROM vehiculos ORDER BY placa ASC");
        return $this->db->resultSet();
    }

    // ✅ CORREGIDO: Lógica de búsqueda con JOINs apropiados
    public function buscarViajesConConteo($fecha, $ruta_id = null, $bus_id = null)
    {
        $sql = "SELECT 
                    v.id, 
                    v.fecha_salida, 
                    v.hora_salida, 
                    CONCAT(r.origen, ' - ', r.destino) as nombre_ruta,
                    COALESCE(b.placa, 'Sin Asignar') as placa,
                    COALESCE(CONCAT(p.nombres, ' ', p.apellidos), 'Sin Conductor') as chofer,
                    COALESCE(tb.capacidad, 40) as capacidad,
                    (SELECT COUNT(*) FROM boletos WHERE viaje_id = v.id AND estado IN ('vendido', 'reservado')) as total_pasajeros,
                    (SELECT MIN(DATE(fecha_reserva)) FROM boletos WHERE viaje_id = v.id AND estado IN ('vendido', 'reservado')) as primera_venta,
                    (SELECT MAX(DATE(fecha_reserva)) FROM boletos WHERE viaje_id = v.id AND estado IN ('vendido', 'reservado')) as ultima_venta
                FROM viajes v
                INNER JOIN rutas r ON v.ruta_id = r.id
                LEFT JOIN vehiculos b ON v.bus_id = b.id
                LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
                LEFT JOIN personal p ON v.chofer_id = p.id
                WHERE 1=1";

        // Filtro por fecha - ahora busca tanto por fecha de salida como por fecha de venta
        if (!empty($fecha)) {
            $sql .= " AND (v.fecha_salida = :fecha 
                      OR EXISTS (
                          SELECT 1 FROM boletos 
                          WHERE viaje_id = v.id 
                          AND DATE(fecha_reserva) = :fecha 
                          AND estado IN ('vendido', 'reservado')
                      ))";
        }

        // Filtro por Ruta
        if (!empty($ruta_id)) {
            $sql .= " AND v.ruta_id = :ruta_id";
        }

        // Filtro por Bus
        if (!empty($bus_id)) {
            $sql .= " AND v.bus_id = :bus_id";
        }

        $sql .= " ORDER BY v.fecha_salida ASC, v.hora_salida ASC";

        $this->db->query($sql);

        if (!empty($fecha)) $this->db->bind(':fecha', $fecha);
        if (!empty($ruta_id)) $this->db->bind(':ruta_id', $ruta_id);
        if (!empty($bus_id)) $this->db->bind(':bus_id', $bus_id);

        return $this->db->resultSet();
    }

    // ✅ CORREGIDO: Obtener pasajeros desde boletos + clientes
    public function obtenerPasajerosPorViaje($viaje_id)
    {
        // Obtenemos datos combinados de boletos, clientes y ruta
        $this->db->query("SELECT 
                            b.numero_asiento,
                            c.numero_documento as documento,
                            c.nombres, 
                            c.apellidos,
                            COALESCE(c.celular, '-') as telefono,
                            r.destino as destino,
                            b.estado
                          FROM boletos b
                          INNER JOIN viajes v ON b.viaje_id = v.id
                          INNER JOIN rutas r ON v.ruta_id = r.id
                          INNER JOIN clientes c ON b.cliente_id = c.id
                          WHERE b.viaje_id = :viaje_id AND b.estado IN ('vendido', 'reservado')
                          ORDER BY CAST(b.numero_asiento AS UNSIGNED) ASC");

        $this->db->bind(':viaje_id', $viaje_id);
        return $this->db->resultSet();
    }

    // Información del viaje para cabecera (JOINs consistentes)
    public function obtenerInfoViaje($viaje_id)
    {
        $this->db->query("SELECT 
                            v.id, 
                            v.fecha_salida, 
                            v.hora_salida, 
                            CONCAT(r.origen, ' - ', r.destino) as nombre_ruta,
                            COALESCE(b.placa, 'Sin Asignar') as placa,
                            COALESCE(CONCAT(p.nombres, ' ', p.apellidos), 'Sin Conductor') as chofer
                          FROM viajes v
                          INNER JOIN rutas r ON v.ruta_id = r.id
                          LEFT JOIN vehiculos b ON v.bus_id = b.id
                          LEFT JOIN personal p ON v.chofer_id = p.id
                          WHERE v.id = :id");
        $this->db->bind(':id', $viaje_id);
        return $this->db->single();
    }
    // --- MÉTODOS FINANCIEROS (NUEVO MÓDULO) ---

    public function obtenerVentasHoy()
    {
        $sql = "SELECT SUM(precio_final) as total, COUNT(id) as cantidad 
                FROM boletos 
                WHERE estado = 'vendido' 
                AND DATE(fecha_reserva) = CURDATE()";
        $this->db->query($sql);
        return $this->db->single();
    }

    public function obtenerVentasMes()
    {
        $sql = "SELECT SUM(precio_final) as total 
                FROM boletos 
                WHERE estado = 'vendido' 
                AND MONTH(fecha_reserva) = MONTH(CURRENT_DATE())
                AND YEAR(fecha_reserva) = YEAR(CURRENT_DATE())";
        $this->db->query($sql);
        $res = $this->db->single();

        // Comparativa mes anterior
        $sqlAnt = "SELECT SUM(precio_final) as total 
                   FROM boletos 
                   WHERE estado = 'vendido' 
                   AND MONTH(fecha_reserva) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)
                   AND YEAR(fecha_reserva) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)";
        $this->db->query($sqlAnt);
        $resAnt = $this->db->single();

        return [
            'actual' => $res->total ?? 0,
            'anterior' => $resAnt->total ?? 0
        ];
    }

    public function obtenerMejorRutaMes()
    {
        $sql = "SELECT 
                    r.origen, 
                    r.destino, 
                    SUM(b.precio_final) as total_ventas,
                    COUNT(b.id) as cantidad_pasajes
                FROM boletos b
                INNER JOIN viajes v ON b.viaje_id = v.id
                INNER JOIN rutas r ON v.ruta_id = r.id
                WHERE b.estado = 'vendido'
                AND MONTH(b.fecha_reserva) = MONTH(CURRENT_DATE())
                GROUP BY r.id
                ORDER BY total_ventas DESC
                LIMIT 1";
        $this->db->query($sql);
        return $this->db->single();
    }

    public function obtenerTendenciaSemanal()
    {
        $sql = "SELECT 
                    DATE(fecha_reserva) as fecha, 
                    SUM(precio_final) as total
                FROM boletos
                WHERE estado = 'vendido'
                AND fecha_reserva >= DATE(NOW()) - INTERVAL 7 DAY
                GROUP BY DATE(fecha_reserva)
                ORDER BY fecha ASC";
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    public function obtenerDesgloseFinanciero($fechaInicio = null, $fechaFin = null)
    {
        $sql = "SELECT 
                    DATE(b.fecha_reserva) as fecha,
                    v.id as viaje_id,
                    CONCAT(r.origen, ' - ', r.destino) as ruta,
                    COALESCE(veh.placa, 'Sin Bus') as bus,
                    veh.id as bus_numero,
                    COUNT(b.id) as pasajes_vendidos,
                    SUM(b.precio_final) as total_ingresos
                FROM boletos b
                INNER JOIN viajes v ON b.viaje_id = v.id
                INNER JOIN rutas r ON v.ruta_id = r.id
                LEFT JOIN vehiculos veh ON v.bus_id = veh.id
                WHERE b.estado = 'vendido' ";

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND DATE(b.fecha_reserva) BETWEEN :inicio AND :fin ";
        } else {
            $sql .= " AND b.fecha_reserva >= DATE(NOW()) - INTERVAL 30 DAY ";
        }

        $sql .= " GROUP BY v.id
                  ORDER BY b.fecha_reserva DESC";

        $this->db->query($sql);

        if ($fechaInicio && $fechaFin) {
            $this->db->bind(':inicio', $fechaInicio);
            $this->db->bind(':fin', $fechaFin);
        }

        return $this->db->resultSet();
    }

    // ✅ NUEVO: Búsqueda histórica por rango de fechas
    public function buscarHistorico($fechaInicio, $fechaFin, $ruta_id = null)
    {
        $sql = "SELECT 
                    v.id, 
                    v.fecha_salida, 
                    v.hora_salida, 
                    CONCAT(r.origen, ' - ', r.destino) as nombre_ruta,
                    COALESCE(b.placa, 'Sin Asignar') as placa,
                    COALESCE(CONCAT(p.nombres, ' ', p.apellidos), 'Sin Conductor') as chofer,
                    COALESCE(tb.capacidad, 40) as capacidad,
                    (SELECT COUNT(*) FROM boletos WHERE viaje_id = v.id AND estado IN ('vendido', 'reservado')) as total_pasajeros
                FROM viajes v
                INNER JOIN rutas r ON v.ruta_id = r.id
                LEFT JOIN vehiculos b ON v.bus_id = b.id
                LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
                LEFT JOIN personal p ON v.chofer_id = p.id
                WHERE v.fecha_salida BETWEEN :fecha_inicio AND :fecha_fin";

        if (!empty($ruta_id)) {
            $sql .= " AND v.ruta_id = :ruta_id";
        }

        $sql .= " ORDER BY v.fecha_salida DESC, v.hora_salida ASC";

        $this->db->query($sql);
        $this->db->bind(':fecha_inicio', $fechaInicio);
        $this->db->bind(':fecha_fin', $fechaFin);

        if (!empty($ruta_id)) {
            $this->db->bind(':ruta_id', $ruta_id);
        }

        return $this->db->resultSet();
    }

    // ✅ NUEVO: Búsqueda de pasajero individual
    public function buscarPasajero($criterio)
    {
        $this->db->query("SELECT 
                            c.id, 
                            c.nombres, 
                            c.apellidos, 
                            c.numero_documento,
                            v.fecha_salida,
                            v.hora_salida,
                            CONCAT(r.origen, ' - ', r.destino) as ruta,
                            b.numero_asiento,
                            b.precio_final,
                            b.estado,
                            v.id as viaje_id
                          FROM clientes c
                          INNER JOIN boletos b ON c.id = b.cliente_id
                          INNER JOIN viajes v ON b.viaje_id = v.id
                          INNER JOIN rutas r ON v.ruta_id = r.id
                          WHERE c.numero_documento LIKE :criterio 
                             OR c.apellidos LIKE :criterio 
                             OR c.nombres LIKE :criterio
                          ORDER BY v.fecha_salida DESC");

        $this->db->bind(':criterio', '%' . $criterio . '%');
        return $this->db->resultSet();
    }
}
