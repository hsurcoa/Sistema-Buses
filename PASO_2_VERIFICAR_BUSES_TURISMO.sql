-- ================================================================
-- PASO 2: Verificar buses de dos pisos (59 asientos)
-- ================================================================
-- Esta consulta busca el tipo de bus "TURISMO 01" con 59 asientos
-- y muestra todos los buses registrados de ese tipo

SELECT 
    '=== TIPO DE BUS ===' as seccion,
    tb.id as tipo_bus_id,
    tb.nombre as tipo_bus,
    tb.capacidad,
    tb.pisos
FROM tipos_buses tb
WHERE tb.capacidad = 59 OR tb.nombre LIKE '%TURISMO%' OR tb.pisos = 2
UNION ALL
SELECT 
    '=== BUSES REGISTRADOS ===' as seccion,
    b.id,
    b.placa,
    b.numero_interno,
    CASE WHEN b.estado = 1 THEN 'ACTIVO' ELSE 'INACTIVO' END
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE tb.capacidad = 59 OR tb.nombre LIKE '%TURISMO%' OR tb.pisos = 2
ORDER BY seccion DESC;
