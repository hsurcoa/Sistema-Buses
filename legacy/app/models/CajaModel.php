<?php
class CajaModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Verificar si el usuario tiene caja abierta
    public function verificarCajaAbierta($usuarioId)
    {
        $this->db->query("SELECT * FROM cajas_sesiones WHERE usuario_id = :usuario_id AND estado = 'ABIERTA'");
        $this->db->bind(':usuario_id', $usuarioId);
        return $this->db->single();
    }

    // Abrir Caja
    public function abrirCaja($usuarioId, $montoInicial)
    {
        try {
            $this->db->beginTransaction();

            // 1. Crear Sesión
            $this->db->query("INSERT INTO cajas_sesiones (usuario_id, monto_inicial, fecha_apertura) VALUES (:usuario_id, :monto, NOW())");
            $this->db->bind(':usuario_id', $usuarioId);
            $this->db->bind(':monto', $montoInicial);
            $this->db->execute();
            $sesionId = $this->db->lastInsertId();

            // 2. Registrar Movimiento Inicial
            if ($montoInicial > 0) {
                $this->registrarMovimiento($sesionId, 'INGRESO', 'APERTURA', null, $montoInicial, 'Monto inicial de apertura');
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Registrar un movimiento (Pasaje, Encomienda, Gasto)
    public function registrarMovimiento($sesionId, $tipo, $origen, $referenciaId, $monto, $descripcion)
    {
        $this->db->query("INSERT INTO movimientos_caja (sesion_id, tipo_movimiento, origen_modulo, referencia_id, monto, descripcion) 
                          VALUES (:sesion, :tipo, :origen, :ref, :monto, :desc)");
        $this->db->bind(':sesion', $sesionId);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':origen', $origen);
        $this->db->bind(':ref', $referenciaId);
        $this->db->bind(':monto', $monto);
        $this->db->bind(':desc', $descripcion);
        return $this->db->execute();
    }

    // Obtener detalles de la sesión actual para el cierre
    public function obtenerResumenSesion($sesionId)
    {
        // - La apertura se guarda como movimiento INGRESO/APERTURA y ademas en
        //   cajas_sesiones.monto_inicial: sumarla en los ingresos la contaba DOS veces
        //   (el efectivo esperado salia inflado en el monto de apertura).
        // - El efectivo esperado solo cuenta ingresos en EFECTIVO: los cobros QR van
        //   directo a la cuenta del dueño y se informan aparte.
        $this->db->query("SELECT
                            COALESCE(SUM(CASE WHEN tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA' THEN monto ELSE 0 END), 0) as total_ingresos,
                            COALESCE(SUM(CASE WHEN tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA' AND metodo_pago = 'QR' THEN monto ELSE 0 END), 0) as total_qr,
                            COALESCE(SUM(CASE WHEN tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA' AND metodo_pago <> 'QR' THEN monto ELSE 0 END), 0) as total_efectivo,
                            COALESCE(SUM(CASE WHEN tipo_movimiento = 'EGRESO' THEN monto ELSE 0 END), 0) as total_egresos,
                            (SELECT monto_inicial FROM cajas_sesiones WHERE id = :sesion) as monto_inicial
                          FROM movimientos_caja 
                          WHERE sesion_id = :sesion");
        $this->db->bind(':sesion', $sesionId);
        return $this->db->single();
    }

    // Cerrar Caja
    public function cerrarCaja($sesionId, $montoReal)
    {
        $resumen = $this->obtenerResumenSesion($sesionId);
        $sistema = ($resumen->monto_inicial + $resumen->total_efectivo) - $resumen->total_egresos;
        $diferencia = $montoReal - $sistema;

        $this->db->query("UPDATE cajas_sesiones SET 
                          fecha_cierre = NOW(), 
                          monto_final_sistema = :sistema, 
                          monto_final_real = :real, 
                          diferencia = :dif, 
                          estado = 'CERRADA' 
                          WHERE id = :id");
        $this->db->bind(':sistema', $sistema);
        $this->db->bind(':real', $montoReal);
        $this->db->bind(':dif', $diferencia);
        $this->db->bind(':id', $sesionId);
        return $this->db->execute();
    }

    // Validar si una contraseúa pertenece a algún administrador (Rol ID 1)
    public function validarCredencialesAdmin($password)
    {
        // 1. Obtener todos los usuarios con rol de Admin (Asumimos rol_id = 1 o 'ADMINISTRADOR')
        // Ajustar según tu tabla de roles. Por defecto intentamos rol_id = 1
        $this->db->query("SELECT password FROM usuarios WHERE rol_id = 1 AND estado = 1");
        $admins = $this->db->resultSet();

        foreach ($admins as $admin) {
            if (password_verify($password, $admin->password)) {
                return true; // Contraseña válida para al menos un admin
            }
        }
        return false;
    }

    // Obtener sesión completa por ID para reporte
    public function obtenerSesionPorId($id)
    {
        $this->db->query("SELECT cs.*, 
                          CONCAT(u.nombres, ' ', u.apellidos) as cajero_nombre,
                          (SELECT COALESCE(SUM(monto), 0) FROM movimientos_caja WHERE sesion_id = cs.id AND tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA') as total_ingresos,
                          (SELECT COALESCE(SUM(monto), 0) FROM movimientos_caja WHERE sesion_id = cs.id AND tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA' AND metodo_pago = 'QR') as total_qr,
                          (SELECT SUM(monto) FROM movimientos_caja WHERE sesion_id = cs.id AND tipo_movimiento = 'EGRESO') as total_egresos
                          FROM cajas_sesiones cs
                          INNER JOIN usuarios u ON cs.usuario_id = u.id
                          WHERE cs.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // --- NUEVO: REPORTE DE INGRESOS ---
    public function obtenerReporteIngresos($fechaInicio, $fechaFin)
    {
        // Ajustamos la hora para cubrir todo el día
        $inicioFull = $fechaInicio . ' 00:00:00';
        $finFull = $fechaFin . ' 23:59:59';

        $sql = "SELECT 
                    m.id,
                    m.fecha_creacion,
                    m.origen_modulo as tipo,
                    m.descripcion,
                    m.monto,
                    CONCAT(u.nombres, ' ', u.apellidos) as usuario
                FROM movimientos_caja m
                INNER JOIN cajas_sesiones s ON m.sesion_id = s.id
                INNER JOIN usuarios u ON s.usuario_id = u.id
                WHERE m.tipo_movimiento = 'INGRESO'
                AND m.fecha_creacion BETWEEN :inicio AND :fin
                ORDER BY m.fecha_creacion DESC";

        $this->db->query($sql);
        $this->db->bind(':inicio', $inicioFull);
        $this->db->bind(':fin', $finFull);

        return $this->db->resultSet();
    }

    // --- NUEVO: OBTENER MOVIMIENTOS POR SESIÓN ---
    public function obtenerMovimientosPorSesion($sesionId)
    {
        $this->db->query("SELECT 
                            id,
                            tipo_movimiento,
                            origen_modulo,
                            monto,
                            descripcion,
                            fecha_creacion
                          FROM movimientos_caja 
                          WHERE sesion_id = :sesion
                          ORDER BY fecha_creacion ASC");
        $this->db->bind(':sesion', $sesionId);
        return $this->db->resultSet();
    }
    // --- NUEVO: OBTENER SESIONES CERRADAS POR FECHA ---
    public function obtenerSesionesCerradas($fechaInicio, $fechaFin)
    {
        $inicioFull = $fechaInicio . ' 00:00:00';
        $finFull = $fechaFin . ' 23:59:59';

        $sql = "SELECT 
                    cs.id,
                    cs.usuario_id,
                    CONCAT(u.nombres, ' ', u.apellidos) as cajero_nombre,
                    cs.fecha_apertura,
                    cs.fecha_cierre,
                    cs.monto_inicial,
                    cs.monto_final_sistema,
                    cs.monto_final_real,
                    cs.diferencia,
                    (SELECT COALESCE(SUM(monto), 0) FROM movimientos_caja
                     WHERE sesion_id = cs.id AND tipo_movimiento = 'INGRESO' AND origen_modulo <> 'APERTURA') as total_ingresos,
                    (SELECT SUM(monto) FROM movimientos_caja 
                     WHERE sesion_id = cs.id AND tipo_movimiento = 'EGRESO') as total_egresos
                FROM cajas_sesiones cs
                INNER JOIN usuarios u ON cs.usuario_id = u.id
                WHERE cs.estado = 'CERRADA'
                AND cs.fecha_cierre BETWEEN :inicio AND :fin
                ORDER BY cs.fecha_cierre DESC";

        $this->db->query($sql);
        $this->db->bind(':inicio', $inicioFull);
        $this->db->bind(':fin', $finFull);

        return $this->db->resultSet();
    }

    // --- NUEVO: ESTADÍSTICAS DEL PERÍODO ---
    public function obtenerEstadisticasPeriodo($fechaInicio, $fechaFin)
    {
        $inicioFull = $fechaInicio . ' 00:00:00';
        $finFull = $fechaFin . ' 23:59:59';

        $sql = "SELECT 
                    COUNT(*) as total_sesiones,
                    SUM(monto_inicial) as suma_inicial,
                    SUM(monto_final_sistema) as suma_sistema,
                    SUM(monto_final_real) as suma_real,
                    SUM(diferencia) as suma_diferencias,
                    AVG(monto_final_sistema) as promedio_sistema
                FROM cajas_sesiones
                WHERE estado = 'CERRADA'
                AND fecha_cierre BETWEEN :inicio AND :fin";

        $this->db->query($sql);
        $this->db->bind(':inicio', $inicioFull);
        $this->db->bind(':fin', $finFull);

        return $this->db->single();
    }
}
