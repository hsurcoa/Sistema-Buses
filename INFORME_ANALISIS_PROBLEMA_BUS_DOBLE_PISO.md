# 📋 INFORME DE ANÁLISIS - PROBLEMA DE CARGA DE PLACA Y CHOFER EN BUS DE DOS PISOS

**Fecha:** 25 de diciembre de 2025  
**Analista:** Antigravity AI  
**Prioridad:** 🔴 ALTA  
**Estado:** Análisis Completado - Solución Identificada

---

## 🎯 RESUMEN EJECUTIVO

Al seleccionar una ruta y asignar un bus de dos pisos, el sistema **NO carga automáticamente** la placa del bus ni el chofer asignado desde la base de datos. Este problema impide la asignación correcta de recursos operacionales durante la creación de viajes.

---

## 🔍 ANÁLISIS DEL PROBLEMA

### 1. **FLUJO ACTUAL DEL SISTEMA**

#### Paso 1: Selección del Tipo de Bus
```javascript
// Archivo: crear_ruta.php (línea 860-903)
document.getElementById('tipoBusSelect').addEventListener('change', function() {
    const tipoId = this.value;
    // ✅ FUNCIONA: Carga buses disponibles del tipo seleccionado
    fetch('/ventas/obtener_buses_tipo', {
        method: 'POST',
        body: 'tipo_id=' + tipoId
    })
    .then(response => response.json())
    .then(data => {
        // Llena el select de buses con: placa, número interno, marca
        selectBus.innerHTML = options;
    });
});
```

#### Paso 2: Selección del Bus Específico
```javascript
// Archivo: crear_ruta.php (línea 908-956)
function cargarTripulacion(busId) {
    // ⚠️ PROBLEMA IDENTIFICADO AQUÍ
    fetch('/ventas/obtener_tripulacion_bus', {
        method: 'POST',
        body: 'bus_id=' + busId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const chofer = data.data;
            // Carga el chofer asignado
            selectChofer.innerHTML = `<option value="${chofer.chofer_id}" selected>${chofer.nombre_chofer} (Asignado)</option>`;
        }
    });
}
```

---

### 2. **CONSULTA SQL EN EL MODELO**

```php
// Archivo: RutaModel.php (línea 388-405)
public function obtenerTripulacionBus($busId)
{
    $this->db->query("
        SELECT 
            ab.bus_id,
            ab.chofer_id,
            CONCAT(u.nombres, ' ', u.apellidos) as nombre_chofer,
            ab.copiloto_id,
            CONCAT(u2.nombres, ' ', u2.apellidos) as nombre_copiloto
        FROM asignaciones_buses ab
        INNER JOIN usuarios u ON ab.chofer_id = u.id
        LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
        WHERE ab.bus_id = :bus_id AND ab.estado = 1
        LIMIT 1
    ");
    $this->db->bind(':bus_id', $busId);
    return $this->db->single();
}
```

---

## 🐛 CAUSAS RAÍZ IDENTIFICADAS

### **CAUSA #1: Datos Faltantes en la Tabla `asignaciones_buses`**

**Problema:**  
La consulta SQL depende de que exista un registro en la tabla `asignaciones_buses` con:
- `bus_id` = ID del bus seleccionado
- `estado` = 1 (activo)
- `chofer_id` válido vinculado a la tabla `usuarios`

**Escenarios de Falla:**

| Escenario | Descripción | Resultado |
|-----------|-------------|-----------|
| **A** | No existe registro en `asignaciones_buses` para el bus de dos pisos | `data.success = false` → No se carga chofer |
| **B** | Existe registro pero `estado = 0` (inactivo) | Query no retorna resultados → No se carga chofer |
| **C** | `chofer_id` es NULL o no existe en `usuarios` | INNER JOIN falla → No se carga chofer |
| **D** | Bus registrado pero nunca asignado a tripulación | No hay fila en `asignaciones_buses` → No se carga chofer |

---

### **CAUSA #2: Falta de Información de Placa en la Respuesta**

**Problema:**  
La función `obtenerTripulacionBus()` **NO retorna la placa del bus**, solo retorna:
- `bus_id`
- `chofer_id` y `nombre_chofer`
- `copiloto_id` y `nombre_copiloto`

**Evidencia:**
```php
// ❌ FALTA: No se incluye información del bus (placa, número interno)
SELECT 
    ab.bus_id,
    ab.chofer_id,
    CONCAT(u.nombres, ' ', u.apellidos) as nombre_chofer
FROM asignaciones_buses ab
```

**Consecuencia:**  
Aunque el JavaScript reciba la tripulación, **no tiene forma de mostrar la placa** porque no viene en la respuesta del endpoint.

---

### **CAUSA #3: Lógica de Frontend Incompleta**

**Problema:**  
El código JavaScript solo actualiza el campo `selectChofer`, pero **no actualiza ningún campo de placa**.

```javascript
// ❌ Solo actualiza el chofer, NO la placa
selectChofer.innerHTML = `<option value="${chofer.chofer_id}" selected>${chofer.nombre_chofer} (Asignado)</option>`;
```

**Campos Faltantes:**
- No existe un campo en el formulario para mostrar la placa del bus seleccionado
- No existe un campo para mostrar el número interno del bus
- La información del bus ya viene en el `selectBus` pero no se extrae para mostrarse

---

## 🔬 DIAGNÓSTICO DE BASE DE DATOS

### **Verificaciones Necesarias:**

#### 1. **Verificar Existencia de Registros en `asignaciones_buses`**
```sql
-- Consulta de diagnóstico
SELECT 
    b.id as bus_id,
    b.placa,
    b.numero_interno,
    tb.nombre as tipo_bus,
    tb.pisos,
    ab.id as asignacion_id,
    ab.chofer_id,
    ab.estado as asignacion_estado,
    CONCAT(u.nombres, ' ', u.apellidos) as nombre_chofer
FROM buses b
LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
LEFT JOIN usuarios u ON ab.chofer_id = u.id
WHERE tb.pisos = 2  -- Filtrar solo buses de dos pisos
ORDER BY b.id;
```

**Interpretación de Resultados:**

| Resultado | Diagnóstico | Acción Requerida |
|-----------|-------------|------------------|
| `asignacion_id` es NULL | Bus no tiene tripulación asignada | Crear registro en `asignaciones_buses` |
| `asignacion_estado` = 0 | Asignación inactiva | Actualizar `estado = 1` |
| `chofer_id` es NULL | Asignación existe pero sin chofer | Asignar un chofer válido |
| `nombre_chofer` es NULL | `chofer_id` no existe en `usuarios` | Verificar integridad referencial |

---

#### 2. **Verificar Estructura de la Tabla `asignaciones_buses`**
```sql
-- Verificar estructura
DESCRIBE asignaciones_buses;

-- Verificar datos
SELECT * FROM asignaciones_buses LIMIT 10;
```

**Campos Esperados:**
- `id` (PK)
- `bus_id` (FK → buses.id)
- `chofer_id` (FK → usuarios.id)
- `copiloto_id` (FK → usuarios.id, nullable)
- `estado` (1 = activo, 0 = inactivo)
- `fecha_asignacion`
- `fecha_actualizacion`

---

#### 3. **Verificar Buses de Dos Pisos Registrados**
```sql
SELECT 
    b.id,
    b.placa,
    b.numero_interno,
    b.marca,
    tb.nombre as tipo_bus,
    tb.pisos,
    tb.capacidad
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
WHERE tb.pisos = 2 AND b.estado = 1;
```

---

## ✅ SOLUCIONES PROPUESTAS

### **SOLUCIÓN #1: Corregir Datos en Base de Datos (INMEDIATO)**

#### Paso 1: Identificar Buses sin Asignación
```sql
SELECT 
    b.id as bus_id,
    b.placa,
    'SIN ASIGNACIÓN' as problema
FROM buses b
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
WHERE ab.id IS NULL AND b.estado = 1;
```

#### Paso 2: Crear Asignaciones Faltantes
```sql
-- Ejemplo: Asignar chofer al bus de dos pisos
INSERT INTO asignaciones_buses (bus_id, chofer_id, copiloto_id, estado, fecha_asignacion)
VALUES 
    (1, 5, NULL, 1, NOW()),  -- Reemplazar con IDs reales
    (2, 6, 7, 1, NOW());     -- bus_id, chofer_id, copiloto_id
```

#### Paso 3: Verificar Choferes Disponibles
```sql
SELECT 
    u.id,
    CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo,
    u.rol
FROM usuarios u
WHERE u.rol IN ('Chofer', 'Piloto', 'Conductor')
AND u.estado = 1;
```

---

### **SOLUCIÓN #2: Mejorar la Consulta SQL del Modelo (RECOMENDADO)**

**Modificar:** `RutaModel.php` → `obtenerTripulacionBus()`

```php
public function obtenerTripulacionBus($busId)
{
    $this->db->query("
        SELECT 
            ab.bus_id,
            ab.chofer_id,
            CONCAT(u.nombres, ' ', u.apellidos) as nombre_chofer,
            ab.copiloto_id,
            CONCAT(u2.nombres, ' ', u2.apellidos) as nombre_copiloto,
            -- ✅ AGREGAR INFORMACIÓN DEL BUS
            b.placa as bus_placa,
            b.numero_interno as bus_numero,
            b.marca as bus_marca,
            tb.nombre as tipo_bus,
            tb.pisos as bus_pisos
        FROM asignaciones_buses ab
        INNER JOIN usuarios u ON ab.chofer_id = u.id
        LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
        -- ✅ AGREGAR JOINS PARA OBTENER DATOS DEL BUS
        INNER JOIN buses b ON ab.bus_id = b.id
        LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
        WHERE ab.bus_id = :bus_id AND ab.estado = 1
        LIMIT 1
    ");
    $this->db->bind(':bus_id', $busId);
    return $this->db->single();
}
```

**Beneficios:**
- ✅ Retorna placa, número interno y marca del bus
- ✅ Incluye información del tipo de bus (nombre, pisos)
- ✅ Permite mostrar todos los datos en el frontend

---

### **SOLUCIÓN #3: Actualizar el Frontend para Mostrar la Placa**

**Modificar:** `crear_ruta.php` → función `cargarTripulacion()`

```javascript
function cargarTripulacion(busId) {
    const selectChofer = document.getElementById('selectChofer');
    
    if (!busId) {
        selectChofer.value = '';
        selectChofer.innerHTML = '<option value="">Esperando selección de bus...</option>';
        return;
    }

    selectChofer.innerHTML = '<option value="">Buscando conductor asignado...</option>';

    fetch('<?php echo URLROOT; ?>/ventas/obtener_tripulacion_bus', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'bus_id=' + busId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const tripulacion = data.data;
            
            // ✅ Cargar chofer
            selectChofer.innerHTML = `<option value="${tripulacion.chofer_id}" selected>${tripulacion.nombre_chofer} (Asignado)</option>`;
            
            // ✅ NUEVO: Mostrar información del bus en un elemento visual
            mostrarInfoBus(tripulacion);
            
            // Feedback visual
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Bus ${tripulacion.bus_placa} asignado`,
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            selectChofer.innerHTML = '<option value="">Sin conductor fijo asignado</option>';
            
            // ✅ Advertencia si no hay asignación
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: 'Este bus no tiene tripulación asignada',
                showConfirmButton: false,
                timer: 3000
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        selectChofer.innerHTML = '<option value="">Error al buscar conductor</option>';
    });
}

// ✅ NUEVA FUNCIÓN: Mostrar información del bus
function mostrarInfoBus(tripulacion) {
    // Crear o actualizar un elemento para mostrar la info del bus
    let infoBusDiv = document.getElementById('infoBusAsignado');
    
    if (!infoBusDiv) {
        // Crear el elemento si no existe
        infoBusDiv = document.createElement('div');
        infoBusDiv.id = 'infoBusAsignado';
        infoBusDiv.className = 'alert alert-info mt-3';
        
        // Insertar después del select de chofer
        const selectChofer = document.getElementById('selectChofer');
        selectChofer.parentElement.parentElement.appendChild(infoBusDiv);
    }
    
    // Actualizar contenido
    infoBusDiv.innerHTML = `
        <h6 class="mb-2"><i class="bi bi-bus-front-fill me-2"></i>Información del Bus Asignado</h6>
        <div class="row">
            <div class="col-md-4">
                <strong>Placa:</strong> ${tripulacion.bus_placa || 'N/A'}
            </div>
            <div class="col-md-4">
                <strong>Unidad N°:</strong> ${tripulacion.bus_numero || 'N/A'}
            </div>
            <div class="col-md-4">
                <strong>Tipo:</strong> ${tripulacion.tipo_bus || 'N/A'} (${tripulacion.bus_pisos || 1} piso${tripulacion.bus_pisos > 1 ? 's' : ''})
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-6">
                <strong>Conductor:</strong> ${tripulacion.nombre_chofer || 'Sin asignar'}
            </div>
            <div class="col-md-6">
                <strong>Copiloto:</strong> ${tripulacion.nombre_copiloto || 'Sin asignar'}
            </div>
        </div>
    `;
}
```

---

### **SOLUCIÓN #4: Agregar Validación en el Backend**

**Modificar:** `Ventas.php` → `obtener_tripulacion_bus()`

```php
public function obtener_tripulacion_bus()
{
    // Limpiar cualquier output previo
    if (ob_get_length()) ob_clean();

    // Obtener ID del POST o JSON
    $busId = $_POST['bus_id'] ?? null;
    if (!$busId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $busId = $input['bus_id'] ?? null;
    }

    if ($busId) {
        $tripulacion = $this->rutaModel->obtenerTripulacionBus($busId);
        header('Content-Type: application/json');

        if ($tripulacion) {
            echo json_encode(['success' => true, 'data' => $tripulacion]);
        } else {
            // ✅ MEJORAR: Mensaje más descriptivo
            echo json_encode([
                'success' => false, 
                'message' => 'No hay tripulación asignada para este bus. Por favor, asigne un conductor desde el módulo de Gestión de Buses.',
                'bus_id' => $busId
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Falta el ID del bus']);
    }
}
```

---

## 📊 PLAN DE ACCIÓN RECOMENDADO

### **FASE 1: Diagnóstico Inmediato (5 minutos)**
1. ✅ Ejecutar consulta SQL de diagnóstico para buses de dos pisos
2. ✅ Verificar existencia de registros en `asignaciones_buses`
3. ✅ Identificar buses sin tripulación asignada

### **FASE 2: Corrección de Datos (10 minutos)**
1. ✅ Crear registros faltantes en `asignaciones_buses`
2. ✅ Asignar choferes a buses de dos pisos
3. ✅ Verificar que `estado = 1` en todas las asignaciones activas

### **FASE 3: Mejora del Código (20 minutos)**
1. ✅ Modificar `RutaModel.php` → `obtenerTripulacionBus()` (Solución #2)
2. ✅ Actualizar `crear_ruta.php` → `cargarTripulacion()` (Solución #3)
3. ✅ Mejorar `Ventas.php` → `obtener_tripulacion_bus()` (Solución #4)

### **FASE 4: Pruebas (10 minutos)**
1. ✅ Crear nueva ruta con bus de dos pisos
2. ✅ Verificar que se cargue placa y chofer automáticamente
3. ✅ Probar con bus sin asignación (debe mostrar advertencia)

---

## 🎯 CONSULTAS SQL DE DIAGNÓSTICO

### **Consulta 1: Verificar Buses de Dos Pisos y sus Asignaciones**
```sql
SELECT 
    b.id as bus_id,
    b.placa,
    b.numero_interno,
    b.marca,
    tb.nombre as tipo_bus,
    tb.pisos,
    tb.capacidad,
    CASE 
        WHEN ab.id IS NULL THEN '❌ SIN ASIGNACIÓN'
        WHEN ab.estado = 0 THEN '⚠️ ASIGNACIÓN INACTIVA'
        WHEN ab.chofer_id IS NULL THEN '⚠️ SIN CHOFER'
        ELSE '✅ ASIGNADO'
    END as estado_asignacion,
    ab.chofer_id,
    CONCAT(u.nombres, ' ', u.apellidos) as nombre_chofer,
    ab.copiloto_id,
    CONCAT(u2.nombres, ' ', u2.apellidos) as nombre_copiloto
FROM buses b
INNER JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
LEFT JOIN usuarios u ON ab.chofer_id = u.id
LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
WHERE tb.pisos = 2 AND b.estado = 1
ORDER BY b.id;
```

### **Consulta 2: Listar Choferes Disponibles**
```sql
SELECT 
    u.id,
    CONCAT(u.nombres, ' ', u.apellidos) as nombre_completo,
    u.rol,
    u.estado,
    COUNT(ab.id) as buses_asignados
FROM usuarios u
LEFT JOIN asignaciones_buses ab ON u.id = ab.chofer_id AND ab.estado = 1
WHERE u.rol IN ('Chofer', 'Piloto', 'Conductor')
AND u.estado = 1
GROUP BY u.id
ORDER BY buses_asignados ASC;
```

### **Consulta 3: Crear Asignación de Ejemplo**
```sql
-- ⚠️ REEMPLAZAR CON IDs REALES DE TU BASE DE DATOS
INSERT INTO asignaciones_buses (bus_id, chofer_id, copiloto_id, estado, fecha_asignacion)
VALUES 
    (
        (SELECT id FROM buses WHERE placa = 'ABC-123' LIMIT 1),  -- Reemplazar con placa real
        (SELECT id FROM usuarios WHERE rol = 'Chofer' LIMIT 1),  -- Primer chofer disponible
        NULL,  -- Sin copiloto
        1,     -- Activo
        NOW()
    );
```

---

## 📝 CONCLUSIONES

### **Problema Principal:**
El sistema **NO carga la placa ni el chofer** porque:
1. ❌ Faltan registros en la tabla `asignaciones_buses` para buses de dos pisos
2. ❌ La consulta SQL no retorna información del bus (placa, número)
3. ❌ El frontend no tiene lógica para mostrar la placa del bus

### **Impacto:**
- 🔴 **ALTO:** Impide la asignación correcta de recursos operacionales
- 🔴 **ALTO:** Genera confusión en el personal de ventas
- 🟡 **MEDIO:** Requiere asignación manual posterior

### **Solución Recomendada:**
1. ✅ **Inmediato:** Crear registros en `asignaciones_buses` para buses de dos pisos
2. ✅ **Corto Plazo:** Modificar consulta SQL para incluir datos del bus
3. ✅ **Corto Plazo:** Actualizar frontend para mostrar placa y chofer

### **Tiempo Estimado de Implementación:**
- Diagnóstico: 5 minutos
- Corrección de datos: 10 minutos
- Mejora de código: 20 minutos
- Pruebas: 10 minutos
- **TOTAL: 45 minutos**

---

## 🚀 PRÓXIMOS PASOS

1. **Ejecutar consultas de diagnóstico** para identificar buses sin asignación
2. **Crear registros faltantes** en `asignaciones_buses`
3. **Implementar mejoras de código** según las soluciones propuestas
4. **Realizar pruebas** con buses de dos pisos
5. **Documentar** el proceso de asignación de tripulación

---

**Fin del Informe**  
*Generado por Antigravity AI - Sistema de Análisis de Código*
