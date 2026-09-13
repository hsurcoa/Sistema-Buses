-- =====================================================
-- SCRIPT DE CREACIÓN DE TABLAS PARA MÓDULO DE CAJA
-- =====================================================

-- Tabla: cajas_sesiones
CREATE TABLE IF NOT EXISTS `cajas_sesiones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `fecha_apertura` DATETIME NOT NULL,
  `fecha_cierre` DATETIME NULL DEFAULT NULL,
  `monto_inicial` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_final_sistema` DECIMAL(10,2) NULL DEFAULT NULL,
  `monto_final_real` DECIMAL(10,2) NULL DEFAULT NULL,
  `diferencia` DECIMAL(10,2) NULL DEFAULT NULL,
  `estado` ENUM('ABIERTA','CERRADA') NOT NULL DEFAULT 'ABIERTA',
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_apertura` (`fecha_apertura`),
  CONSTRAINT `fk_caja_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: movimientos_caja
CREATE TABLE IF NOT EXISTS `movimientos_caja` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `sesion_id` INT(11) NOT NULL,
  `tipo_movimiento` ENUM('INGRESO','EGRESO') NOT NULL,
  `origen_modulo` ENUM('PASAJE','ENCOMIENDA','GASTO','APERTURA','OTRO') NOT NULL,
  `referencia_id` INT(11) NULL DEFAULT NULL COMMENT 'ID del boleto, encomienda, etc.',
  `monto` DECIMAL(10,2) NOT NULL,
  `descripcion` VARCHAR(255) NULL DEFAULT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sesion` (`sesion_id`),
  KEY `idx_tipo` (`tipo_movimiento`),
  KEY `idx_origen` (`origen_modulo`),
  KEY `idx_fecha` (`fecha_registro`),
  CONSTRAINT `fk_movimiento_sesion` FOREIGN KEY (`sesion_id`) REFERENCES `cajas_sesiones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar creación
SELECT 'Tablas creadas exitosamente' AS resultado;
