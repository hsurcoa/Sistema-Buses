-- ================================================================
-- DIAGNÓSTICO URGENTE: "No hay buses disponibles de este tipo"
-- Fecha: 25 de diciembre de 2025 - 15:43
-- ================================================================

-- PASO 1: Verificar tipos de buses registrados
SELECT 
    id,
    nombre,
    capacidad,
    pisos,
    estado
FROM tipos_buses
ORDER BY id;

-- PASO 2: Verificar buses de dos pisos (TURISMO 01)
-- Buscar el tipo de bus que tiene 59 asientos
SELECT 
    tb.id as tipo_bus_id,
    tb.nombre as tipo_bus,
    tb.capacidad,
    tb.pisos,
    COUNT(b.id) as cantidad_buses,
    SUM(CASE WHEN b.estado = 1 THEN 1 ELSE 0 END) as buses_activos,
    SUM(CASE WHEN b.estado = 0 THEN 1 ELSE 0 END) as buses_inactivos
FROM tipos_buses tb
LEFT JOIN buses b ON tb.id = b.tipo_bus_id
WHERE tb.capacidad = 59 OR tb.pisos = 2
GROUP BY tb.id
ORDER BY tb.id;

-- PASO 3: Listar TODOS los buses con su tipo
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    b.marca,
    b.modelo,
    b.estado as bus_estado,
    b.tipo_bus_id,
    tb.nombre as tipo_bus,
    tb.capacidad,
    tb.pisos
FROM buses b
LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
ORDER BY tb.pisos DESC, b.id;

-- PASO 4: Buscar buses de dos pisos específicamente
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    b.marca,
    b.tipo_bus_id,
    b.estado,
    tb.nombre as tipo_bus,
    tb.capacidad,
    tb.pisos,
    CASE 
        WHEN b.estado = 1 THEN '✅ ACTIVO'
        WHEN b.estado = 0 THEN '❌ INACTIVO'
        ELSE '⚠️ DESCONOCIDO'
    END as estado_texto
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE tb.pisos = 2
ORDER BY b.estado DESC, b.id;

-- PASO 5: Verificar si hay buses con tipo_bus_id NULL
SELECT 
    COUNT(*) as buses_sin_tipo,
    GROUP_CONCAT(id) as ids_buses_sin_tipo
FROM buses
WHERE tipo_bus_id IS NULL;

-- PASO 6: Verificar buses con estado = 0 (inactivos)
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    b.estado,
    tb.nombre as tipo_bus,
    tb.pisos
FROM buses b
LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE b.estado = 0
ORDER BY tb.pisos DESC;

-- ================================================================
-- SOLUCIONES POSIBLES SEGÚN EL RESULTADO
-- ================================================================

-- SOLUCIÓN 1: Si los buses existen pero están INACTIVOS (estado = 0)
/*
UPDATE buses 
SET estado = 1 
WHERE tipo_bus_id = (SELECT id FROM tipos_buses WHERE capacidad = 59 LIMIT 1);
*/

-- SOLUCIÓN 2: Si los buses NO existen, crear uno de ejemplo
/*
INSERT INTO buses (placa, numero_interno, marca, modelo, tipo_bus_id, estado, fecha_registro)
VALUES (
    'ABC-123',
    '01',
    'Mercedes Benz',
    'O500',
    (SELECT id FROM tipos_buses WHERE capacidad = 59 AND pisos = 2 LIMIT 1),
    1,
    NOW()
);
*/

-- SOLUCIÓN 3: Si el tipo_bus_id es incorrecto, corregirlo
/*
UPDATE buses 
SET tipo_bus_id = (SELECT id FROM tipos_buses WHERE capacidad = 59 AND pisos = 2 LIMIT 1)
WHERE placa = 'TU_PLACA_AQUI';
*/

-- ================================================================
-- VERIFICACIÓN POST-CORRECCIÓN
-- ================================================================

-- Verificar que ahora SÍ hay buses disponibles
SELECT 
    'RESULTADO FINAL' as verificacion,
    COUNT(*) as buses_disponibles,
    GROUP_CONCAT(placa) as placas
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE tb.capacidad = 59 
  AND tb.pisos = 2 
  AND b.estado = 1;
