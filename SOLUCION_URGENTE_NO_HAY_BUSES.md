# 🚨 SOLUCIÓN URGENTE: "No hay buses disponibles de este tipo"

**Fecha:** 25 de diciembre de 2025 - 15:43 hrs  
**Problema:** Al seleccionar "TURISMO 01 (59 as.)" no aparecen buses disponibles  
**Estado:** 🔴 CRÍTICO - Requiere acción inmediata

---

## 🎯 PROBLEMA IDENTIFICADO

Cuando seleccionas el tipo de bus **"TURISMO 01 (59 as.)"** (bus de dos pisos), el sistema muestra:

```
❌ "No hay buses disponibles de este tipo"
```

Esto significa que la consulta SQL **NO está encontrando buses** con ese `tipo_bus_id` en estado activo.

---

## 🔍 DIAGNÓSTICO PASO A PASO

### **PASO 1: Abrir la Consola del Navegador**

1. Presiona **F12** en tu navegador
2. Ve a la pestaña **"Console"**
3. Selecciona el tipo de bus "TURISMO 01 (59 as.)"
4. Observa los mensajes que aparecen:

```javascript
🔍 Tipo de bus seleccionado: {id: "X", nombre: "TURISMO 01 (59 as.)"}
📊 Respuesta del servidor: {success: true, buses: [], count: 0, ...}
⚠️ NO SE ENCONTRARON BUSES DISPONIBLES
```

**Anota el ID del tipo de bus** (el número que aparece en `id: "X"`). Lo necesitarás para el siguiente paso.

---

### **PASO 2: Ejecutar Consultas SQL de Diagnóstico**

Abre **phpMyAdmin** o tu cliente SQL favorito y ejecuta estas consultas:

#### **Consulta 1: Verificar el tipo de bus**
```sql
SELECT * FROM tipos_buses WHERE capacidad = 59;
```

**Resultado esperado:**
```
id | nombre      | capacidad | pisos | estado
---|-------------|-----------|-------|-------
X  | TURISMO 01  | 59        | 2     | 1
```

**Anota el `id`** que aparece. Este es el `tipo_bus_id` que debes usar.

---

#### **Consulta 2: Buscar buses de ese tipo**
```sql
-- Reemplaza X con el ID del tipo de bus que anotaste
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    b.marca,
    b.tipo_bus_id,
    b.estado,
    CASE 
        WHEN b.estado = 1 THEN '✅ ACTIVO'
        WHEN b.estado = 0 THEN '❌ INACTIVO'
        ELSE '⚠️ DESCONOCIDO'
    END as estado_texto
FROM buses b
WHERE b.tipo_bus_id = X;  -- Reemplaza X con el ID real
```

**Posibles resultados:**

| Resultado | Diagnóstico | Solución |
|-----------|-------------|----------|
| **No hay filas** | No hay buses registrados | → **SOLUCIÓN A** |
| **Hay filas con estado = 0** | Buses inactivos | → **SOLUCIÓN B** |
| **Hay filas con estado = 1** | Problema en el código | → **SOLUCIÓN C** |

---

## ✅ SOLUCIONES

### **SOLUCIÓN A: No hay buses registrados de ese tipo**

Si la consulta no retorna ninguna fila, necesitas **crear un bus**:

```sql
-- Reemplaza X con el ID del tipo de bus (ej: 2, 3, 4...)
INSERT INTO buses (placa, numero_interno, marca, modelo, tipo_bus_id, estado, fecha_registro)
VALUES (
    'ABC-123',           -- Placa del bus
    '01',                -- Número interno
    'Mercedes Benz',     -- Marca
    'O500',              -- Modelo
    X,                   -- ← REEMPLAZA X con el ID del tipo de bus
    1,                   -- Estado: 1 = activo
    NOW()                -- Fecha actual
);
```

**Verificar que se creó:**
```sql
SELECT * FROM buses WHERE placa = 'ABC-123';
```

---

### **SOLUCIÓN B: Los buses están inactivos (estado = 0)**

Si la consulta muestra buses con `estado = 0`, necesitas **activarlos**:

```sql
-- Activar TODOS los buses de dos pisos
UPDATE buses 
SET estado = 1 
WHERE tipo_bus_id = X;  -- Reemplaza X con el ID del tipo de bus
```

**O activar un bus específico por placa:**
```sql
UPDATE buses 
SET estado = 1 
WHERE placa = 'ABC-123';  -- Reemplaza con la placa real
```

**Verificar que se activó:**
```sql
SELECT id, placa, estado FROM buses WHERE tipo_bus_id = X;
```

---

### **SOLUCIÓN C: Los buses existen y están activos (problema de código)**

Si la consulta muestra buses con `estado = 1` pero el sistema no los encuentra, hay un problema de sincronización.

**Solución rápida:**
1. Limpia la caché del navegador (Ctrl + Shift + Delete)
2. Recarga la página (Ctrl + F5)
3. Intenta nuevamente

**Si el problema persiste:**
```sql
-- Verificar que el tipo_bus_id coincide
SELECT 
    tb.id as tipo_id,
    tb.nombre as tipo_nombre,
    COUNT(b.id) as cantidad_buses
FROM tipos_buses tb
LEFT JOIN buses b ON tb.id = b.tipo_bus_id AND b.estado = 1
WHERE tb.capacidad = 59
GROUP BY tb.id;
```

---

## 🧪 VERIFICACIÓN POST-CORRECCIÓN

Después de aplicar cualquiera de las soluciones, ejecuta esta consulta para verificar:

```sql
-- Verificar que ahora SÍ hay buses disponibles
SELECT 
    'VERIFICACIÓN FINAL' as resultado,
    COUNT(*) as buses_disponibles,
    GROUP_CONCAT(placa) as placas
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE tb.capacidad = 59 
  AND b.estado = 1;
```

**Resultado esperado:**
```
resultado            | buses_disponibles | placas
---------------------|-------------------|-------------
VERIFICACIÓN FINAL   | 1                 | ABC-123
```

---

## 🚀 PRUEBA EN EL SISTEMA

1. Ve a **"Ventas" → "Crear Rutas"**
2. Haz clic en **"Nueva Ruta"**
3. Selecciona una ruta
4. Selecciona **"TURISMO 01 (59 as.)"**
5. ✅ **Ahora deberías ver buses disponibles** en el select "Unidad / Bus"

---

## 📊 MEJORAS IMPLEMENTADAS

He mejorado el código para que ahora:

### **Backend (Ventas.php):**
- ✅ Registra en logs cada petición
- ✅ Muestra cuántos buses se encontraron
- ✅ Sugiere verificar la base de datos si no hay buses
- ✅ Retorna información de debugging

### **Frontend (crear_ruta.php):**
- ✅ Muestra logs detallados en la consola del navegador
- ✅ Alerta visual cuando no hay buses disponibles
- ✅ Explica las posibles causas del problema
- ✅ Sugiere revisar la consola para más detalles

---

## 📝 LOGS AUTOMÁTICOS

Ahora el sistema registra automáticamente en los logs:

```
🔍 Buscando buses para tipo_bus_id: 2
📊 Buses encontrados: 0
⚠️ WARNING: No se encontraron buses activos para tipo_bus_id: 2
💡 SUGERENCIA: Verificar que existan buses con tipo_bus_id=2 y estado=1 en la tabla 'buses'
```

**Ubicación de los logs:**
- **Windows XAMPP:** `C:\xampp\apache\logs\error.log`
- **Linux:** `/var/log/apache2/error.log`
- **Consola del navegador:** Presiona F12 → pestaña "Console"

---

## 🎯 RESUMEN EJECUTIVO

| Paso | Acción | Tiempo |
|------|--------|--------|
| 1 | Abrir consola del navegador (F12) | 10 seg |
| 2 | Seleccionar tipo de bus y anotar ID | 20 seg |
| 3 | Ejecutar consulta SQL de diagnóstico | 30 seg |
| 4 | Aplicar solución correspondiente (A, B o C) | 1-2 min |
| 5 | Verificar que funcionó | 30 seg |
| **TOTAL** | | **3-4 minutos** |

---

## 🆘 SI AÚN NO FUNCIONA

Si después de aplicar las soluciones el problema persiste:

1. **Revisa los logs del servidor:**
   ```bash
   # En XAMPP Windows
   notepad C:\xampp\apache\logs\error.log
   ```

2. **Revisa la consola del navegador:**
   - Presiona F12
   - Ve a la pestaña "Console"
   - Busca mensajes con 🔍 📊 ⚠️ ❌

3. **Ejecuta el archivo de diagnóstico completo:**
   - Abre: `DIAGNOSTICO_URGENTE_BUSES_NO_DISPONIBLES.sql`
   - Ejecuta todas las consultas
   - Anota los resultados

4. **Comparte los resultados:**
   - Logs del servidor
   - Logs de la consola
   - Resultados de las consultas SQL

---

## 📞 INFORMACIÓN ADICIONAL

**Archivos relacionados:**
- `app/models/RutaModel.php` → Función `obtenerBusesPorTipo()`
- `app/controllers/Ventas.php` → Función `obtener_buses_tipo()`
- `app/views/ventas/crear_ruta.php` → Event listener de `tipoBusSelect`

**Consultas SQL útiles:**
- `DIAGNOSTICO_URGENTE_BUSES_NO_DISPONIBLES.sql`
- `DIAGNOSTICO_SQL_BUS_DOBLE_PISO.sql`

---

**Última actualización:** 25/12/2025 15:43 hrs  
**Versión:** 2.0 (con logging mejorado)
