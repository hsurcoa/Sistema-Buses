-- SCRIPT DE IMPLEMENTACIÓN DE MÓDULO DE ENCOMIENDAS
-- Basado en el Informe Técnico 30/12/2025

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabla de Configuración de Tarifas
DROP TABLE IF EXISTS `tarifas_encomienda`;
CREATE TABLE `tarifas_encomienda` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ruta_id` INT(11) NOT NULL,
  `precio_base` DECIMAL(10,2) NOT NULL DEFAULT 10.00,
  `precio_por_kg` DECIMAL(10,2) NOT NULL DEFAULT 2.00,
  `porcentaje_seguro` DECIMAL(5,2) NOT NULL DEFAULT 2.00, -- 2% del valor declarado
  `estado` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_tarifa_ruta` (`ruta_id`),
  CONSTRAINT `fk_tarifa_ruta` FOREIGN KEY (`ruta_id`) REFERENCES `rutas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabla Principal de Encomiendas
-- Reemplaza la estructura anterior para adaptarse al nuevo diseño
DROP TABLE IF EXISTS `encomiendas`;
CREATE TABLE `encomiendas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo_guia` VARCHAR(20) NOT NULL, -- Ej: ENC-2025-001
  `viaje_id` INT(11) NOT NULL,
  
  -- Datos de Remitente y Destinatario (Vinculados a tabla clientes si existe, o texto libre)
  `remitente_id` INT(11) NULL, 
  `remitente_nombre` VARCHAR(150) NOT NULL,
  `remitente_dni` VARCHAR(20) NOT NULL,
  
  `destinatario_id` INT(11) NULL,
  `destinatario_nombre` VARCHAR(150) NOT NULL,
  `destinatario_dni` VARCHAR(20) NULL,
  `destinatario_telefono` VARCHAR(20) NOT NULL,
  
  -- Seguridad y Logística
  `clave_retiro` VARCHAR(4) NULL, -- Código de 4 dígitos para retirar
  `usuario_creacion_id` INT(11) NOT NULL,
  `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  -- Totales
  `total_pagar` DECIMAL(10,2) NOT NULL,
  `estado_pago` ENUM('PAGADO','PENDIENTE') DEFAULT 'PAGADO',
  
  -- Estado del Envío
  `estado` ENUM('REGISTRADO','EN_ALMACEN_ORIGEN','EN_RUTA','EN_DESTINO','ENTREGADO') DEFAULT 'REGISTRADO',
  `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo_guia` (`codigo_guia`),
  KEY `fk_encomienda_viaje` (`viaje_id`),
  KEY `fk_encomienda_usuario` (`usuario_creacion_id`),
  CONSTRAINT `fk_encomienda_viaje` FOREIGN KEY (`viaje_id`) REFERENCES `viajes` (`id`),
  CONSTRAINT `fk_encomienda_usuario` FOREIGN KEY (`usuario_creacion_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabla de Detalles del Paquete
DROP TABLE IF EXISTS `detalles_encomienda`;
CREATE TABLE `detalles_encomienda` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `encomienda_id` INT(11) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `peso_kg` DECIMAL(10,2) NOT NULL,
  `tipo_carga` ENUM('GENERAL','DOCUMENTOS','FRAGIL','ELECTRONICA','PERECIBLE') DEFAULT 'GENERAL',
  `valor_declarado` DECIMAL(10,2) DEFAULT 0.00,
  `precio_calculado` DECIMAL(10,2) NOT NULL, -- Precio específico de este ítem
  
  PRIMARY KEY (`id`),
  KEY `fk_detalle_encomienda` (`encomienda_id`),
  CONSTRAINT `fk_detalle_encomienda` FOREIGN KEY (`encomienda_id`) REFERENCES `encomiendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Insertar datos de ejemplo para Tarifas (Asumiendo que existen rutas con ID 1, 2, etc)
-- Ajustar IDs según la base de datos real
INSERT INTO `tarifas_encomienda` (`ruta_id`, `precio_base`, `precio_por_kg`)
SELECT id, 15.00, 2.50 FROM `rutas` LIMIT 5; 

SET FOREIGN_KEY_CHECKS = 1;
