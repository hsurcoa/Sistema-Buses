<?php
class DashboardModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Obtener próximas salidas (próximas 2 horas)
    public function obtenerProximasSalidas($limite = 10)
    {
        $horaActual = date('H:i:s');
        $fechaActual = date('Y-m-d');

        // ModIficado para mostrar todas las salidas futuras (Hoy + Futuro)
        // Ordenado cronológicamente
        $sql = "SELECT 
                    v.id,
                    v.fecha_salida,
                    v.hora_salida,
                    CONCAT(r.origen, ' - ', r.destino) as ruta,
                    r.destino,
                    COALESCE(b.placa, 'Sin Asignar') as placa,
                    COALESCE(tb.nombre, 'Estándar') as tipo_bus,
                    COALESCE(tb.capacidad, 40) as capacidad,
                    (SELECT COUNT(*) FROM boletos WHERE viaje_id = v.id AND estado IN ('vendido', 'reservado')) as ocupados,
                    v.estado
                FROM viajes v
                INNER JOIN rutas r ON v.ruta_id = r.id
                LEFT JOIN buses b ON v.bus_id = b.id
                LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
                WHERE (v.fecha_salida > :fecha) 
                   OR (v.fecha_salida = :fecha AND v.hora_salida >= :hora_actual)
                AND v.estado IN ('programado', 'abordando', 'en_venta')
                ORDER BY v.fecha_salida ASC, v.hora_salida ASC
                LIMIT :limite";

        $this->db->query($sql);
        $this->db->bind(':fecha', $fechaActual);
        $this->db->bind(':hora_actual', $horaActual);
        $this->db->bind(':limite', $limite);

        return $this->db->resultSet();
    }

    // Obtener estadísticas generales del día
    public function obtenerEstadisticasDelDia()
    {
        $fechaActual = date('Y-m-d');

        // Total de ingresos del día
        $this->db->query("SELECT COALESCE(SUM(precio_final), 0) as total 
                          FROM boletos 
                          WHERE DATE(fecha_reserva) = :fecha 
                          AND estado IN ('vendido', 'reservado')");
        $this->db->bind(':fecha', $fechaActual);
        $ingresos = $this->db->single();

        // Total de boletos vendidos
        $this->db->query("SELECT COUNT(*) as total 
                          FROM boletos 
                          WHERE DATE(fecha_reserva) = :fecha 
                          AND estado = 'vendido'");
        $this->db->bind(':fecha', $fechaActual);
        $boletos = $this->db->single();

        // Reservas por vencer (próximas 24 horas)
        $this->db->query("SELECT COUNT(*) as total 
                          FROM boletos 
                          WHERE estado = 'reservado' 
                          AND fecha_expiracion_reserva <= DATE_ADD(NOW(), INTERVAL 24 HOUR)");
        $reservas = $this->db->single();

        // Buses en ruta hoy
        $this->db->query("SELECT COUNT(DISTINCT v.id) as total 
                          FROM viajes v 
                          WHERE v.fecha_salida = :fecha 
                          AND v.estado IN ('programado', 'abordando', 'en_ruta')");
        $this->db->bind(':fecha', $fechaActual);
        $buses = $this->db->single();

        return [
            'ingresos' => $ingresos->total,
            'boletos_vendidos' => $boletos->total,
            'reservas_vencer' => $reservas->total,
            'buses_ruta' => $buses->total
        ];
    }

    // Obtener ventas por hora del día (para gráfico)
    public function obtenerVentasPorHora()
    {
        $fechaActual = date('Y-m-d');

        $sql = "SELECT 
                    HOUR(fecha_reserva) as hora,
                    COUNT(*) as cantidad,
                    SUM(precio_final) as monto
                FROM boletos
                WHERE DATE(fecha_reserva) = :fecha
                AND estado IN ('vendido', 'reservado')
                GROUP BY HOUR(fecha_reserva)
                ORDER BY hora ASC";

        $this->db->query($sql);
        $this->db->bind(':fecha', $fechaActual);

        return $this->db->resultSet();
    }

    // Top 5 destinos más vendidos del día
    public function obtenerTopDestinos($limite = 5)
    {
        $fechaActual = date('Y-m-d');

        $sql = "SELECT 
                    r.destino,
                    COUNT(b.id) as total_boletos,
                    SUM(b.precio_final) as total_ingresos
                FROM boletos b
                INNER JOIN viajes v ON b.viaje_id = v.id
                INNER JOIN rutas r ON v.ruta_id = r.id
                WHERE DATE(b.fecha_reserva) = :fecha
                AND b.estado IN ('vendido', 'reservado')
                GROUP BY r.destino
                ORDER BY total_boletos DESC
                LIMIT :limite";

        $this->db->query($sql);
        $this->db->bind(':fecha', $fechaActual);
        $this->db->bind(':limite', $limite);

        return $this->db->resultSet();
    }

    // Verificar alertas críticas
    public function obtenerAlertas()
    {
        $alertas = [];

        // Verificar si hay caja abierta (asumiendo que existe sesión de usuario)
        if (isset($_SESSION['user_id'])) {
            $this->db->query("SELECT * FROM cajas_sesiones 
                             WHERE usuario_id = :user_id 
                             AND estado = 'ABIERTA'");
            $this->db->bind(':user_id', $_SESSION['user_id']);
            $cajaAbierta = $this->db->single();

            if (!$cajaAbierta) {
                $alertas[] = [
                    'tipo' => 'danger',
                    'titulo' => '¡Atención!',
                    'mensaje' => 'La caja de este turno no ha sido abierta. No puede emitir boletos sin apertura de caja.',
                    'accion' => 'Abrir Caja Ahora',
                    'url' => URLROOT . '/caja'
                ];
            }
        }

        // Verificar reservas a punto de vencer
        $this->db->query("SELECT COUNT(*) as total 
                          FROM boletos 
                          WHERE estado = 'reservado' 
                          AND fecha_expiracion_reserva <= DATE_ADD(NOW(), INTERVAL 2 HOUR)");
        $reservasUrgentes = $this->db->single();

        if ($reservasUrgentes->total > 0) {
            $alertas[] = [
                'tipo' => 'warning',
                'titulo' => 'Reservas Urgentes',
                'mensaje' => "Hay {$reservasUrgentes->total} reserva(s) que vencen en las próximas 2 horas.",
                'accion' => 'Ver Reservas',
                'url' => URLROOT . '/ventas/reservas'
            ];
        }

        return $alertas;
    }

    // Encomiendas pendientes de entrega
    public function obtenerEncomiendas()
    {
        $this->db->query("SELECT COUNT(*) as total 
                          FROM encomiendas 
                          WHERE estado = 'en_destino'");
        $resultado = $this->db->single();

        return $resultado->total;
    }
}
