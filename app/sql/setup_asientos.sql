-- ========================================
-- SCRIPT SQL: Tabla de Asientos
-- Sistema de Venta de Pasajes de Bus
-- Fecha: 2025-12-20
-- ========================================

-- Tabla principal de asientos
CREATE TABLE IF NOT EXISTS `asientos` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tipo_bus_id` INT(11) NOT NULL,
    `numero` VARCHAR(10) NOT NULL COMMENT 'Número del asiento (ej: 1, 2, 3A)',
    `piso` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Piso 1, 2 = Piso 2',
    `fila` INT(3) NOT NULL COMMENT 'Fila en el grid del bus',
    `columna` INT(3) NOT NULL COMMENT 'Columna en el grid del bus',
    `tipo` ENUM('normal', 'premium', 'vip') NOT NULL DEFAULT 'normal',
    `estado` ENUM('disponible', 'ocupado', 'mantenimiento', 'bloqueado') NOT NULL DEFAULT 'disponible',
    `precio_adicional` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Precio adicional para asientos premium/vip',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_asiento` (`tipo_bus_id`, `numero`, `piso`),
    KEY `idx_tipo_bus` (`tipo_bus_id`),
    KEY `idx_piso` (`piso`),
    KEY `idx_estado` (`estado`),
    CONSTRAINT `fk_asientos_tipo_bus` 
        FOREIGN KEY (`tipo_bus_id`) 
        REFERENCES `tipos_buses` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de reservas temporales (para el proceso de compra)
CREATE TABLE IF NOT EXISTS `reservas_temporales` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `asiento_id` INT(11) NOT NULL,
    `usuario_id` INT(11) NOT NULL,
    `ruta_id` INT(11) NOT NULL,
    `fecha_viaje` DATE NOT NULL,
    `fecha_reserva` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expira_en` TIMESTAMP NOT NULL COMMENT 'Fecha de expiración de la reserva (15 minutos)',
    `estado` ENUM('activa', 'confirmada', 'expirada', 'cancelada') DEFAULT 'activa',
    PRIMARY KEY (`id`),
    KEY `idx_asiento` (`asiento_id`),
    KEY `idx_usuario` (`usuario_id`),
    KEY `idx_ruta` (`ruta_id`),
    KEY `idx_expira` (`expira_en`),
    KEY `idx_estado` (`estado`),
    CONSTRAINT `fk_reservas_asiento` 
        FOREIGN KEY (`asiento_id`) 
        REFERENCES `asientos` (`id`) 
        ON DELETE CASCADE,
    CONSTRAINT `fk_reservas_usuario` 
        FOREIGN KEY (`usuario_id`) 
        REFERENCES `usuarios` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de historial de asientos (para auditoría)
CREATE TABLE IF NOT EXISTS `asientos_historial` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `asiento_id` INT(11) NOT NULL,
    `accion` VARCHAR(50) NOT NULL COMMENT 'reservado, vendido, liberado, bloqueado',
    `usuario_id` INT(11) DEFAULT NULL,
    `venta_id` INT(11) DEFAULT NULL,
    `ruta_id` INT(11) DEFAULT NULL,
    `fecha_viaje` DATE DEFAULT NULL,
    `detalles` TEXT DEFAULT NULL,
    `fecha_accion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_asiento` (`asiento_id`),
    KEY `idx_usuario` (`usuario_id`),
    KEY `idx_fecha` (`fecha_accion`),
    CONSTRAINT `fk_historial_asiento` 
        FOREIGN KEY (`asiento_id`) 
        REFERENCES `asientos` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- DATOS DE EJEMPLO
-- ========================================

-- Asientos para Bus de 35 asientos (1 piso)
-- Asumiendo que existe un tipo_bus_id = 1
INSERT INTO `asientos` (`tipo_bus_id`, `numero`, `piso`, `fila`, `columna`, `tipo`, `precio_adicional`) VALUES
-- Asientos Premium (Cabina)
(1, '1', 1, 0, 1, 'premium', 20.00),
(1, '2', 1, 0, 2, 'premium', 20.00),

-- Fila 1
(1, '3', 1, 1, 0, 'normal', 0.00),
(1, '4', 1, 1, 1, 'normal', 0.00),
(1, '5', 1, 1, 2, 'normal', 0.00),
(1, '6', 1, 1, 3, 'normal', 0.00),

-- Fila 2
(1, '7', 1, 2, 0, 'normal', 0.00),
(1, '8', 1, 2, 1, 'normal', 0.00),
(1, '9', 1, 2, 2, 'normal', 0.00),
(1, '10', 1, 2, 3, 'normal', 0.00),

-- Fila 3
(1, '11', 1, 3, 0, 'normal', 0.00),
(1, '12', 1, 3, 1, 'normal', 0.00),
(1, '13', 1, 3, 2, 'normal', 0.00),
(1, '14', 1, 3, 3, 'normal', 0.00),

-- Fila 4
(1, '15', 1, 4, 0, 'normal', 0.00),
(1, '16', 1, 4, 1, 'normal', 0.00),
(1, '17', 1, 4, 2, 'normal', 0.00),
(1, '18', 1, 4, 3, 'normal', 0.00),

-- Fila 5
(1, '19', 1, 5, 0, 'normal', 0.00),
(1, '20', 1, 5, 1, 'normal', 0.00),
(1, '21', 1, 5, 2, 'normal', 0.00),
(1, '22', 1, 5, 3, 'normal', 0.00),

-- Fila 6
(1, '23', 1, 6, 0, 'normal', 0.00),
(1, '24', 1, 6, 1, 'normal', 0.00),
(1, '25', 1, 6, 2, 'normal', 0.00),
(1, '26', 1, 6, 3, 'normal', 0.00),

-- Fila 7
(1, '27', 1, 7, 0, 'normal', 0.00),
(1, '28', 1, 7, 1, 'normal', 0.00),
(1, '29', 1, 7, 2, 'normal', 0.00),
(1, '30', 1, 7, 3, 'normal', 0.00),

-- Fila 8
(1, '31', 1, 8, 0, 'normal', 0.00),
(1, '32', 1, 8, 1, 'normal', 0.00),
(1, '33', 1, 8, 2, 'normal', 0.00);

-- Asientos para Bus de 49 asientos (2 pisos)
-- Asumiendo que existe un tipo_bus_id = 2
-- PISO 1
INSERT INTO `asientos` (`tipo_bus_id`, `numero`, `piso`, `fila`, `columna`, `tipo`, `precio_adicional`) VALUES
-- Asientos Premium (Cabina)
(2, '1', 1, 0, 1, 'premium', 25.00),
(2, '2', 1, 0, 2, 'premium', 25.00),

-- Filas 1-7 del Piso 1 (4 asientos por fila = 28 asientos)
(2, '3', 1, 1, 0, 'normal', 0.00),
(2, '4', 1, 1, 1, 'normal', 0.00),
(2, '5', 1, 1, 2, 'normal', 0.00),
(2, '6', 1, 1, 3, 'normal', 0.00),

(2, '7', 1, 2, 0, 'normal', 0.00),
(2, '8', 1, 2, 1, 'normal', 0.00),
(2, '9', 1, 2, 2, 'normal', 0.00),
(2, '10', 1, 2, 3, 'normal', 0.00),

(2, '11', 1, 3, 0, 'normal', 0.00),
(2, '12', 1, 3, 1, 'normal', 0.00),
(2, '13', 1, 3, 2, 'normal', 0.00),
(2, '14', 1, 3, 3, 'normal', 0.00),

(2, '15', 1, 4, 0, 'normal', 0.00),
(2, '16', 1, 4, 1, 'normal', 0.00),
(2, '17', 1, 4, 2, 'normal', 0.00),
(2, '18', 1, 4, 3, 'normal', 0.00),

(2, '19', 1, 5, 0, 'normal', 0.00),
(2, '20', 1, 5, 1, 'normal', 0.00),
(2, '21', 1, 5, 2, 'normal', 0.00),
(2, '22', 1, 5, 3, 'normal', 0.00),

(2, '23', 1, 6, 0, 'normal', 0.00),
(2, '24', 1, 6, 1, 'normal', 0.00),
(2, '25', 1, 6, 2, 'normal', 0.00),
(2, '26', 1, 6, 3, 'normal', 0.00),

(2, '27', 1, 7, 0, 'normal', 0.00),
(2, '28', 1, 7, 1, 'normal', 0.00),
(2, '29', 1, 7, 2, 'normal', 0.00),
(2, '30', 1, 7, 3, 'normal', 0.00);

-- PISO 2 (19 asientos restantes)
INSERT INTO `asientos` (`tipo_bus_id`, `numero`, `piso`, `fila`, `columna`, `tipo`, `precio_adicional`) VALUES
(2, '31', 2, 0, 0, 'normal', 0.00),
(2, '32', 2, 0, 1, 'normal', 0.00),
(2, '33', 2, 0, 2, 'normal', 0.00),
(2, '34', 2, 0, 3, 'normal', 0.00),

(2, '35', 2, 1, 0, 'normal', 0.00),
(2, '36', 2, 1, 1, 'normal', 0.00),
(2, '37', 2, 1, 2, 'normal', 0.00),
(2, '38', 2, 1, 3, 'normal', 0.00),

(2, '39', 2, 2, 0, 'normal', 0.00),
(2, '40', 2, 2, 1, 'normal', 0.00),
(2, '41', 2, 2, 2, 'normal', 0.00),
(2, '42', 2, 2, 3, 'normal', 0.00),

(2, '43', 2, 3, 0, 'normal', 0.00),
(2, '44', 2, 3, 1, 'normal', 0.00),
(2, '45', 2, 3, 2, 'normal', 0.00),
(2, '46', 2, 3, 3, 'normal', 0.00),

(2, '47', 2, 4, 0, 'normal', 0.00),
(2, '48', 2, 4, 1, 'normal', 0.00),
(2, '49', 2, 4, 2, 'normal', 0.00);

-- ========================================
-- PROCEDIMIENTOS ALMACENADOS
-- ========================================

-- Procedimiento para limpiar reservas expiradas
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS `limpiar_reservas_expiradas`()
BEGIN
    DELETE FROM reservas_temporales 
    WHERE expira_en < NOW() 
    AND estado = 'activa';
    
    SELECT ROW_COUNT() as reservas_eliminadas;
END$$
DELIMITER ;

-- Procedimiento para obtener asientos disponibles
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS `obtener_asientos_disponibles`(
    IN p_tipo_bus_id INT,
    IN p_ruta_id INT,
    IN p_fecha_viaje DATE
)
BEGIN
    SELECT 
        a.id,
        a.numero,
        a.piso,
        a.fila,
        a.columna,
        a.tipo,
        a.precio_adicional,
        CASE 
            WHEN v.id IS NOT NULL THEN 'ocupado'
            WHEN rt.id IS NOT NULL AND rt.expira_en > NOW() THEN 'reservado'
            ELSE 'disponible'
        END as estado
    FROM asientos a
    LEFT JOIN ventas v ON a.id = v.asiento_id 
        AND v.ruta_id = p_ruta_id 
        AND DATE(v.fecha_viaje) = p_fecha_viaje
        AND v.estado != 'cancelado'
    LEFT JOIN reservas_temporales rt ON a.id = rt.asiento_id
        AND rt.ruta_id = p_ruta_id
        AND DATE(rt.fecha_viaje) = p_fecha_viaje
        AND rt.estado = 'activa'
        AND rt.expira_en > NOW()
    WHERE a.tipo_bus_id = p_tipo_bus_id
    AND a.estado = 'disponible'
    ORDER BY a.piso ASC, a.fila ASC, a.columna ASC;
END$$
DELIMITER ;

-- ========================================
-- EVENTOS PROGRAMADOS
-- ========================================

-- Evento para limpiar reservas expiradas cada 5 minutos
SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS `evento_limpiar_reservas`
ON SCHEDULE EVERY 5 MINUTE
DO
    CALL limpiar_reservas_expiradas();

-- ========================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ========================================

-- Índice compuesto para búsquedas frecuentes
CREATE INDEX idx_tipo_piso_estado ON asientos(tipo_bus_id, piso, estado);
CREATE INDEX idx_reserva_activa ON reservas_temporales(asiento_id, estado, expira_en);

-- ========================================
-- COMENTARIOS DE DOCUMENTACIÓN
-- ========================================

ALTER TABLE `asientos` COMMENT = 'Tabla que almacena la configuración de asientos para cada tipo de bus';
ALTER TABLE `reservas_temporales` COMMENT = 'Tabla para gestionar reservas temporales durante el proceso de compra';
ALTER TABLE `asientos_historial` COMMENT = 'Tabla de auditoría para rastrear cambios en los asientos';
