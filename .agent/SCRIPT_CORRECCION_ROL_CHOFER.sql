-- ============================================
-- SCRIPT DE CORRECCIÓN: ROL CHOFER FALTANTE
-- Fecha: 2025-12-25
-- Autor: Antigravity AI
-- Descripción: Inserta el rol Chofer y roles operativos adicionales en la tabla roles
-- Base de Datos: venta_pasajes
-- ============================================

-- Verificar estado actual de la tabla roles
SELECT 'Estado Actual de Roles:' AS mensaje;
SELECT id, nombre, descripcion, activo FROM roles ORDER BY id;

-- ============================================
-- PASO 1: INSERTAR EL ROL CHOFER (CRÍTICO)
-- ============================================
SELECT '' AS separador;
SELECT 'PASO 1: Insertando rol Chofer...' AS mensaje;

INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `activo`, `fecha_creacion`) 
VALUES (6, 'Chofer', 'Personal encargado de conducir los vehículos de transporte', 1, NOW());

SELECT 'Rol Chofer insertado exitosamente' AS resultado;

-- ============================================
-- PASO 2: INSERTAR ROLES OPERATIVOS ADICIONALES (OPCIONAL)
-- ============================================
SELECT '' AS separador;
SELECT 'PASO 2: Insertando roles operativos adicionales...' AS mensaje;

INSERT INTO `roles` (`nombre`, `descripcion`, `activo`, `fecha_creacion`) VALUES
('Mecánico', 'Personal encargado del mantenimiento y reparación de vehículos', 1, NOW()),
('Despachador', 'Personal encargado de despachar vehículos y coordinar salidas', 1, NOW()),
('Cajero', 'Personal encargado de operaciones de caja y cobros', 1, NOW())
ON DUPLICATE KEY UPDATE nombre = nombre;

SELECT 'Roles operativos adicionales insertados' AS resultado;

-- ============================================
-- PASO 3: ASIGNAR PERMISOS BÁSICOS AL ROL CHOFER
-- ============================================
SELECT '' AS separador;
SELECT 'PASO 3: Asignando permisos básicos al rol Chofer...' AS mensaje;

-- Asignar permiso de Dashboard principal
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) 
SELECT 6, id FROM `permisos` WHERE clave = 'dashboard.principal'
ON DUPLICATE KEY UPDATE rol_id = rol_id;

SELECT 'Permisos básicos asignados al rol Chofer' AS resultado;

-- ============================================
-- PASO 4: VERIFICACIÓN DE LA INSERCIÓN
-- ============================================
SELECT '' AS separador;
SELECT 'PASO 4: Verificando la inserción...' AS mensaje;

-- Verificar que el rol Chofer existe
SELECT 
    id, 
    nombre, 
    descripcion, 
    activo, 
    fecha_creacion 
FROM `roles` 
WHERE nombre = 'Chofer';

-- Verificar todos los roles activos
SELECT '' AS separador;
SELECT 'Todos los roles activos en el sistema:' AS mensaje;
SELECT id, nombre, descripcion, activo FROM `roles` WHERE activo = 1 ORDER BY nombre;

-- ============================================
-- PASO 5: VERIFICAR REGISTROS DE PERSONAL CON PERFIL CHOFER
-- ============================================
SELECT '' AS separador;
SELECT 'PASO 5: Verificando registros de personal con perfil Chofer...' AS mensaje;

SELECT 
    id, 
    CONCAT(nombres, ' ', apellidos) AS nombre_completo,
    perfil,
    estado,
    created_at
FROM `personal` 
WHERE perfil = 'Chofer'
ORDER BY apellidos, nombres;

-- Contar registros de personal por perfil
SELECT '' AS separador;
SELECT 'Resumen de personal por perfil:' AS mensaje;
SELECT 
    perfil,
    COUNT(*) AS cantidad,
    SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) AS activos,
    SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) AS inactivos
FROM `personal`
WHERE perfil IS NOT NULL
GROUP BY perfil
ORDER BY cantidad DESC;

-- ============================================
-- PASO 6: VERIFICAR PERMISOS ASIGNADOS AL ROL CHOFER
-- ============================================
SELECT '' AS separador;
SELECT 'PASO 6: Verificando permisos asignados al rol Chofer...' AS mensaje;

SELECT 
    r.nombre AS rol,
    p.grupo,
    p.vista,
    p.clave,
    p.descripcion,
    rp.fecha_asignacion
FROM `roles` r
INNER JOIN `rol_permiso` rp ON r.id = rp.rol_id
INNER JOIN `permisos` p ON rp.permiso_id = p.id
WHERE r.nombre = 'Chofer'
ORDER BY p.grupo, p.orden;

-- ============================================
-- RESUMEN FINAL
-- ============================================
SELECT '' AS separador;
SELECT '============================================' AS separador;
SELECT 'RESUMEN DE EJECUCIÓN' AS titulo;
SELECT '============================================' AS separador;

SELECT 
    (SELECT COUNT(*) FROM roles WHERE nombre = 'Chofer') AS rol_chofer_existe,
    (SELECT COUNT(*) FROM roles WHERE activo = 1) AS total_roles_activos,
    (SELECT COUNT(*) FROM personal WHERE perfil = 'Chofer') AS personal_con_perfil_chofer,
    (SELECT COUNT(*) FROM rol_permiso WHERE rol_id = 6) AS permisos_asignados_chofer;

SELECT '' AS separador;
SELECT '✅ Script ejecutado exitosamente' AS mensaje;
SELECT 'El rol Chofer ahora debería aparecer en el dropdown de Perfil' AS resultado;
SELECT '============================================' AS separador;

-- ============================================
-- NOTAS IMPORTANTES
-- ============================================
/*
NOTAS:
1. Este script inserta el rol "Chofer" con ID 6
2. También inserta roles operativos adicionales (Mecánico, Despachador, Cajero)
3. Asigna el permiso básico de dashboard al rol Chofer
4. Los registros existentes de personal con perfil "Chofer" seguirán funcionando
5. El dropdown en personal_form.php ahora mostrará la opción "Chofer"

VALIDACIÓN POST-EJECUCIÓN:
- Verificar que el rol aparezca en: http://localhost/venta-pasajes/admin/registrar_personal
- Intentar crear un nuevo registro de personal con perfil "Chofer"
- Verificar que no haya errores en el log de PHP

ROLLBACK (si es necesario):
DELETE FROM rol_permiso WHERE rol_id = 6;
DELETE FROM roles WHERE id = 6;
DELETE FROM roles WHERE nombre IN ('Mecánico', 'Despachador', 'Cajero');
*/
