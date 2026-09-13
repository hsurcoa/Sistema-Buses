-- ================================================================
-- SOLUCIÓN B: Activar buses existentes que están inactivos
-- ================================================================
-- IMPORTANTE: Reemplaza [TIPO_BUS_ID] con el ID real del tipo de bus

-- Opción 1: Activar TODOS los buses de ese tipo
UPDATE buses 
SET estado = 1 
WHERE tipo_bus_id = [TIPO_BUS_ID];  -- ← REEMPLAZA ESTO

-- Opción 2: Activar un bus específico por placa
-- UPDATE buses 
-- SET estado = 1 
-- WHERE placa = 'TUR-001';  -- ← Reemplaza con la placa real

-- Verificar que se activaron
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    tb.nombre as tipo_bus,
    CASE WHEN b.estado = 1 THEN '✅ ACTIVO' ELSE '❌ INACTIVO' END as estado
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE tb.tipo_bus_id = [TIPO_BUS_ID];  -- ← REEMPLAZA ESTO
