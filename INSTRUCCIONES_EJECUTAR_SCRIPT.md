# 🚀 INSTRUCCIONES PARA EJECUTAR LA SOLUCIÓN AUTOMÁTICA

**Fecha:** 25 de diciembre de 2025 - 15:52 hrs  
**Objetivo:** Ejecutar script automatizado que soluciona el problema

---

## ⚡ OPCIÓN RÁPIDA (RECOMENDADA)

### **PASO 1: Abrir phpMyAdmin**
1. Ve a: `http://localhost/phpmyadmin`
2. Selecciona la base de datos: **`venta_pasajes`**
3. Haz clic en la pestaña: **"SQL"**

---

### **PASO 2: Ejecutar Script Automatizado**

**Opción A: Script Completo (con informe detallado)**
1. Abre el archivo: **`SCRIPT_AUTOMATIZADO_SOLUCION.sql`**
2. Copia **TODO** el contenido (Ctrl + A, Ctrl + C)
3. Pégalo en phpMyAdmin (Ctrl + V)
4. Haz clic en **"Continuar"** o **"Go"**

**Opción B: Script Simple (más rápido)**
1. Abre el archivo: **`SCRIPT_SIMPLE_SOLUCION.sql`**
2. Copia **TODO** el contenido
3. Pégalo en phpMyAdmin
4. Haz clic en **"Continuar"**

---

### **PASO 3: Revisar el Informe**

El script te mostrará varias tablas con información. Busca estas secciones:

#### **1. TIPOS DE BUSES REGISTRADOS**
```
┌────┬─────────────┬───────────┬───────┬────────┐
│ id │   nombre    │ capacidad │ pisos │ estado │
├────┼─────────────┼───────────┼───────┼────────┤
│ 2  │ TURISMO 01  │    59     │   2   │ ACTIVO │
└────┴─────────────┴───────────┴───────┴────────┘
```

#### **2. BUSES DISPONIBLES (Resultado Final)**
```
┌────┬─────────┬────────┬────────────────┬────────┬─────────────┬───────────┬───────┬──────────────────────┐
│ id │  placa  │ número │     marca      │ modelo │  tipo_bus   │ capacidad │ pisos │       estado         │
├────┼─────────┼────────┼────────────────┼────────┼─────────────┼───────────┼───────┼──────────────────────┤
│ 5  │ TUR-001 │   01   │ Mercedes Benz  │ O500   │ TURISMO 01  │    59     │   2   │ ACTIVO Y LISTO PARA  │
│    │         │        │                │        │             │           │       │ USAR                 │
└────┴─────────┴────────┴────────────────┴────────┴─────────────┴───────────┴───────┴──────────────────────┘
```

#### **3. RESUMEN**
```
┌───────────────────────────┬────────────────────┐
│ total_buses_disponibles   │ placas_disponibles │
├───────────────────────────┼────────────────────┤
│            1              │      TUR-001       │
└───────────────────────────┴────────────────────┘
```

---

### **PASO 4: Interpretar Resultados**

| Resultado | Significado | Acción |
|-----------|-------------|--------|
| **total_buses_disponibles = 1 o más** | ✅ Problema resuelto | Ir al PASO 5 |
| **total_buses_disponibles = 0** | ❌ Problema persiste | Ver SOLUCIÓN MANUAL |
| **Error SQL** | ⚠️ Problema de permisos | Ver SOLUCIÓN ALTERNATIVA |

---

### **PASO 5: Probar en el Sistema**

1. **Recarga la página** del sistema (Ctrl + F5)
2. Ve a: **Ventas → Crear Rutas**
3. Haz clic en: **"Nueva Ruta"**
4. Selecciona una ruta
5. Selecciona: **"TURISMO 01 (59 as.)"**
6. **Verifica que ahora aparecen buses** en el select "Unidad / Bus"

---

## 🎯 RESULTADO ESPERADO

Después de ejecutar el script, deberías ver:

### **En el Sistema:**
```
┌─────────────────────────────────────────────────┐
│ Unidad / Bus                            ▼       │
├─────────────────────────────────────────────────┤
│ Seleccione Bus...                               │
│ TUR-001 - Bus #01 (Mercedes Benz)       ✅      │
└─────────────────────────────────────────────────┘
```

### **En la Consola del Navegador (F12):**
```javascript
🔍 Tipo de bus seleccionado: {id: "2", nombre: "TURISMO 01 (59 as.)"}
📊 Respuesta del servidor: {success: true, buses: [...], count: 1}
✅ Se encontraron 1 bus(es) del tipo: TURISMO 01 (59 as.)
```

---

## ⚠️ SI EL PROBLEMA PERSISTE

### **SOLUCIÓN MANUAL:**

Si después de ejecutar el script aún no aparecen buses, ejecuta esto manualmente:

```sql
-- 1. Identificar el ID del tipo de bus
SELECT id, nombre FROM tipos_buses WHERE pisos = 2 OR capacidad = 59;

-- Anota el ID que aparece (ejemplo: 2)

-- 2. Crear un bus manualmente
INSERT INTO buses (placa, numero_interno, marca, modelo, tipo_bus_id, estado, fecha_registro)
VALUES ('TUR-001', '01', 'Mercedes Benz', 'O500', 2, 1, NOW());
-- ↑ Reemplaza el 2 con el ID que anotaste

-- 3. Verificar
SELECT * FROM buses WHERE placa = 'TUR-001';
```

---

## 📊 QUÉ HACE EL SCRIPT AUTOMÁTICO

El script realiza estas acciones en orden:

1. ✅ **Diagnóstico:** Identifica tipos de buses y buses existentes
2. ✅ **Activación:** Activa buses inactivos de dos pisos
3. ✅ **Creación:** Crea un bus de ejemplo si no existe ninguno
4. ✅ **Verificación:** Muestra el estado final
5. ✅ **Informe:** Genera resumen completo

---

## 🔍 LOGS Y DEBUGGING

### **Ver logs del servidor:**
```bash
# En XAMPP Windows
notepad C:\xampp\apache\logs\error.log
```

Busca mensajes como:
```
🔍 Buscando buses para tipo_bus_id: 2
📊 Buses encontrados: 1
```

### **Ver logs del navegador:**
1. Presiona **F12**
2. Ve a la pestaña **"Console"**
3. Selecciona el tipo de bus
4. Busca mensajes con emojis: 🔍 📊 ✅ ⚠️

---

## ✅ CHECKLIST FINAL

Marca cada paso cuando lo completes:

- [ ] Abrí phpMyAdmin
- [ ] Seleccioné la base de datos `venta_pasajes`
- [ ] Ejecuté el script SQL completo
- [ ] Vi el informe generado
- [ ] `total_buses_disponibles` es mayor a 0
- [ ] Recargué la página del sistema (Ctrl + F5)
- [ ] Fui a Ventas → Crear Rutas
- [ ] Seleccioné "TURISMO 01 (59 as.)"
- [ ] Aparecen buses disponibles en el select
- [ ] ✅ **PROBLEMA RESUELTO**

---

## 📞 SOPORTE

Si después de ejecutar el script el problema persiste:

1. **Copia el resultado** del script SQL
2. **Copia los logs** de la consola del navegador (F12)
3. **Comparte** ambos para ayudarte a resolverlo

---

## 🎉 ÉXITO

Si todos los pasos están marcados, ¡felicitaciones! El problema está resuelto.

**Ahora puedes:**
- ✅ Crear rutas con buses de dos pisos
- ✅ Ver la placa del bus automáticamente
- ✅ Ver el chofer asignado (si existe)
- ✅ Ver información completa del bus

---

**Última actualización:** 25/12/2025 15:52 hrs  
**Archivos relacionados:**
- `SCRIPT_AUTOMATIZADO_SOLUCION.sql` (Completo con informe)
- `SCRIPT_SIMPLE_SOLUCION.sql` (Versión simplificada)
