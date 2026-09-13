-- ================================================================
-- SCRIPT AUTOMATIZADO DE DIAGNÓSTICO Y SOLUCIÓN
-- Sistema de Venta de Pasajes - Problema: "No hay buses disponibles"
-- Fecha: 25 de diciembre de 2025
-- ================================================================

-- IMPORTANTE: Este script hace lo siguiente:
-- 1. Diagnostica el problema
-- 2. Crea buses de dos pisos si no existen
-- 3. Activa buses inactivos
-- 4. Genera un informe completo

-- ================================================================
-- PARTE 1: DIAGNÓSTICO INICIAL
-- ================================================================

SELECT '========================================' as '';
SELECT 'INFORME DE DIAGNÓSTICO Y SOLUCIÓN' as '';
SELECT 'Fecha y Hora:' as '', NOW() as 'Timestamp';
SELECT '========================================' as '';
SELECT '' as '';

-- 1.1 Verificar tipos de buses
SELECT '--- PASO 1: TIPOS DE BUSES REGISTRADOS ---' as '';
SELECT 
    id as 'ID',
    nombre as 'Nombre',
    capacidad as 'Capacidad',
    pisos as 'Pisos',
    CASE WHEN estado = 1 THEN '✅ Activo' ELSE '❌ Inactivo' END as 'Estado'
FROM tipos_buses
ORDER BY pisos DESC, capacidad DESC;

SELECT '' as '';

-- 1.2 Buscar tipo de bus de dos pisos (59 asientos)
SELECT '--- PASO 2: IDENTIFICAR TIPO "TURISMO 01" ---' as '';
SELECT 
    id as 'ID_Tipo_Bus',
    nombre as 'Nombre',
    capacidad as 'Capacidad',
    pisos as 'Pisos'
FROM tipos_buses
WHERE (capacidad = 59 OR pisos = 2 OR nombre LIKE '%TURISMO%')
LIMIT 1;

-- Guardar el ID en una variable (para MySQL 8.0+)
SET @tipo_bus_id = (
    SELECT id FROM tipos_buses 
    WHERE (capacidad = 59 OR pisos = 2 OR nombre LIKE '%TURISMO%')
    LIMIT 1
);

SELECT '' as '';
SELECT CONCAT('ID del tipo de bus identificado: ', COALESCE(@tipo_bus_id, 'NO ENCONTRADO')) as 'Resultado';
SELECT '' as '';

-- 1.3 Verificar buses existentes de ese tipo
SELECT '--- PASO 3: BUSES EXISTENTES DEL TIPO IDENTIFICADO ---' as '';
SELECT 
    b.id as 'ID',
    b.placa as 'Placa',
    b.numero_interno as 'Número',
    b.marca as 'Marca',
    CASE WHEN b.estado = 1 THEN '✅ ACTIVO' ELSE '❌ INACTIVO' END as 'Estado',
    tb.nombre as 'Tipo'
FROM buses b
LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE b.tipo_bus_id = @tipo_bus_id;

SELECT '' as '';

-- ================================================================
-- PARTE 2: APLICAR SOLUCIONES AUTOMÁTICAS
-- ================================================================

SELECT '========================================' as '';
SELECT 'APLICANDO SOLUCIONES AUTOMÁTICAS' as '';
SELECT '========================================' as '';
SELECT '' as '';

-- 2.1 SOLUCIÓN A: Crear bus si no existe ninguno
SELECT '--- SOLUCIÓN A: CREAR BUS SI NO EXISTE ---' as '';

-- Verificar si ya existe al menos un bus de ese tipo
SET @buses_existentes = (
    SELECT COUNT(*) FROM buses WHERE tipo_bus_id = @tipo_bus_id
);

SELECT CONCAT('Buses existentes: ', COALESCE(@buses_existentes, 0)) as 'Estado Actual';

-- Si no existe ningún bus, crear uno
INSERT INTO buses (placa, numero_interno, marca, modelo, tipo_bus_id, estado, fecha_registro)
SELECT 
    'TUR-001' as placa,
    '01' as numero_interno,
    'Mercedes Benz' as marca,
    'O500 RS' as modelo,
    @tipo_bus_id as tipo_bus_id,
    1 as estado,
    NOW() as fecha_registro
FROM DUAL
WHERE @buses_existentes = 0 AND @tipo_bus_id IS NOT NULL;

SELECT 
    CASE 
        WHEN @buses_existentes = 0 AND @tipo_bus_id IS NOT NULL 
        THEN '✅ Bus creado: TUR-001'
        WHEN @buses_existentes > 0 
        THEN '⚠️ Ya existen buses, no se creó uno nuevo'
        ELSE '❌ No se pudo crear (tipo de bus no encontrado)'
    END as 'Resultado Solución A';

SELECT '' as '';

-- 2.2 SOLUCIÓN B: Activar buses inactivos
SELECT '--- SOLUCIÓN B: ACTIVAR BUSES INACTIVOS ---' as '';

-- Contar buses inactivos
SET @buses_inactivos = (
    SELECT COUNT(*) FROM buses 
    WHERE tipo_bus_id = @tipo_bus_id AND estado = 0
);

SELECT CONCAT('Buses inactivos encontrados: ', COALESCE(@buses_inactivos, 0)) as 'Estado Actual';

-- Activar buses inactivos
UPDATE buses 
SET estado = 1 
WHERE tipo_bus_id = @tipo_bus_id AND estado = 0;

SELECT 
    CASE 
        WHEN @buses_inactivos > 0 
        THEN CONCAT('✅ Se activaron ', @buses_inactivos, ' bus(es)')
        ELSE '⚠️ No había buses inactivos para activar'
    END as 'Resultado Solución B';

SELECT '' as '';

-- ================================================================
-- PARTE 3: VERIFICACIÓN POST-SOLUCIÓN
-- ================================================================

SELECT '========================================' as '';
SELECT 'VERIFICACIÓN POST-SOLUCIÓN' as '';
SELECT '========================================' as '';
SELECT '' as '';

-- 3.1 Estado final de buses
SELECT '--- ESTADO FINAL DE BUSES ---' as '';
SELECT 
    b.id as 'ID',
    b.placa as 'Placa',
    b.numero_interno as 'Número',
    b.marca as 'Marca',
    b.modelo as 'Modelo',
    tb.nombre as 'Tipo de Bus',
    tb.capacidad as 'Capacidad',
    tb.pisos as 'Pisos',
    CASE WHEN b.estado = 1 THEN '✅ ACTIVO' ELSE '❌ INACTIVO' END as 'Estado'
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE b.tipo_bus_id = @tipo_bus_id
ORDER BY b.estado DESC, b.id;

SELECT '' as '';

-- 3.2 Resumen final
SELECT '--- RESUMEN FINAL ---' as '';
SELECT 
    COUNT(*) as 'Total Buses Disponibles',
    GROUP_CONCAT(placa SEPARATOR ', ') as 'Placas',
    @tipo_bus_id as 'ID Tipo de Bus'
FROM buses
WHERE tipo_bus_id = @tipo_bus_id AND estado = 1;

SELECT '' as '';

-- 3.3 Verificar asignaciones de tripulación
SELECT '--- VERIFICAR ASIGNACIONES DE TRIPULACIÓN ---' as '';
SELECT 
    b.id as 'ID Bus',
    b.placa as 'Placa',
    CASE 
        WHEN ab.id IS NOT NULL THEN '✅ Tiene asignación'
        ELSE '⚠️ Sin asignación (se creará automáticamente)'
    END as 'Estado Asignación',
    COALESCE(CONCAT(u.nombres, ' ', u.apellidos), 'Sin chofer') as 'Chofer Asignado'
FROM buses b
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
LEFT JOIN usuarios u ON ab.chofer_id = u.id
WHERE b.tipo_bus_id = @tipo_bus_id AND b.estado = 1;

SELECT '' as '';

-- ================================================================
-- PARTE 4: INFORME FINAL Y RECOMENDACIONES
-- ================================================================

SELECT '========================================' as '';
SELECT 'INFORME FINAL' as '';
SELECT '========================================' as '';
SELECT '' as '';

-- 4.1 Resultado general
SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM buses WHERE tipo_bus_id = @tipo_bus_id AND estado = 1) > 0
        THEN '✅ PROBLEMA RESUELTO'
        ELSE '❌ PROBLEMA PERSISTE'
    END as 'Estado General';

SELECT '' as '';

-- 4.2 Próximos pasos
SELECT '--- PRÓXIMOS PASOS ---' as '';
SELECT '1. Recargar la página del sistema (Ctrl + F5)' as 'Paso 1';
SELECT '2. Ir a Ventas → Crear Rutas' as 'Paso 2';
SELECT '3. Seleccionar tipo de bus "TURISMO 01 (59 as.)"' as 'Paso 3';
SELECT '4. Verificar que aparecen buses disponibles' as 'Paso 4';

SELECT '' as '';

-- 4.3 Información de contacto
SELECT '--- INFORMACIÓN ADICIONAL ---' as '';
SELECT CONCAT('Tipo de bus ID: ', COALESCE(@tipo_bus_id, 'N/A')) as 'Info 1';
SELECT CONCAT('Buses activos: ', (SELECT COUNT(*) FROM buses WHERE tipo_bus_id = @tipo_bus_id AND estado = 1)) as 'Info 2';
SELECT 'Si el problema persiste, revisar logs del navegador (F12)' as 'Info 3';

SELECT '' as '';
SELECT '========================================' as '';
SELECT 'FIN DEL INFORME' as '';
SELECT CONCAT('Generado el: ', NOW()) as '';
SELECT '========================================' as '';
