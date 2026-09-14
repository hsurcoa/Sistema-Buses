<?php
/**
 * Datos del tablero principal.
 *
 * Todo se calcula para un periodo [desde, hasta] y una sucursal (null = todas).
 * La fecha de una venta es la fecha de pago (fecha_pago) o, en ventas antiguas
 * sin ese dato, la fecha de registro del boleto.
 */
class DashboardModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    private function bindAll(array $params)
    {
        foreach ($params as $k => $v) {
            $this->db->bind($k, $v);
        }
    }

    private function condSucursal($columna, $sucursal)
    {
        return $sucursal ? [" AND {$columna} = :suc", [':suc' => (int) $sucursal]] : ['', []];
    }

    /** Ingresos y boletos vendidos del periodo, con desglose efectivo / QR. */
    public function resumenVentas($desde, $hasta, $sucursal)
    {
        [$cond, $p] = $this->condSucursal('b.sucursal_id', $sucursal);
        $this->db->query("SELECT COUNT(*) AS boletos,
                                 COALESCE(SUM(b.precio_final), 0) AS ingresos,
                                 COALESCE(SUM(CASE WHEN b.metodo_pago = 'QR' THEN b.precio_final ELSE 0 END), 0) AS ingresos_qr,
                                 COALESCE(SUM(CASE WHEN b.metodo_pago <> 'QR' THEN b.precio_final ELSE 0 END), 0) AS ingresos_efectivo
                          FROM boletos b
                          WHERE b.estado = 'vendido'
                            AND DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) BETWEEN :desde AND :hasta{$cond}");
        $this->bindAll([':desde' => $desde, ':hasta' => $hasta] + $p);
        return $this->db->single();
    }

    /** Ingresos por dia (efectivo y QR) para el grafico. */
    public function ventasPorDia($desde, $hasta, $sucursal)
    {
        [$cond, $p] = $this->condSucursal('b.sucursal_id', $sucursal);
        $this->db->query("SELECT DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) AS dia,
                                 COUNT(*) AS boletos,
                                 SUM(CASE WHEN b.metodo_pago = 'QR' THEN b.precio_final ELSE 0 END) AS qr,
                                 SUM(CASE WHEN b.metodo_pago <> 'QR' THEN b.precio_final ELSE 0 END) AS efectivo
                          FROM boletos b
                          WHERE b.estado = 'vendido'
                            AND DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) BETWEEN :desde AND :hasta{$cond}
                          GROUP BY dia ORDER BY dia");
        $this->bindAll([':desde' => $desde, ':hasta' => $hasta] + $p);
        return $this->db->resultSet();
    }

    /** Con todas las sucursales: ingresos por sucursal. Con una sucursal: por vendedor. */
    public function ventasPorGrupo($desde, $hasta, $sucursal)
    {
        if ($sucursal) {
            $sql = "SELECT CONCAT(u.nombres, ' ', u.apellidos) AS nombre, COUNT(*) AS boletos, SUM(b.precio_final) AS ingresos
                    FROM boletos b JOIN usuarios u ON u.id = b.usuario_vendedor_id
                    WHERE b.estado = 'vendido' AND b.sucursal_id = :suc
                      AND DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) BETWEEN :desde AND :hasta
                    GROUP BY u.id ORDER BY ingresos DESC";
            $params = [':suc' => (int) $sucursal];
        } else {
            $sql = "SELECT COALESCE(t.nombre_sede, 'Sin sucursal') AS nombre, COUNT(*) AS boletos, SUM(b.precio_final) AS ingresos
                    FROM boletos b LEFT JOIN terminales t ON t.id = b.sucursal_id
                    WHERE b.estado = 'vendido'
                      AND DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) BETWEEN :desde AND :hasta
                    GROUP BY b.sucursal_id ORDER BY ingresos DESC";
            $params = [];
        }
        $this->db->query($sql);
        $this->bindAll([':desde' => $desde, ':hasta' => $hasta] + $params);
        return $this->db->resultSet();
    }

    public function rutasMasVendidas($desde, $hasta, $sucursal, $limite = 6)
    {
        [$cond, $p] = $this->condSucursal('b.sucursal_id', $sucursal);
        $this->db->query("SELECT CONCAT(COALESCE(rps.nombre_parada, r.origen), ' → ', COALESCE(rp.nombre_parada, r.destino)) AS tramo,
                                 COUNT(*) AS boletos, SUM(b.precio_final) AS ingresos
                          FROM boletos b
                          JOIN viajes v ON v.id = b.viaje_id
                          JOIN rutas r ON r.id = v.ruta_id
                          LEFT JOIN rutas_paradas rp ON rp.id = b.parada_id
                          LEFT JOIN rutas_paradas rps ON rps.id = b.parada_subida_id
                          WHERE b.estado = 'vendido'
                            AND DATE(COALESCE(b.fecha_pago, b.fecha_reserva)) BETWEEN :desde AND :hasta{$cond}
                          GROUP BY tramo ORDER BY boletos DESC LIMIT " . (int) $limite);
        $this->bindAll([':desde' => $desde, ':hasta' => $hasta] + $p);
        return $this->db->resultSet();
    }

    /** Ocupacion promedio de los viajes que salen en el periodo. */
    public function ocupacion($desde, $hasta, $sucursal)
    {
        [$cond, $p] = $this->condSucursal('v.terminal_origen_id', $sucursal);
        $this->db->query("SELECT COUNT(*) AS viajes,
                                 COALESCE(SUM(x.ocupados), 0) AS asientos_ocupados,
                                 COALESCE(SUM(x.capacidad), 0) AS asientos_totales
                          FROM (
                              SELECT v.id, COALESCE(tb.capacidad, 0) AS capacidad,
                                     (SELECT COUNT(*) FROM boletos b WHERE b.viaje_id = v.id AND b.estado = 'vendido') AS ocupados
                              FROM viajes v
                              LEFT JOIN tipos_buses tb ON tb.id = v.tipo_bus_id
                              WHERE DATE(v.fecha_salida) BETWEEN :desde AND :hasta
                                AND LOWER(v.estado) NOT IN ('cancelado', 'inactivo'){$cond}
                          ) x");
        $this->bindAll([':desde' => $desde, ':hasta' => $hasta] + $p);
        return $this->db->single();
    }

    /** Viajes sin finalizar ordenados por salida; los de fecha pasada se marcan como atrasados. */
    public function proximosViajes($sucursal, $limite = 8)
    {
        [$cond, $p] = $this->condSucursal('v.terminal_origen_id', $sucursal);
        $this->db->query("SELECT v.id, v.fecha_salida, v.hora_salida, v.estado, v.precio_base,
                                 r.origen, r.destino,
                                 ve.placa, COALESCE(tb.capacidad, 0) AS capacidad,
                                 CONCAT(p.nombres, ' ', p.apellidos) AS chofer,
                                 t.nombre_sede AS terminal_origen,
                                 (SELECT COUNT(*) FROM boletos b WHERE b.viaje_id = v.id AND b.estado = 'vendido') AS vendidos,
                                 (SELECT COUNT(*) FROM boletos b WHERE b.viaje_id = v.id AND b.estado = 'reservado') AS reservados,
                                 (v.fecha_salida < NOW()) AS atrasado
                          FROM viajes v
                          JOIN rutas r ON r.id = v.ruta_id
                          LEFT JOIN vehiculos ve ON ve.id = v.bus_id
                          LEFT JOIN tipos_buses tb ON tb.id = v.tipo_bus_id
                          LEFT JOIN personal p ON p.id = v.chofer_id
                          LEFT JOIN terminales t ON t.id = v.terminal_origen_id
                          WHERE LOWER(v.estado) NOT IN ('finalizado', 'cancelado', 'inactivo'){$cond}
                          ORDER BY v.fecha_salida ASC
                          LIMIT " . (int) $limite);
        $this->bindAll($p);
        return $this->db->resultSet();
    }

    /** Cajas abiertas ahora con su efectivo esperado. */
    public function cajasAbiertas($sucursal)
    {
        [$cond, $p] = $this->condSucursal('cs.sucursal_id', $sucursal);
        $this->db->query("SELECT cs.id, cs.fecha_apertura, cs.monto_inicial,
                                 CONCAT(u.nombres, ' ', u.apellidos) AS usuario,
                                 COALESCE(t.nombre_sede, 'Sin sucursal') AS sucursal,
                                 TIMESTAMPDIFF(HOUR, cs.fecha_apertura, NOW()) AS horas,
                                 cs.monto_inicial
                                   + COALESCE((SELECT SUM(m.monto) FROM movimientos_caja m WHERE m.sesion_id = cs.id AND m.tipo_movimiento = 'INGRESO' AND m.origen_modulo <> 'APERTURA' AND m.metodo_pago <> 'QR'), 0)
                                   - COALESCE((SELECT SUM(m.monto) FROM movimientos_caja m WHERE m.sesion_id = cs.id AND m.tipo_movimiento = 'EGRESO'), 0) AS efectivo_esperado,
                                 COALESCE((SELECT SUM(m.monto) FROM movimientos_caja m WHERE m.sesion_id = cs.id AND m.tipo_movimiento = 'INGRESO' AND m.metodo_pago = 'QR'), 0) AS cobrado_qr
                          FROM cajas_sesiones cs
                          JOIN usuarios u ON u.id = cs.usuario_id
                          LEFT JOIN terminales t ON t.id = cs.sucursal_id
                          WHERE cs.estado = 'ABIERTA'{$cond}
                          ORDER BY cs.fecha_apertura");
        $this->bindAll($p);
        return $this->db->resultSet();
    }

    public function resumenEncomiendas($desde, $hasta, $sucursal)
    {
        // PDO sin emulacion no permite repetir un placeholder: uno por subconsulta
        $origen = $sucursal ? ' AND e.sucursal_origen_id = :s%d' : '';
        $destino = $sucursal ? ' AND e.sucursal_destino_id = :s%d' : '';
        $this->db->query("SELECT
                            (SELECT COUNT(*) FROM encomiendas e WHERE DATE(e.fecha_registro) BETWEEN :d1 AND :h1" . sprintf($origen, 1) . ") AS registradas,
                            (SELECT COALESCE(SUM(e.total_pagar), 0) FROM encomiendas e WHERE DATE(e.fecha_registro) BETWEEN :d2 AND :h2" . sprintf($origen, 2) . ") AS monto,
                            (SELECT COUNT(*) FROM encomiendas e WHERE e.estado IN ('REGISTRADO', 'EN_ALMACEN_ORIGEN')" . sprintf($origen, 3) . ") AS por_despachar,
                            (SELECT COUNT(*) FROM encomiendas e WHERE e.estado = 'EN_DESTINO'" . sprintf($destino, 4) . ") AS por_entregar");
        $params = [':d1' => $desde, ':h1' => $hasta, ':d2' => $desde, ':h2' => $hasta];
        if ($sucursal) {
            foreach ([1, 2, 3, 4] as $i) {
                $params[':s' . $i] = (int) $sucursal;
            }
        }
        $this->bindAll($params);
        return $this->db->single();
    }

    /** Fecha de la ultima venta (para orientar cuando el periodo no tiene datos). */
    public function ultimaVenta($sucursal)
    {
        [$cond, $p] = $this->condSucursal('b.sucursal_id', $sucursal);
        $this->db->query("SELECT MAX(COALESCE(b.fecha_pago, b.fecha_reserva)) AS fecha FROM boletos b WHERE b.estado = 'vendido'{$cond}");
        $this->bindAll($p);
        return $this->db->single()->fecha ?? null;
    }

    /**
     * Cosas que requieren accion, cada una con cantidad, texto y enlace.
     * Solo se devuelven las que tienen algo pendiente.
     */
    public function pendientes($sucursal, $esAdmin)
    {
        $items = [];
        $agregar = function ($nivel, $cantidad, $texto, $url, $accion) use (&$items) {
            if ($cantidad > 0) {
                $items[] = compact('nivel', 'cantidad', 'texto', 'url', 'accion');
            }
        };

        [$condV, $pV] = $this->condSucursal('v.terminal_origen_id', $sucursal);
        $this->db->query("SELECT COUNT(*) AS n FROM viajes v WHERE v.fecha_salida < NOW() AND LOWER(v.estado) NOT IN ('finalizado', 'cancelado', 'inactivo'){$condV}");
        $this->bindAll($pV);
        $agregar('critico', (int) $this->db->single()->n, 'viaje(s) con fecha de salida pasada siguen abiertos para la venta', URLROOT . '/ventas/crear_ruta', 'Revisar viajes');

        [$condB, $pB] = $this->condSucursal('b.sucursal_id', $sucursal);
        $this->db->query("SELECT COUNT(*) AS n FROM boletos b JOIN viajes v ON v.id = b.viaje_id
                          WHERE b.estado = 'reservado' AND (b.metodo_pago <> 'QR' OR b.fecha_expiracion_reserva IS NULL)
                            AND (LOWER(v.estado) IN ('finalizado', 'cancelado') OR b.fecha_reserva < NOW() - INTERVAL 2 DAY){$condB}");
        $this->bindAll($pB);
        $agregar('alto', (int) $this->db->single()->n, 'reserva(s) sin cobrar de hace más de 2 días o de viajes ya cerrados bloquean asientos', URLROOT . '/ventas/venta_pasajes', 'Ir a ventas');

        [$condC, $pC] = $this->condSucursal('cs.sucursal_id', $sucursal);
        $this->db->query("SELECT COUNT(*) AS n FROM cajas_sesiones cs WHERE cs.estado = 'ABIERTA' AND cs.fecha_apertura < NOW() - INTERVAL 14 HOUR{$condC}");
        $this->bindAll($pC);
        $agregar('alto', (int) $this->db->single()->n, 'caja(s) abiertas hace más de 14 horas (turno sin cerrar)', URLROOT . '/caja', 'Ver caja');

        $filtroDestino = $sucursal ? ' AND sucursal_destino_id = :suc' : '';
        $this->db->query("SELECT COUNT(*) AS n FROM encomiendas WHERE estado = 'EN_DESTINO'{$filtroDestino}");
        $this->bindAll($sucursal ? [':suc' => (int) $sucursal] : []);
        $agregar('medio', (int) $this->db->single()->n, 'encomienda(s) llegaron y esperan entrega', URLROOT . '/encomiendas', 'Ver encomiendas');

        if ($esAdmin) {
            $this->db->query("SELECT COUNT(*) AS n FROM vehiculos WHERE estado = 1 AND tipo_bus_id IS NULL");
            $agregar('medio', (int) $this->db->single()->n, 'bus(es) sin tipo de bus: no se pueden programar viajes con ellos', URLROOT . '/admin/registrar_buses', 'Completar flota');

            $this->db->query("SELECT COUNT(*) AS n FROM usuarios u JOIN roles r ON r.id = u.rol_id
                              WHERE u.estado = 'activo' AND u.sucursal_id IS NULL AND r.nombre NOT IN ('Administrador', 'Supervisor', 'Chofer', 'Copiloto')");
            $agregar('medio', (int) $this->db->single()->n, 'usuario(s) sin sucursal asignada no pueden abrir caja', URLROOT . '/admin/usuarios', 'Asignar sucursal');

            $this->db->query("SELECT COUNT(*) AS n FROM usuarios WHERE estado = 'activo' AND (username LIKE 'chofer\\\\_%' OR email LIKE '%.sistema.temp' OR email LIKE '%@test.com')");
            $agregar('medio', (int) $this->db->single()->n, 'cuenta(s) automáticas de choferes siguen activas', URLROOT . '/admin/usuarios', 'Revisar usuarios');
        }

        return $items;
    }
}
