-- ================================================================
-- PASO 1: Verificar todos los tipos de buses
-- ================================================================
SELECT 
    id,
    nombre,
    capacidad,
    pisos,
    estado,
    CONCAT('ID: ', id, ' | ', nombre, ' | ', capacidad, ' asientos | ', pisos, ' piso(s)') as info_completa
FROM tipos_buses
ORDER BY pisos DESC, capacidad DESC;
