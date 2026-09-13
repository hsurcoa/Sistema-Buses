-- =====================================================
-- Script completo: Actualizar tabla viajes e insertar ejemplos
-- Base de datos: venta_pasajes
-- =====================================================

USE venta_pasajes;

-- =====================================================
-- PASO 1: Actualizar estructura de la tabla viajes
-- =====================================================

-- Verificar si las columnas ya existen antes de agregarlas
SET @dbname = DATABASE();
SET @tablename = 'viajes';

-- Agregar columnas si no existen
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'tipo_bus_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE viajes ADD COLUMN tipo_bus_id INT(11) NULL AFTER ruta_id', 
    'SELECT "Column tipo_bus_id already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'terminal_origen_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE viajes ADD COLUMN terminal_origen_id INT(11) NULL AFTER tipo_bus_id', 
    'SELECT "Column terminal_origen_id already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'terminal_destino_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE viajes ADD COLUMN terminal_destino_id INT(11) NULL AFTER terminal_origen_id', 
    'SELECT "Column terminal_destino_id already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'hora_salida');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE viajes ADD COLUMN hora_salida TIME NULL AFTER fecha_salida', 
    'SELECT "Column hora_salida already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'hora_llegada');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE viajes ADD COLUMN hora_llegada TIME NULL AFTER fecha_llegada_estimada', 
    'SELECT "Column hora_llegada already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'precio_base');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE viajes ADD COLUMN precio_base DECIMAL(10,2) DEFAULT 0.00 AFTER hora_llegada', 
    'SELECT "Column precio_base already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'tipo_servicio');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE viajes ADD COLUMN tipo_servicio VARCHAR(50) DEFAULT "Ejecutivo" AFTER precio_base', 
    'SELECT "Column tipo_servicio already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'servicios_incluidos');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE viajes ADD COLUMN servicios_incluidos TEXT NULL COMMENT "JSON con servicios incluidos" AFTER tipo_servicio', 
    'SELECT "Column servicios_incluidos already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'notas');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE viajes ADD COLUMN notas TEXT NULL AFTER servicios_incluidos', 
    'SELECT "Column notas already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'fecha_creacion');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE viajes ADD COLUMN fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER notas', 
    'SELECT "Column fecha_creacion already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'fecha_actualizacion');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE viajes ADD COLUMN fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER fecha_creacion', 
    'SELECT "Column fecha_actualizacion already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PASO 2: Modificar el enum de estado
-- =====================================================
ALTER TABLE viajes 
MODIFY COLUMN estado ENUM('Activo', 'Programado', 'Inactivo', 'programado', 'abordando', 'en_ruta', 'finalizado', 'cancelado') DEFAULT 'Programado';

-- =====================================================
-- PASO 3: Insertar datos de ejemplo
-- =====================================================

-- Ejemplo 1: Ruta La Paz → Copacabana (Servicio Ejecutivo)
INSERT INTO viajes (
    ruta_id,
    tipo_bus_id,
    terminal_origen_id,
    terminal_destino_id,
    fecha_salida,
    hora_salida,
    fecha_llegada_estimada,
    hora_llegada,
    precio_base,
    tipo_servicio,
    servicios_incluidos,
    notas,
    estado,
    fecha_creacion
) VALUES (
    6,  -- Ruta: LA PAZ → COPACABANA
    1,  -- Tipo de bus: BUS DE 45 ASIENTOS
    8,  -- Terminal origen: La Paz - Central
    10, -- Terminal destino: Copacabana
    '2025-12-21',
    '08:00:00',
    '2025-12-21 11:30:00',
    '11:30:00',
    35.00,
    'Ejecutivo',
    '["wifi","ac","bano","seguro"]',
    'Viaje directo sin paradas intermedias. Incluye vista panorámica del Lago Titicaca.',
    'Activo',
    NOW()
);

-- Ejemplo 2: Ruta La Paz → Sorata (Servicio Platino)
INSERT INTO viajes (
    ruta_id,
    tipo_bus_id,
    terminal_origen_id,
    terminal_destino_id,
    fecha_salida,
    hora_salida,
    fecha_llegada_estimada,
    hora_llegada,
    precio_base,
    tipo_servicio,
    servicios_incluidos,
    notas,
    estado,
    fecha_creacion
) VALUES (
    7,  -- Ruta: LA PAZ → SORATA
    3,  -- Tipo de bus: BUS DE 49 ASIENTOS (2 pisos)
    8,  -- Terminal origen: La Paz - Central
    9,  -- Terminal destino: El Alto (conexión a Sorata)
    '2025-12-22',
    '14:30:00',
    '2025-12-22 18:00:00',
    '18:00:00',
    45.00,
    'Platino',
    '["wifi","ac","tv","snack","bano","usb","seguro"]',
    'Servicio premium con snacks incluidos. Ruta escénica por los Andes.',
    'Programado',
    NOW()
);

-- =====================================================
-- PASO 4: Verificar los datos insertados
-- =====================================================

SELECT 
    v.id,
    CONCAT(r.origen, ' → ', r.destino) AS ruta,
    DATE_FORMAT(v.fecha_salida, '%d/%m/%Y') AS fecha,
    v.hora_salida,
    v.hora_llegada,
    CONCAT('Bs. ', v.precio_base) AS precio,
    v.tipo_servicio,
    tb.nombre AS tipo_bus,
    to.nombre_sede AS terminal_origen,
    td.nombre_sede AS terminal_destino,
    v.estado
FROM viajes v
INNER JOIN rutas r ON v.ruta_id = r.id
LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
LEFT JOIN terminales to ON v.terminal_origen_id = to.id
LEFT JOIN terminales td ON v.terminal_destino_id = td.id
WHERE v.id IN (LAST_INSERT_ID(), LAST_INSERT_ID() - 1)
ORDER BY v.id DESC;

SELECT '✅ Tabla actualizada y 2 ejemplos insertados correctamente' AS resultado;
