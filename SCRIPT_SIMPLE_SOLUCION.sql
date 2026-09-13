-- ================================================================
-- SCRIPT SIMPLIFICADO DE SOLUCIÓN (Sin variables)
-- Compatible con todas las versiones de MySQL/MariaDB
-- ================================================================

-- PASO 1: Ver tipos de buses
SELECT '=== TIPOS DE BUSES REGISTRADOS ===' as 'INFORME';
SELECT 
    id,
    nombre,
    capacidad,
    pisos,
    CASE WHEN estado = 1 THEN 'ACTIVO' ELSE 'INACTIVO' END as estado
FROM tipos_buses
ORDER BY pisos DESC;

-- PASO 2: Ver buses de dos pisos existentes
SELECT '=== BUSES DE DOS PISOS EXISTENTES ===' as 'INFORME';
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    b.marca,
    b.tipo_bus_id,
    CASE WHEN b.estado = 1 THEN 'ACTIVO' ELSE 'INACTIVO' END as estado,
    tb.nombre as tipo_bus
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE tb.pisos = 2 OR tb.capacidad = 59;

-- ================================================================
-- SOLUCIONES AUTOMÁTICAS
-- ================================================================

-- SOLUCIÓN 1: Activar todos los buses de dos pisos que estén inactivos
UPDATE buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
SET b.estado = 1
WHERE (tb.pisos = 2 OR tb.capacidad = 59) AND b.estado = 0;

-- SOLUCIÓN 2: Crear un bus de ejemplo si no existe ninguno
-- NOTA: Esto solo se ejecutará si no hay buses de dos pisos
INSERT INTO buses (placa, numero_interno, marca, modelo, tipo_bus_id, estado, fecha_registro)
SELECT 
    'TUR-001',
    '01',
    'Mercedes Benz',
    'O500 RS',
    tb.id,
    1,
    NOW()
FROM tipos_buses tb
WHERE (tb.pisos = 2 OR tb.capacidad = 59)
AND NOT EXISTS (
    SELECT 1 FROM buses b2 
    WHERE b2.tipo_bus_id = tb.id
)
LIMIT 1;

-- ================================================================
-- VERIFICACIÓN FINAL
-- ================================================================

SELECT '=== VERIFICACIÓN FINAL: BUSES DISPONIBLES ===' as 'INFORME';
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    b.marca,
    b.modelo,
    tb.nombre as tipo_bus,
    tb.capacidad,
    tb.pisos,
    'ACTIVO Y LISTO PARA USAR' as estado
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE (tb.pisos = 2 OR tb.capacidad = 59) AND b.estado = 1;

-- Resumen
SELECT '=== RESUMEN ===' as 'INFORME';
SELECT 
    COUNT(*) as total_buses_disponibles,
    GROUP_CONCAT(placa) as placas_disponibles
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE (tb.pisos = 2 OR tb.capacidad = 59) AND b.estado = 1;
