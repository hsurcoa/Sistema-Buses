-- ================================================================
-- SOLUCIÓN C: Corregir tipo_bus_id de buses mal configurados
-- ================================================================
-- IMPORTANTE: Reemplaza [TIPO_BUS_ID_CORRECTO] con el ID real

-- Ver buses que podrían estar mal configurados
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    b.tipo_bus_id as tipo_actual,
    tb.nombre as tipo_nombre,
    '¿Es este un bus de dos pisos?' as pregunta
FROM buses b
LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE b.estado = 1
ORDER BY b.id;

-- Si encuentras un bus que DEBERÍA ser TURISMO 01 pero tiene otro tipo_bus_id:
-- UPDATE buses 
-- SET tipo_bus_id = [TIPO_BUS_ID_CORRECTO]  -- ← ID del tipo TURISMO 01
-- WHERE placa = 'ABC-123';  -- ← Placa del bus a corregir
