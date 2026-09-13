# 🚀 GUÍA PASO A PASO - SOLUCIÓN ASISTIDA

**Fecha:** 25 de diciembre de 2025 - 15:47 hrs  
**Objetivo:** Solucionar "No hay buses disponibles de este tipo"

---

## 📋 CHECKLIST DE PASOS

- [ ] **PASO 1:** Abrir phpMyAdmin
- [ ] **PASO 2:** Ejecutar consulta de verificación
- [ ] **PASO 3:** Identificar el problema
- [ ] **PASO 4:** Aplicar solución correspondiente
- [ ] **PASO 5:** Verificar que funcionó
- [ ] **PASO 6:** Probar en el sistema

---

## 🎯 PASO 1: ABRIR phpMyAdmin

1. Abre tu navegador
2. Ve a: `http://localhost/phpmyadmin`
3. En el panel izquierdo, haz clic en **`venta_pasajes`**
4. Haz clic en la pestaña **"SQL"** en la parte superior

✅ **Marca cuando hayas completado este paso**

---

## 🔍 PASO 2: EJECUTAR CONSULTA DE VERIFICACIÓN

### **Opción A: Desde archivo**
1. Abre el archivo: `PASO_1_VERIFICAR_TIPOS_BUSES.sql`
2. Copia TODO el contenido
3. Pégalo en el cuadro de texto de phpMyAdmin
4. Haz clic en **"Continuar"** o **"Go"**

### **Opción B: Copiar directamente**
```sql
SELECT 
    id,
    nombre,
    capacidad,
    pisos,
    estado,
    CONCAT('ID: ', id, ' | ', nombre, ' | ', capacidad, ' asientos | ', pisos, ' piso(s)') as info_completa
FROM tipos_buses
ORDER BY pisos DESC, capacidad DESC;
```

### **¿Qué esperar?**
Deberías ver una tabla como esta:

```
┌────┬─────────────┬───────────┬───────┬────────┬──────────────────────────────────────┐
│ id │   nombre    │ capacidad │ pisos │ estado │           info_completa              │
├────┼─────────────┼───────────┼───────┼────────┼──────────────────────────────────────┤
│ 2  │ TURISMO 01  │    59     │   2   │   1    │ ID: 2 | TURISMO 01 | 59 as. | 2 p. │
│ 1  │ EJECUTIVO   │    40     │   1   │   1    │ ID: 1 | EJECUTIVO | 40 as. | 1 p.  │
└────┴─────────────┴───────────┴───────┴────────┴──────────────────────────────────────┘
```

### **📝 ANOTA AQUÍ:**
- **ID del tipo "TURISMO 01":** _______
- **Capacidad:** _______
- **Pisos:** _______
- **Estado:** _______

✅ **Marca cuando hayas completado este paso**

---

## 🔍 PASO 3: IDENTIFICAR EL PROBLEMA

Ahora ejecuta esta segunda consulta:

```sql
-- Buscar buses del tipo TURISMO 01
-- Reemplaza X con el ID que anotaste arriba
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    b.marca,
    b.tipo_bus_id,
    CASE WHEN b.estado = 1 THEN '✅ ACTIVO' ELSE '❌ INACTIVO' END as estado
FROM buses b
WHERE b.tipo_bus_id = X;  -- ← REEMPLAZA X con el ID que anotaste
```

### **Resultado 1: No aparece ninguna fila**
```
┌──────────────────────────────────────┐
│ No se encontraron registros          │
└──────────────────────────────────────┘
```
**→ Ve a SOLUCIÓN A (Crear bus nuevo)**

---

### **Resultado 2: Aparecen buses pero están INACTIVOS**
```
┌────┬─────────┬────────────────┬────────┬─────────────┬──────────────┐
│ id │  placa  │ numero_interno │ marca  │ tipo_bus_id │    estado    │
├────┼─────────┼────────────────┼────────┼─────────────┼──────────────┤
│ 5  │ TUR-001 │      01        │ MB     │      2      │ ❌ INACTIVO  │
└────┴─────────┴────────────────┴────────┴─────────────┴──────────────┘
```
**→ Ve a SOLUCIÓN B (Activar buses)**

---

### **Resultado 3: Aparecen buses ACTIVOS**
```
┌────┬─────────┬────────────────┬────────┬─────────────┬────────────┐
│ id │  placa  │ numero_interno │ marca  │ tipo_bus_id │   estado   │
├────┼─────────┼────────────────┼────────┼─────────────┼────────────┤
│ 5  │ TUR-001 │      01        │ MB     │      2      │ ✅ ACTIVO  │
└────┴─────────┴────────────────┴────────┴─────────────┴────────────┘
```
**→ Ve a SOLUCIÓN C (Problema de código)**

✅ **Marca cuando hayas identificado el problema**

---

## ✅ SOLUCIÓN A: CREAR BUS NUEVO

Si NO aparecieron buses en el PASO 3, necesitas crear uno:

### **1. Abre el archivo:** `SOLUCION_A_CREAR_BUS.sql`

### **2. Reemplaza `[TIPO_BUS_ID]` con el ID que anotaste**

Ejemplo: Si el ID del tipo "TURISMO 01" es **2**, cambia:
```sql
tipo_bus_id, 
[TIPO_BUS_ID],  -- ← ANTES
```
Por:
```sql
tipo_bus_id, 
2,              -- ← DESPUÉS
```

### **3. Ejecuta la consulta en phpMyAdmin**

### **4. Deberías ver:**
```
✅ 1 fila insertada
```

### **5. Verifica que se creó:**
La misma consulta del archivo incluye una verificación al final.

✅ **Marca cuando hayas completado este paso**

---

## ✅ SOLUCIÓN B: ACTIVAR BUSES EXISTENTES

Si aparecieron buses pero están **INACTIVOS**:

### **1. Abre el archivo:** `SOLUCION_B_ACTIVAR_BUSES.sql`

### **2. Reemplaza `[TIPO_BUS_ID]` con el ID que anotaste**

### **3. Ejecuta la consulta en phpMyAdmin**

### **4. Deberías ver:**
```
✅ X filas afectadas
```

✅ **Marca cuando hayas completado este paso**

---

## ✅ SOLUCIÓN C: PROBLEMA DE CÓDIGO

Si aparecieron buses **ACTIVOS** pero el sistema no los muestra:

### **1. Limpia la caché del navegador:**
- Presiona: `Ctrl + Shift + Delete`
- Selecciona: "Imágenes y archivos en caché"
- Haz clic en: "Borrar datos"

### **2. Recarga la página:**
- Presiona: `Ctrl + F5`

### **3. Verifica en la consola del navegador:**
- Presiona: `F12`
- Ve a la pestaña: "Console"
- Selecciona el tipo de bus
- Busca mensajes con: 🔍 📊

✅ **Marca cuando hayas completado este paso**

---

## 🧪 PASO 5: VERIFICAR QUE FUNCIONÓ

Ejecuta esta consulta final:

```sql
-- Reemplaza X con el ID del tipo de bus
SELECT 
    'VERIFICACIÓN FINAL' as resultado,
    COUNT(*) as buses_disponibles,
    GROUP_CONCAT(placa) as placas
FROM buses b
WHERE b.tipo_bus_id = X  -- ← REEMPLAZA X
  AND b.estado = 1;
```

### **Resultado esperado:**
```
┌────────────────────┬────────────────────┬─────────┐
│     resultado      │ buses_disponibles  │ placas  │
├────────────────────┼────────────────────┼─────────┤
│ VERIFICACIÓN FINAL │         1          │ TUR-001 │
└────────────────────┴────────────────────┴─────────┘
```

✅ **Marca cuando hayas completado este paso**

---

## 🎮 PASO 6: PROBAR EN EL SISTEMA

1. Ve a: `http://localhost/venta-pasajes/ventas/crear_ruta`
2. Haz clic en: **"Nueva Ruta"**
3. Selecciona una ruta
4. Selecciona: **"TURISMO 01 (59 as.)"**
5. **Verifica que ahora aparecen buses disponibles** en el select "Unidad / Bus"

### **Resultado esperado:**
```
┌─────────────────────────────────────────────────┐
│ Unidad / Bus                                    │
├─────────────────────────────────────────────────┤
│ Seleccione Bus...                               │
│ TUR-001 - Bus #01 (Mercedes Benz)              │
└─────────────────────────────────────────────────┘
```

✅ **Marca cuando hayas completado este paso**

---

## 🎉 ¡LISTO!

Si llegaste hasta aquí y todos los pasos están marcados, el problema está resuelto.

### **Resumen de lo que hicimos:**
- ✅ Identificamos el tipo de bus "TURISMO 01"
- ✅ Verificamos si había buses registrados
- ✅ Aplicamos la solución correspondiente
- ✅ Verificamos que funcionó
- ✅ Probamos en el sistema

---

## 📞 SI ALGO SALIÓ MAL

Si en algún paso tuviste un error, anota:

1. **¿En qué paso fue?** _______
2. **¿Qué mensaje de error apareció?** _______
3. **¿Qué resultado obtuviste?** _______

Y comparte esta información para ayudarte a resolverlo.

---

**Última actualización:** 25/12/2025 15:47 hrs
