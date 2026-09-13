-- Actualizar tabla viajes para incluir todos los campos necesarios
-- Ejecutar este script para agregar los campos faltantes

USE venta_pasajes;

-- Agregar columnas faltantes a la tabla viajes
ALTER TABLE `viajes` 
ADD COLUMN IF NOT EXISTS `tipo_bus_id` INT(11) NULL AFTER `ruta_id`,
ADD COLUMN IF NOT EXISTS `terminal_origen_id` INT(11) NULL AFTER `tipo_bus_id`,
ADD COLUMN IF NOT EXISTS `terminal_destino_id` INT(11) NULL AFTER `terminal_origen_id`,
ADD COLUMN IF NOT EXISTS `hora_salida` TIME NULL AFTER `fecha_salida`,
ADD COLUMN IF NOT EXISTS `hora_llegada` TIME NULL AFTER `fecha_llegada_estimada`,
ADD COLUMN IF NOT EXISTS `precio_base` DECIMAL(10,2) DEFAULT 0.00 AFTER `hora_llegada`,
ADD COLUMN IF NOT EXISTS `tipo_servicio` VARCHAR(50) DEFAULT 'Ejecutivo' AFTER `precio_base`,
ADD COLUMN IF NOT EXISTS `servicios_incluidos` TEXT NULL COMMENT 'JSON con servicios incluidos' AFTER `tipo_servicio`,
ADD COLUMN IF NOT EXISTS `notas` TEXT NULL AFTER `servicios_incluidos`,
ADD COLUMN IF NOT EXISTS `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `notas`,
ADD COLUMN IF NOT EXISTS `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `fecha_creacion`;

-- Agregar índices para las nuevas columnas
ALTER TABLE `viajes`
ADD INDEX IF NOT EXISTS `idx_tipo_bus` (`tipo_bus_id`),
ADD INDEX IF NOT EXISTS `idx_terminal_origen` (`terminal_origen_id`),
ADD INDEX IF NOT EXISTS `idx_terminal_destino` (`terminal_destino_id`),
ADD INDEX IF NOT EXISTS `idx_fecha_salida` (`fecha_salida`),
ADD INDEX IF NOT EXISTS `idx_estado` (`estado`);

-- Agregar foreign keys para las nuevas columnas
ALTER TABLE `viajes`
ADD CONSTRAINT `fk_viajes_tipo_bus` FOREIGN KEY (`tipo_bus_id`) REFERENCES `tipos_buses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `fk_viajes_terminal_origen` FOREIGN KEY (`terminal_origen_id`) REFERENCES `terminales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `fk_viajes_terminal_destino` FOREIGN KEY (`terminal_destino_id`) REFERENCES `terminales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Actualizar el enum de estado para incluir más opciones
ALTER TABLE `viajes` 
MODIFY COLUMN `estado` ENUM('Activo', 'Programado', 'Inactivo', 'programado', 'abordando', 'en_ruta', 'finalizado', 'cancelado') DEFAULT 'Programado';

-- CORRECCIÓN DE ERROR: Hacer que bus_id sea nullable
-- Esto es necesario porque al crear una ruta programada, aún no asignamos un bus específico (bus_id), sino un tipo de bus (tipo_bus_id)
ALTER TABLE `viajes` MODIFY COLUMN `bus_id` INT(11) NULL DEFAULT NULL;

SELECT 'Tabla viajes actualizada correctamente' AS resultado;
