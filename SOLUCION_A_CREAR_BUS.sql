-- ================================================================
-- SOLUCIÓN A: Crear un bus de dos pisos (TURISMO 01)
-- ================================================================
-- IMPORTANTE: Reemplaza [TIPO_BUS_ID] con el ID real del tipo de bus
-- que obtuviste en el PASO 1

-- Ejemplo: Si el ID del tipo "TURISMO 01" es 2, reemplaza [TIPO_BUS_ID] con 2

INSERT INTO buses (
    placa, 
    numero_interno, 
    marca, 
    modelo, 
    tipo_bus_id, 
    estado, 
    fecha_registro
) VALUES (
    'TUR-001',              -- Placa del bus (puedes cambiarla)
    '01',                   -- Número interno
    'Mercedes Benz',        -- Marca
    'O500',                 -- Modelo
    [TIPO_BUS_ID],          -- ← REEMPLAZA ESTO con el ID del tipo de bus
    1,                      -- Estado: 1 = activo
    NOW()                   -- Fecha actual
);

-- Verificar que se creó correctamente
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    b.marca,
    tb.nombre as tipo_bus,
    tb.capacidad,
    tb.pisos,
    CASE WHEN b.estado = 1 THEN '✅ ACTIVO' ELSE '❌ INACTIVO' END as estado
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE b.placa = 'TUR-001';
