-- =====================================================
-- SISTEMA DE ROLES Y PERMISOS - BusDriver (Simplificado)
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `rol_permiso`;
DROP TABLE IF EXISTS `permisos`;
DROP TABLE IF EXISTS `roles`;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- TABLA: roles
-- =====================================================
CREATE TABLE `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL UNIQUE,
  `descripcion` TEXT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: permisos
-- =====================================================
CREATE TABLE `permisos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `grupo` VARCHAR(50) NOT NULL,
  `vista` VARCHAR(100) NOT NULL,
  `clave` VARCHAR(100) NOT NULL UNIQUE,
  `descripcion` TEXT NULL,
  `orden` INT(11) NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_grupo` (`grupo`),
  INDEX `idx_activo` (`activo`),
  INDEX `idx_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: rol_permiso
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
  CONSTRAINT `fk_rol_permiso_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rol_permiso_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS: Roles
-- =====================================================
INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'Administrador', 'Acceso total al sistema', 1),
(2, 'Vendedor', 'Acceso a módulos de venta y consultas', 1),
(3, 'Supervisor', 'Acceso a reportes y supervisión', 1),
(4, 'Contador', 'Acceso a módulos financieros y reportes', 1);

-- =====================================================
-- DATOS: Permisos
-- =====================================================
INSERT INTO `permisos` (`grupo`, `vista`, `clave`, `descripcion`, `orden`) VALUES
('Dashboard', 'Dashboard Principal', 'dashboard.principal', 'Acceso al dashboard principal', 1),
('Procesos', 'Ventas Boletos', 'procesos.ventas_boletos', 'Venta de pasajes de bus', 10),
('Procesos', 'Crear Rutas', 'procesos.crear_rutas', 'Creación de nuevas rutas', 11),
('Procesos', 'Encomienda', 'procesos.encomienda', 'Registro de encomiendas', 12),
('Procesos', 'Consultar Encomiendas', 'procesos.consultar_encomiendas', 'Consulta de encomiendas registradas', 13),
('Reportes', 'Ventas', 'reportes.ventas', 'Reportes de ventas', 20),
('Registros', 'Registrar Buses', 'registros.registrar_buses', 'Registro de buses', 30),
('Registros', 'Asignar Buses', 'registros.asignar_buses', 'Asignación de buses a rutas', 31),
('Registros', 'Registrar Terminal', 'registros.registrar_terminal', 'Registro de terminales', 32),
('Registros', 'Rutas y Paradas', 'registros.rutas_paradas', 'Gestión de rutas y paradas', 33),
('Registros', 'Tipos de Buses', 'registros.tipos_buses', 'Gestión de tipos de buses', 34),
('Administrador', 'Roles y Permisos', 'administrador.roles_permisos', 'Gestión de roles y permisos', 40),
('Administrador', 'Registrar Personal', 'administrador.registrar_personal', 'Registro de personal del sistema', 41);

-- =====================================================
-- DATOS: Asignación de Permisos
-- =====================================================

-- Administrador (Todos los permisos)
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) 
SELECT 1, id FROM permisos WHERE activo = 1;

-- Vendedor
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) 
SELECT 2, id FROM permisos WHERE clave IN (
    'dashboard.principal',
    'procesos.ventas_boletos',
    'procesos.encomienda',
    'procesos.consultar_encomiendas'
);

-- Supervisor
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) 
SELECT 3, id FROM permisos WHERE clave IN (
    'dashboard.principal',
    'procesos.consultar_encomiendas',
    'reportes.ventas',
    'registros.asignar_buses'
);

-- Contador
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) 
SELECT 4, id FROM permisos WHERE clave IN (
    'dashboard.principal',
    'reportes.ventas'
);
