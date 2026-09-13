-- ================================================================
-- CONSULTAS SQL DE DIAGNÓSTICO - PROBLEMA BUS DE DOS PISOS
-- Fecha: 25 de diciembre de 2025
-- Propósito: Identificar y corregir problemas de asignación
-- ================================================================

-- ================================================================
-- PASO 1: DIAGNÓSTICO GENERAL
-- ================================================================

-- 1.1 Verificar estructura de la tabla asignaciones_buses
DESCRIBE asignaciones_buses;

-- 1.2 Verificar estructura de la tabla buses
DESCRIBE buses;

-- 1.3 Verificar estructura de la tabla tipos_buses
DESCRIBE tipos_buses;

-- ================================================================
-- PASO 2: IDENTIFICAR BUSES DE DOS PISOS
-- ================================================================

-- 2.1 Listar todos los buses de dos pisos con su estado de asignación
SELECT 
    b.id as bus_id,
    b.placa,
    b.numero_interno,
    b.marca,
    b.estado as bus_estado,
    tb.nombre as tipo_bus,
    tb.pisos,
    tb.capacidad,
    CASE 
        WHEN ab.id IS NULL THEN '❌ SIN ASIGNACIÓN'
        WHEN ab.estado = 0 THEN '⚠️ ASIGNACIÓN INACTIVA'
        WHEN ab.chofer_id IS NULL THEN '⚠️ SIN CHOFER'
        ELSE '✅ ASIGNADO CORRECTAMENTE'
    END as estado_asignacion,
    ab.id as asignacion_id,
    ab.chofer_id,
    CONCAT(u.nombres, ' ', u.apellidos) as nombre_chofer,
    ab.copiloto_id,
    CONCAT(u2.nombres, ' ', u2.apellidos) as nombre_copiloto,
    ab.fecha_asignacion
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
LEFT JOIN usuarios u ON ab.chofer_id = u.id
LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
WHERE tb.pisos = 2
ORDER BY b.id;

-- 2.2 Contar buses de dos pisos por estado de asignación
SELECT 
    CASE 
        WHEN ab.id IS NULL THEN 'SIN ASIGNACIÓN'
        WHEN ab.estado = 0 THEN 'ASIGNACIÓN INACTIVA'
        WHEN ab.chofer_id IS NULL THEN 'SIN CHOFER'
        ELSE 'ASIGNADO CORRECTAMENTE'
    END as estado,
    COUNT(*) as cantidad
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
WHERE tb.pisos = 2 AND b.estado = 1
GROUP BY estado;

-- ================================================================
-- PASO 3: IDENTIFICAR BUSES SIN ASIGNACIÓN
-- ================================================================

-- 3.1 Listar buses de dos pisos SIN asignación de tripulación
SELECT 
    b.id as bus_id,
    b.placa,
    b.numero_interno,
    b.marca,
    tb.nombre as tipo_bus,
    tb.pisos,
    '❌ REQUIERE ASIGNACIÓN' as accion_requerida
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
WHERE tb.pisos = 2 
  AND b.estado = 1
  AND ab.id IS NULL;

-- 3.2 Listar buses con asignación INACTIVA
SELECT 
    b.id as bus_id,
    b.placa,
    b.numero_interno,
    ab.id as asignacion_id,
    ab.estado as estado_asignacion,
    '⚠️ ACTIVAR ASIGNACIÓN' as accion_requerida
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
INNER JOIN asignaciones_buses ab ON b.id = ab.bus_id
WHERE tb.pisos = 2 
  AND b.estado = 1
  AND ab.estado = 0;

-- ================================================================
-- PASO 4: VERIFICAR CHOFERES DISPONIBLES
-- ================================================================

-- 4.1 Listar todos los choferes activos
SELECT 
    u.id,
    CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo,
    u.rol,
    u.estado,
    COUNT(ab.id) as buses_asignados,
    CASE 
        WHEN COUNT(ab.id) = 0 THEN '✅ DISPONIBLE'
        ELSE CONCAT('⚠️ ASIGNADO A ', COUNT(ab.id), ' BUS(ES)')
    END as disponibilidad
FROM usuarios u
LEFT JOIN asignaciones_buses ab ON u.id = ab.chofer_id AND ab.estado = 1
WHERE u.rol IN ('Chofer', 'Piloto', 'Conductor', 'chofer', 'piloto', 'conductor')
  AND u.estado = 1
GROUP BY u.id
ORDER BY buses_asignados ASC, u.nombres ASC;

-- 4.2 Contar choferes por estado
SELECT 
    CASE 
        WHEN COUNT(ab.id) = 0 THEN 'DISPONIBLE'
        WHEN COUNT(ab.id) = 1 THEN 'ASIGNADO A 1 BUS'
        ELSE CONCAT('ASIGNADO A ', COUNT(ab.id), ' BUSES')
    END as estado,
    COUNT(DISTINCT u.id) as cantidad_choferes
FROM usuarios u
LEFT JOIN asignaciones_buses ab ON u.id = ab.chofer_id AND ab.estado = 1
WHERE u.rol IN ('Chofer', 'Piloto', 'Conductor', 'chofer', 'piloto', 'conductor')
  AND u.estado = 1
GROUP BY CASE 
    WHEN COUNT(ab.id) = 0 THEN 'DISPONIBLE'
    WHEN COUNT(ab.id) = 1 THEN 'ASIGNADO A 1 BUS'
    ELSE CONCAT('ASIGNADO A ', COUNT(ab.id), ' BUSES')
END;

-- ================================================================
-- PASO 5: VERIFICAR INTEGRIDAD REFERENCIAL
-- ================================================================

-- 5.1 Verificar asignaciones con chofer_id inválido
SELECT 
    ab.id as asignacion_id,
    ab.bus_id,
    ab.chofer_id,
    '❌ CHOFER NO EXISTE EN USUARIOS' as problema
FROM asignaciones_buses ab
LEFT JOIN usuarios u ON ab.chofer_id = u.id
WHERE ab.chofer_id IS NOT NULL 
  AND u.id IS NULL;

-- 5.2 Verificar asignaciones con bus_id inválido
SELECT 
    ab.id as asignacion_id,
    ab.bus_id,
    ab.chofer_id,
    '❌ BUS NO EXISTE' as problema
FROM asignaciones_buses ab
LEFT JOIN buses b ON ab.bus_id = b.id
WHERE b.id IS NULL;

-- ================================================================
-- PASO 6: SOLUCIONES - CREAR ASIGNACIONES FALTANTES
-- ================================================================

-- 6.1 TEMPLATE: Crear asignación para un bus específico
-- ⚠️ IMPORTANTE: Reemplazar los valores entre [] con datos reales

/*
INSERT INTO asignaciones_buses (bus_id, chofer_id, copiloto_id, estado, fecha_asignacion)
VALUES (
    [ID_DEL_BUS],           -- Ejemplo: 1, 2, 3
    [ID_DEL_CHOFER],        -- Ejemplo: 5, 6, 7
    [ID_DEL_COPILOTO],      -- Ejemplo: 8 o NULL si no tiene
    1,                      -- Estado: 1 = activo
    NOW()                   -- Fecha actual
);
*/

-- 6.2 EJEMPLO: Asignar chofer al primer bus de dos pisos sin asignación
-- ⚠️ EJECUTAR SOLO DESPUÉS DE VERIFICAR LOS IDs

/*
INSERT INTO asignaciones_buses (bus_id, chofer_id, copiloto_id, estado, fecha_asignacion)
SELECT 
    b.id as bus_id,
    (SELECT id FROM usuarios WHERE rol IN ('Chofer', 'Piloto', 'Conductor') AND estado = 1 LIMIT 1) as chofer_id,
    NULL as copiloto_id,
    1 as estado,
    NOW() as fecha_asignacion
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
WHERE tb.pisos = 2 
  AND b.estado = 1
  AND ab.id IS NULL
LIMIT 1;
*/

-- ================================================================
-- PASO 7: SOLUCIONES - ACTIVAR ASIGNACIONES INACTIVAS
-- ================================================================

-- 7.1 TEMPLATE: Activar una asignación específica
/*
UPDATE asignaciones_buses 
SET estado = 1, 
    fecha_actualizacion = NOW()
WHERE id = [ID_DE_LA_ASIGNACION];
*/

-- 7.2 Activar TODAS las asignaciones inactivas de buses de dos pisos
-- ⚠️ CUIDADO: Esto activará todas las asignaciones inactivas
/*
UPDATE asignaciones_buses ab
INNER JOIN buses b ON ab.bus_id = b.id
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
SET ab.estado = 1,
    ab.fecha_actualizacion = NOW()
WHERE tb.pisos = 2 
  AND ab.estado = 0;
*/

-- ================================================================
-- PASO 8: VERIFICACIÓN POST-CORRECCIÓN
-- ================================================================

-- 8.1 Verificar que todos los buses de dos pisos tengan asignación activa
SELECT 
    COUNT(*) as total_buses_dos_pisos,
    SUM(CASE WHEN ab.id IS NOT NULL THEN 1 ELSE 0 END) as buses_con_asignacion,
    SUM(CASE WHEN ab.id IS NULL THEN 1 ELSE 0 END) as buses_sin_asignacion
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
WHERE tb.pisos = 2 AND b.estado = 1;

-- 8.2 Listar resultado final de asignaciones
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    tb.nombre as tipo_bus,
    CONCAT(u.nombres, ' ', u.apellidos) as chofer,
    CONCAT(u2.nombres, ' ', u2.apellidos) as copiloto,
    '✅ LISTO PARA USAR' as estado
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
INNER JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
INNER JOIN usuarios u ON ab.chofer_id = u.id
LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
WHERE tb.pisos = 2 AND b.estado = 1
ORDER BY b.id;

-- ================================================================
-- PASO 9: CONSULTAS DE MANTENIMIENTO
-- ================================================================

-- 9.1 Ver historial de asignaciones de un bus específico
/*
SELECT 
    ab.id,
    ab.bus_id,
    b.placa,
    CONCAT(u.nombres, ' ', u.apellidos) as chofer,
    CONCAT(u2.nombres, ' ', u2.apellidos) as copiloto,
    ab.estado,
    ab.fecha_asignacion,
    ab.fecha_actualizacion
FROM asignaciones_buses ab
INNER JOIN buses b ON ab.bus_id = b.id
LEFT JOIN usuarios u ON ab.chofer_id = u.id
LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
WHERE ab.bus_id = [ID_DEL_BUS]
ORDER BY ab.fecha_asignacion DESC;
*/

-- 9.2 Ver todos los buses asignados a un chofer específico
/*
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    tb.nombre as tipo_bus,
    tb.pisos,
    ab.fecha_asignacion,
    ab.estado
FROM asignaciones_buses ab
INNER JOIN buses b ON ab.bus_id = b.id
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE ab.chofer_id = [ID_DEL_CHOFER]
ORDER BY ab.fecha_asignacion DESC;
*/

-- ================================================================
-- NOTAS IMPORTANTES
-- ================================================================

/*
IMPORTANTE:
1. Ejecutar las consultas de diagnóstico ANTES de hacer cambios
2. Guardar los resultados del diagnóstico para referencia
3. Reemplazar los valores entre [] con IDs reales de tu base de datos
4. Hacer backup de la base de datos antes de ejecutar UPDATEs o INSERTs
5. Verificar los resultados después de cada cambio

ORDEN RECOMENDADO DE EJECUCIÓN:
1. PASO 1-5: Diagnóstico completo
2. PASO 6-7: Aplicar correcciones según lo identificado
3. PASO 8: Verificar que todo esté correcto
4. PASO 9: Consultas de mantenimiento (opcional)

CONTACTO:
Si encuentras errores o necesitas ayuda, revisa el archivo:
INFORME_ANALISIS_PROBLEMA_BUS_DOBLE_PISO.md
*/
