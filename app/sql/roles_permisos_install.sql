-- =====================================================
-- SISTEMA DE ROLES Y PERMISOS - BusDriver
-- =====================================================
-- Fecha: 2025-12-20
-- Descripción: Estructura de base de datos para gestión
--              de roles y permisos del sistema BusDriver
-- =====================================================

-- Desactivar verificación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar tablas si existen (para desarrollo)
DROP TABLE IF EXISTS `rol_permiso`;
DROP TABLE IF EXISTS `permisos`;
DROP TABLE IF EXISTS `roles`;

-- Reactivar verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- TABLA: roles
-- =====================================================
CREATE TABLE `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nombre del rol (ej: Administrador, Vendedor)',
  `descripcion` TEXT NULL COMMENT 'Descripción del rol',
  `activo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Roles del sistema';

-- =====================================================
-- TABLA: permisos
-- =====================================================
CREATE TABLE `permisos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `grupo` VARCHAR(50) NOT NULL COMMENT 'Grupo del módulo (Dashboard, Procesos, Reportes, etc.)',
  `vista` VARCHAR(100) NOT NULL COMMENT 'Nombre de la vista/permiso',
  `clave` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Clave única del permiso (ej: procesos.ventas_boletos)',
  `descripcion` TEXT NULL COMMENT 'Descripción del permiso',
  `orden` INT(11) NOT NULL DEFAULT 0 COMMENT 'Orden de visualización',
  `activo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_grupo` (`grupo`),
  INDEX `idx_activo` (`activo`),
  INDEX `idx_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permisos del sistema';

-- =====================================================
-- TABLA: rol_permiso (Relación Many-to-Many)
-- =====================================================
CREATE TABLE `rol_permiso` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rol_id` INT(11) NOT NULL,
  `permiso_id` INT(11) NOT NULL,
  `fecha_asignacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rol_permiso` (`rol_id`, `permiso_id`),
  INDEX `idx_rol_id` (`rol_id`),
  INDEX `idx_permiso_id` (`permiso_id`),
  CONSTRAINT `fk_rol_permiso_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rol_permiso_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relación entre roles y permisos';

-- =====================================================
-- DATOS SEMILLA: Roles
-- =====================================================
INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'Administrador', 'Acceso total al sistema', 1),
(2, 'Vendedor', 'Acceso a módulos de venta y consultas', 1),
(3, 'Supervisor', 'Acceso a reportes y supervisión', 1),
(4, 'Contador', 'Acceso a módulos financieros y reportes', 1);

-- =====================================================
-- DATOS SEMILLA: Permisos
-- =====================================================
-- Grupo: Dashboard
INSERT INTO `permisos` (`grupo`, `vista`, `clave`, `descripcion`, `orden`) VALUES
('Dashboard', 'Dashboard Principal', 'dashboard.principal', 'Acceso al dashboard principal', 1);

-- Grupo: Procesos
INSERT INTO `permisos` (`grupo`, `vista`, `clave`, `descripcion`, `orden`) VALUES
('Procesos', 'Ventas Boletos', 'procesos.ventas_boletos', 'Venta de pasajes de bus', 10),
('Procesos', 'Crear Rutas', 'procesos.crear_rutas', 'Creación de nuevas rutas', 11),
('Procesos', 'Encomienda', 'procesos.encomienda', 'Registro de encomiendas', 12),
('Procesos', 'Consultar Encomiendas', 'procesos.consultar_encomiendas', 'Consulta de encomiendas registradas', 13);

-- Grupo: Reportes
INSERT INTO `permisos` (`grupo`, `vista`, `clave`, `descripcion`, `orden`) VALUES
('Reportes', 'Ventas', 'reportes.ventas', 'Reportes de ventas', 20);

-- Grupo: Registros
INSERT INTO `permisos` (`grupo`, `vista`, `clave`, `descripcion`, `orden`) VALUES
('Registros', 'Registrar Buses', 'registros.registrar_buses', 'Registro de buses', 30),
('Registros', 'Asignar Buses', 'registros.asignar_buses', 'Asignación de buses a rutas', 31),
('Registros', 'Registrar Terminal', 'registros.registrar_terminal', 'Registro de terminales', 32),
('Registros', 'Rutas y Paradas', 'registros.rutas_paradas', 'Gestión de rutas y paradas', 33),
('Registros', 'Tipos de Buses', 'registros.tipos_buses', 'Gestión de tipos de buses', 34);

-- Grupo: Administrador
INSERT INTO `permisos` (`grupo`, `vista`, `clave`, `descripcion`, `orden`) VALUES
('Administrador', 'Roles y Permisos', 'administrador.roles_permisos', 'Gestión de roles y permisos', 40),
('Administrador', 'Registrar Personal', 'administrador.registrar_personal', 'Registro de personal del sistema', 41);

-- =====================================================
-- DATOS SEMILLA: Asignación de Permisos a Roles
-- =====================================================

-- ROL: Administrador (Acceso Total)
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) 
SELECT 1, id FROM permisos WHERE activo = 1;

-- ROL: Vendedor (Acceso limitado a ventas y consultas)
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) 
SELECT 2, id FROM permisos WHERE clave IN (
    'dashboard.principal',
    'procesos.ventas_boletos',
    'procesos.encomienda',
    'procesos.consultar_encomiendas'
);

-- ROL: Supervisor (Acceso a reportes y supervisión)
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) 
SELECT 3, id FROM permisos WHERE clave IN (
    'dashboard.principal',
    'procesos.consultar_encomiendas',
    'reportes.ventas',
    'registros.asignar_buses'
);

-- ROL: Contador (Acceso a reportes financieros)
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) 
SELECT 4, id FROM permisos WHERE clave IN (
    'dashboard.principal',
    'reportes.ventas'
);

-- =====================================================
-- VISTAS ÚTILES PARA CONSULTAS
-- =====================================================

-- Vista: Permisos por Rol
CREATE OR REPLACE VIEW `v_permisos_por_rol` AS
SELECT 
    r.id AS rol_id,
    r.nombre AS rol_nombre,
    p.id AS permiso_id,
    p.grupo,
    p.vista,
    p.clave,
    rp.fecha_asignacion
FROM roles r
INNER JOIN rol_permiso rp ON r.id = rp.rol_id
INNER JOIN permisos p ON rp.permiso_id = p.id
WHERE r.activo = 1 AND p.activo = 1
ORDER BY r.nombre, p.grupo, p.orden;

-- Vista: Resumen de Roles
CREATE OR REPLACE VIEW `v_resumen_roles` AS
SELECT 
    r.id,
    r.nombre,
    r.descripcion,
    r.activo,
    COUNT(rp.permiso_id) AS total_permisos
FROM roles r
LEFT JOIN rol_permiso rp ON r.id = rp.rol_id
GROUP BY r.id, r.nombre, r.descripcion, r.activo
ORDER BY r.nombre;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

-- Procedimiento: Asignar todos los permisos a un rol
DELIMITER $$
CREATE PROCEDURE `sp_asignar_todos_permisos`(IN p_rol_id INT)
BEGIN
    -- Eliminar permisos existentes
    DELETE FROM rol_permiso WHERE rol_id = p_rol_id;
    
    -- Asignar todos los permisos activos
    INSERT INTO rol_permiso (rol_id, permiso_id)
    SELECT p_rol_id, id FROM permisos WHERE activo = 1;
    
    SELECT CONCAT('Se asignaron ', ROW_COUNT(), ' permisos al rol ID ', p_rol_id) AS mensaje;
END$$
DELIMITER ;

-- Procedimiento: Remover todos los permisos de un rol
DELIMITER $$
CREATE PROCEDURE `sp_remover_todos_permisos`(IN p_rol_id INT)
BEGIN
    DELETE FROM rol_permiso WHERE rol_id = p_rol_id;
    SELECT CONCAT('Se removieron todos los permisos del rol ID ', p_rol_id) AS mensaje;
END$$
DELIMITER ;

-- =====================================================
-- CONSULTAS DE VERIFICACIÓN
-- =====================================================

-- Ver todos los roles
-- SELECT * FROM roles;

-- Ver todos los permisos
-- SELECT * FROM permisos ORDER BY grupo, orden;

-- Ver permisos del Administrador
-- SELECT * FROM v_permisos_por_rol WHERE rol_nombre = 'Administrador';

-- Ver resumen de roles
-- SELECT * FROM v_resumen_roles;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
