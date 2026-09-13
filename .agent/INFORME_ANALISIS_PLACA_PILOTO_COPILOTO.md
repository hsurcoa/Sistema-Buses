# 📋 INFORME DE ANÁLISIS TÉCNICO
## Problema: No aparece Placa, Nombre de Piloto y Nombre de Copiloto

**Fecha:** 25 de Diciembre de 2025  
**Analista:** Antigravity AI  
**Sistema:** Venta de Pasajes - Módulo de Gestión de Viajes

---

## 🔍 1. RESUMEN EJECUTIVO

### Problema Identificado
En la interfaz de "Detalle de Ruta y Bus" (panel colapsable), los campos **Placa**, **Piloto** y **Copiloto** muestran el texto "PENDIENTE DE ASIGNACIÓN" en lugar de los valores reales de la base de datos, a pesar de que:
- La consulta SQL en el backend está correctamente estructurada
- Los datos existen en la base de datos
- El frontend está preparado para recibirlos

### Causa Raíz
**PROBLEMA CRÍTICO:** La tabla `viajes` NO tiene asignados valores en los campos:
- `bus_id` (todos los registros tienen valor `NULL`)
- `chofer_id` (todos los registros tienen valor `NULL`)

Adicionalmente, **NO EXISTE** la tabla `asignaciones_buses` que el modelo SQL intenta consultar mediante LEFT JOIN para obtener el copiloto.

---

## 🗄️ 2. ANÁLISIS DE BASE DE DATOS

### 2.1 Estructura de la Tabla `viajes`
```sql
CREATE TABLE `viajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ruta_id` int(11) NOT NULL,
  `tipo_bus_id` int(11) DEFAULT NULL,
  `terminal_origen_id` int(11) DEFAULT NULL,
  `terminal_destino_id` int(11) DEFAULT NULL,
  `bus_id` int(11) DEFAULT NULL,              -- ⚠️ PROBLEMA: Siempre NULL
  `chofer_id` int(11) DEFAULT NULL,           -- ⚠️ PROBLEMA: Siempre NULL
  `fecha_salida` datetime NOT NULL,
  `hora_salida` time DEFAULT NULL,
  -- ... otros campos
  PRIMARY KEY (`id`),
  CONSTRAINT `viajes_ibfk_2` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`),
  CONSTRAINT `viajes_ibfk_3` FOREIGN KEY (`chofer_id`) REFERENCES `usuarios` (`id`)
)
```

### 2.2 Datos Actuales en `viajes`
```sql
INSERT INTO `viajes` VALUES
(1, 11, 5, 9, 11, NULL, NULL, '2025-12-21 07:00:00', '07:00:00', ...),
(2, 12, 4, 8, 10, NULL, NULL, '2025-12-21 07:40:00', '07:40:00', ...),
(3, 14, 7, 9, 12, NULL, NULL, '2025-12-24 08:00:00', '08:00:00', ...);
```

**Observación:** Todos los viajes tienen `bus_id = NULL` y `chofer_id = NULL`.

### 2.3 Tabla `buses` (Existe y tiene datos)
```sql
CREATE TABLE `buses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `numero_interno` varchar(20) DEFAULT NULL,
  `chofer_default_id` int(11) DEFAULT NULL,
  -- ... otros campos
)

INSERT INTO `buses` VALUES
(1, 'ABC-123', '01-2025', 'Mercedes-Benz', 'Paradiso 1800', 1, 3, 'activo');
```

### 2.4 Tabla `asignaciones_buses` (NO EXISTE)
El modelo `RutaModel.php` intenta hacer un LEFT JOIN con esta tabla:
```php
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
```

**Resultado:** Esta consulta siempre devuelve NULL para el copiloto porque la tabla no existe.

---

## 💻 3. ANÁLISIS DEL CÓDIGO BACKEND

### 3.1 Método `obtenerViajePorId()` en RutaModel.php
```php
public function obtenerViajePorId($id)
{
    $sql = "SELECT 
            v.*,
            r.origen,
            r.destino,
            tb.nombre as tipo_bus,
            COALESCE(tb.capacidad, 0) as capacidad,
            COALESCE(term_origen.nombre_sede, 'Sin Asignar') as terminal_origen,
            COALESCE(term_destino.nombre_sede, 'Sin Asignar') as terminal_destino,
            COALESCE(CONCAT(u1.nombres, ' ', u1.apellidos), 'PENDIENTE DE ASIGNACIÓN') as chofer_nombre,
            COALESCE(CONCAT(u2.nombres, ' ', u2.apellidos), 'PENDIENTE DE ASIGNACIÓN') as copiloto_nombre,
            COALESCE(b.placa, 'PENDIENTE DE ASIGNACIÓN') as bus_placa,
            COALESCE(b.numero_interno, 'S/N') as bus_numero
        FROM viajes v
        INNER JOIN rutas r ON v.ruta_id = r.id
        LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
        LEFT JOIN terminales term_origen ON v.terminal_origen_id = term_origen.id
        LEFT JOIN terminales term_destino ON v.terminal_destino_id = term_destino.id
        LEFT JOIN buses b ON v.bus_id = b.id                    -- ⚠️ v.bus_id es NULL
        LEFT JOIN usuarios u1 ON v.chofer_id = u1.id            -- ⚠️ v.chofer_id es NULL
        LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1  -- ⚠️ Tabla no existe
        LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
        WHERE v.id = :id";
}
```

**Análisis:**
1. ✅ La consulta SQL está correctamente estructurada
2. ✅ Usa COALESCE para valores por defecto
3. ❌ `v.bus_id` es NULL → LEFT JOIN con `buses` no encuentra nada → `b.placa` es NULL → Retorna "PENDIENTE DE ASIGNACIÓN"
4. ❌ `v.chofer_id` es NULL → LEFT JOIN con `usuarios u1` no encuentra nada → Retorna "PENDIENTE DE ASIGNACIÓN"
5. ❌ Tabla `asignaciones_buses` no existe → LEFT JOIN falla → `u2.nombres` es NULL → Retorna "PENDIENTE DE ASIGNACIÓN"

---

## 🎨 4. ANÁLISIS DEL CÓDIGO FRONTEND

### 4.1 Función `actualizarPanelDetalleBus()` en venta_pasajes.php
```javascript
function actualizarPanelDetalleBus(data, viajeId) {
    $('#txtDetallePlaca').val(data.bus_placa || 'PENDIENTE DE ASIGNACIÓN');
    $('#txtDetallePiloto').val(data.chofer_nombre || 'PENDIENTE DE ASIGNACIÓN');
    $('#txtDetalleCopiloto').val(data.copiloto_nombre || 'PENDIENTE DE ASIGNACIÓN');
    $('#txtDetalleHora').val(data.hora_salida || '');
    // ... más campos
}
```

**Análisis:**
1. ✅ El código frontend está correctamente implementado
2. ✅ Espera recibir `data.bus_placa`, `data.chofer_nombre`, `data.copiloto_nombre`
3. ❌ Recibe valores NULL del backend → Muestra "PENDIENTE DE ASIGNACIÓN"

### 4.2 HTML de los campos
```html
<!-- Líneas 544-558 de venta_pasajes.php -->
<div class="col-md-3">
    <div class="form-group mb-0">
        <label class="text-secondary small text-uppercase font-weight-bold mb-0">Placa</label>
        <input type="text" readonly class="form-control-plaintext font-weight-bold text-dark" 
               id="txtDetallePlaca" value="">
    </div>
</div>
<div class="col-md-3">
    <div class="form-group mb-0">
        <label class="text-secondary small text-uppercase font-weight-bold mb-0">Piloto</label>
        <input type="text" readonly class="form-control-plaintext font-weight-bold text-dark" 
               id="txtDetallePiloto" value="">
    </div>
</div>
<div class="col-md-3">
    <div class="form-group mb-0">
        <label class="text-secondary small text-uppercase font-weight-bold mb-0">Copiloto</label>
        <input type="text" readonly class="form-control-plaintext font-weight-bold text-dark" 
               id="txtDetalleCopiloto" value="">
    </div>
</div>
```

**Análisis:**
✅ Los campos HTML están correctamente definidos y vinculados con los IDs correctos.

---

## 🔧 5. SOLUCIÓN PROPUESTA

### 5.1 Opción A: Solución Completa (RECOMENDADA)

#### Paso 1: Crear tabla `asignaciones_buses`
```sql
CREATE TABLE `asignaciones_buses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bus_id` INT(11) NOT NULL,
  `copiloto_id` INT(11) DEFAULT NULL,
  `fecha_asignacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `estado` TINYINT(1) DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  PRIMARY KEY (`id`),
  KEY `bus_id` (`bus_id`),
  KEY `copiloto_id` (`copiloto_id`),
  CONSTRAINT `fk_asignaciones_bus` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asignaciones_copiloto` FOREIGN KEY (`copiloto_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Paso 2: Insertar asignación de copiloto de ejemplo
```sql
-- Asignar copiloto Alan Vera (usuario ID 9) al bus ABC-123 (bus ID 1)
INSERT INTO `asignaciones_buses` (`bus_id`, `copiloto_id`, `estado`) 
VALUES (1, 9, 1);
```

#### Paso 3: Actualizar registros de `viajes` con datos reales
```sql
-- Asignar bus y chofer a los viajes existentes
UPDATE `viajes` SET `bus_id` = 1, `chofer_id` = 7 WHERE `id` = 1;  -- Miguel Angel Mendez
UPDATE `viajes` SET `bus_id` = 1, `chofer_id` = 8 WHERE `id` = 2;  -- Grover Surco
UPDATE `viajes` SET `bus_id` = 1, `chofer_id` = 7 WHERE `id` = 3;  -- Miguel Angel Mendez
```

**Nota:** Los IDs de chofer (7 y 8) corresponden a usuarios con perfil "Chofer" según la tabla `personal`.

#### Paso 4: Verificar datos
```sql
-- Verificar que los viajes ahora tienen bus y chofer asignados
SELECT 
    v.id,
    v.bus_id,
    v.chofer_id,
    b.placa,
    CONCAT(u1.nombres, ' ', u1.apellidos) as chofer,
    CONCAT(u2.nombres, ' ', u2.apellidos) as copiloto
FROM viajes v
LEFT JOIN buses b ON v.bus_id = b.id
LEFT JOIN usuarios u1 ON v.chofer_id = u1.id
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id;
```

**Resultado esperado:**
```
id | bus_id | chofer_id | placa   | chofer              | copiloto
---|--------|-----------|---------|---------------------|------------
1  | 1      | 7         | ABC-123 | Miguel Angel Mendez | Alan Vera
2  | 1      | 8         | ABC-123 | Grover Surco        | Alan Vera
3  | 1      | 7         | ABC-123 | Miguel Angel Mendez | Alan Vera
```

---

### 5.2 Opción B: Solución Rápida (Sin copiloto)

Si no se requiere la funcionalidad de copiloto inmediatamente:

#### Paso 1: Actualizar solo `viajes` con bus y chofer
```sql
UPDATE `viajes` SET `bus_id` = 1, `chofer_id` = 7 WHERE `id` = 1;
UPDATE `viajes` SET `bus_id` = 1, `chofer_id` = 8 WHERE `id` = 2;
UPDATE `viajes` SET `bus_id` = 1, `chofer_id` = 7 WHERE `id` = 3;
```

#### Paso 2: Modificar consulta SQL en `RutaModel.php`
Eliminar las líneas que hacen referencia a `asignaciones_buses`:
```php
// ELIMINAR estas líneas:
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id

// Y cambiar:
COALESCE(CONCAT(u2.nombres, ' ', u2.apellidos), 'PENDIENTE DE ASIGNACIÓN') as copiloto_nombre,

// Por:
'PENDIENTE DE ASIGNACIÓN' as copiloto_nombre,
```

**Resultado:** Mostrará placa y piloto correctamente, pero copiloto siempre será "PENDIENTE DE ASIGNACIÓN".

---

### 5.3 Opción C: Usar `chofer_default_id` de la tabla `buses`

Si se prefiere usar el chofer por defecto del bus en lugar de asignar por viaje:

#### Paso 1: Actualizar solo `bus_id` en viajes
```sql
UPDATE `viajes` SET `bus_id` = 1 WHERE `id` IN (1, 2, 3);
```

#### Paso 2: Modificar consulta SQL en `RutaModel.php`
```php
// Cambiar:
LEFT JOIN usuarios u1 ON v.chofer_id = u1.id

// Por:
LEFT JOIN usuarios u1 ON COALESCE(v.chofer_id, b.chofer_default_id) = u1.id
```

**Ventaja:** Si no hay chofer específico asignado al viaje, usa el chofer por defecto del bus.

---

## 📊 6. ALGORITMO DE SOLUCIÓN RECOMENDADO

```
INICIO
│
├─ PASO 1: Crear infraestructura de base de datos
│  ├─ Ejecutar script SQL para crear tabla `asignaciones_buses`
│  └─ Verificar creación exitosa
│
├─ PASO 2: Poblar datos de asignaciones
│  ├─ Insertar asignación de copiloto para bus existente
│  └─ Verificar inserción
│
├─ PASO 3: Actualizar viajes existentes
│  ├─ Asignar `bus_id` a cada viaje
│  ├─ Asignar `chofer_id` a cada viaje
│  └─ Ejecutar UPDATE masivo
│
├─ PASO 4: Validar datos
│  ├─ Ejecutar consulta SELECT de verificación
│  ├─ Confirmar que no hay valores NULL
│  └─ Verificar integridad referencial
│
├─ PASO 5: Probar en frontend
│  ├─ Recargar página de venta de pasajes
│  ├─ Seleccionar un viaje
│  ├─ Expandir panel "Detalle de Ruta y Bus"
│  └─ Verificar que aparezcan: Placa, Piloto, Copiloto
│
└─ FIN
```

---

## 🎯 7. IMPACTO Y PRIORIDAD

### Severidad: **ALTA** 🔴
- Los usuarios no pueden ver información crítica del viaje
- Afecta la operación diaria del sistema
- Genera confusión sobre el estado de asignación de recursos

### Esfuerzo de Implementación: **BAJO** 🟢
- Solución Completa (Opción A): ~15 minutos
- Solución Rápida (Opción B): ~5 minutos
- Solución Alternativa (Opción C): ~10 minutos

### Riesgo: **BAJO** 🟢
- Los scripts SQL son simples y seguros
- No requiere modificación de código existente (solo datos)
- Puede revertirse fácilmente

---

## 📝 8. RECOMENDACIONES ADICIONALES

### 8.1 Mejoras en el Flujo de Creación de Viajes
Actualmente, cuando se crea un viaje en `crear_ruta.php`, los campos `bus_id` y `chofer_id` no se están capturando. Se recomienda:

1. **Agregar campos en el formulario de creación de viajes:**
   - Selector de Bus (dropdown con buses activos)
   - Selector de Chofer (dropdown con usuarios con rol "Chofer")
   - Selector de Copiloto (dropdown con usuarios con rol "Copiloto")

2. **Modificar el método `guardarRutaViaje()` en `RutaModel.php`:**
   ```php
   // Agregar en el INSERT:
   $sql = "INSERT INTO viajes (
       ruta_id, tipo_bus_id, terminal_origen_id, terminal_destino_id,
       bus_id, chofer_id,  -- ← AGREGAR ESTOS CAMPOS
       fecha_salida, hora_salida, ...
   ) VALUES (
       :ruta_id, :tipo_bus_id, :terminal_origen_id, :terminal_destino_id,
       :bus_id, :chofer_id,  -- ← AGREGAR ESTOS PARÁMETROS
       :fecha_salida, :hora_salida, ...
   )";
   ```

### 8.2 Validaciones Recomendadas
```php
// Antes de insertar un viaje, validar:
if (empty($data['bus_id'])) {
    return "Error: Debe asignar un bus al viaje";
}
if (empty($data['chofer_id'])) {
    return "Error: Debe asignar un chofer al viaje";
}
```

### 8.3 Interfaz de Asignación Dinámica
Crear un módulo de "Asignación de Recursos" donde se pueda:
- Ver todos los viajes programados
- Asignar/reasignar buses y choferes
- Ver disponibilidad de recursos por fecha
- Gestionar asignaciones de copilotos

---

## 🧪 9. PLAN DE PRUEBAS

### Prueba 1: Verificar datos en base de datos
```sql
SELECT * FROM asignaciones_buses;
SELECT id, bus_id, chofer_id FROM viajes;
```
**Resultado esperado:** Todos los viajes deben tener `bus_id` y `chofer_id` con valores numéricos.

### Prueba 2: Verificar consulta del modelo
```php
// En RutaModel.php, agregar log temporal:
$resultado = $this->db->single();
error_log("DEBUG viaje: " . print_r($resultado, true));
return $resultado;
```
**Resultado esperado:** El log debe mostrar valores reales en `bus_placa`, `chofer_nombre`, `copiloto_nombre`.

### Prueba 3: Verificar frontend
1. Abrir navegador en modo incógnito
2. Ir a: `http://localhost/venta-pasajes/ventas/venta_pasajes/viaje_id=1`
3. Expandir panel "Detalle de Ruta y Bus"
4. Verificar que aparezcan:
   - **Placa:** ABC-123
   - **Piloto:** Miguel Angel Mendez Jimenez
   - **Copiloto:** Alan Vera

---

## 📌 10. CONCLUSIÓN

El problema NO es de código, sino de **datos faltantes en la base de datos**. La arquitectura del sistema está correctamente diseñada, pero los viajes se crearon sin asignar buses ni choferes.

**Solución inmediata:**
1. Ejecutar los scripts SQL de la Opción A (Sección 5.1)
2. Refrescar la página en el navegador
3. Verificar que los datos aparezcan correctamente

**Solución a largo plazo:**
1. Modificar el formulario de creación de viajes para incluir selección de bus y chofer
2. Agregar validaciones para evitar viajes sin asignación
3. Crear interfaz de gestión de asignaciones

---

## 📎 ANEXOS

### Anexo A: Scripts SQL Completos
Ver Sección 5.1

### Anexo B: Datos de Usuarios con Perfil Chofer/Copiloto
```sql
-- Choferes disponibles:
SELECT id, CONCAT(nombres, ' ', apellidos) as nombre, perfil 
FROM personal 
WHERE perfil = 'Chofer';

-- Resultado:
-- ID 18: Miguel Angel Mendez Jimenez
-- ID 19: Grover Surco Mamani

-- Copilotos disponibles:
SELECT id, CONCAT(nombres, ' ', apellidos) as nombre, perfil 
FROM personal 
WHERE perfil = 'Copiloto';

-- Resultado:
-- ID 20: Alan Vera
```

### Anexo C: Estructura de Relaciones
```
viajes
  ├─ bus_id → buses.id
  │            └─ placa (ABC-123)
  │
  ├─ chofer_id → usuarios.id
  │                └─ nombres + apellidos
  │
  └─ (via buses) → asignaciones_buses
                     └─ copiloto_id → usuarios.id
                                        └─ nombres + apellidos
```

---

**FIN DEL INFORME**

*Generado automáticamente por Antigravity AI*  
*Fecha: 2025-12-25*
