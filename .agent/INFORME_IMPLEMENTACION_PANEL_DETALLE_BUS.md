# INFORME DE ANÁLISIS E IMPLEMENTACIÓN
## Panel Desplegable "Detalle de Ruta y Bus"

**Fecha:** 24 de Diciembre de 2025  
**Analista:** Antigravity AI  
**Objetivo:** Implementar formulario desplegable con información dinámica del viaje sin afectar modales existentes

---

## 1. ANÁLISIS DE IMÁGENES PROPORCIONADAS

### Imagen 1: Estado Colapsado
- **Elemento:** Panel con título "Detalle de Ruta y Bus"
- **Color:** Fondo celeste/cyan claro (rgba(23, 162, 184, 0.1))
- **Icono:** Bus (fas fa-bus)
- **Botón:** Símbolo "+" para expandir
- **Ubicación:** Debajo del panel "Descripción" y arriba de la leyenda de estados

### Imagen 2: Estado Expandido
El panel muestra un formulario de solo lectura con los siguientes campos distribuidos en 4 columnas:

| Campo | Valor Ejemplo | Tipo |
|-------|---------------|------|
| **PLACA DE BUS** | PENDIENTE DE ASIGNACIÓN | readonly input |
| **PILOTO** | PENDIENTE DE ASIGNACIÓN | readonly input |
| **COPILOTO** | PENDIENTE DE ASIGNACIÓN | readonly input |
| **HORA SALIDA** | 09:00:00 | readonly input (color azul) |
| **LIBRES** | 42 | readonly input |
| **VENDIDOS** | 0 | readonly input |
| **RESERVADOS** | 0 | readonly input |
| **TOTAL** | 42 | readonly input |
| **DÍA** | 2025-12-23 09:00:00 | readonly input |
| **FECHA DE VIAJE** | 2025-12-23 09:00:00 | readonly input |
| **HORA PARTIDA** | 09:05:00 | readonly input |
| **Terminal** | EL ALTO | readonly button (cyan) |

---

## 2. ANÁLISIS DE CÓDIGO ACTUAL

### 2.1. Archivo: `venta_pasajes.php`

**Ubicación Actual del Panel:**
- Líneas 526-571: Panel "Detalle de Ruta y Bus" ya existe
- **Estado:** Parcialmente implementado con solo 4 campos (Placa, Piloto, Copiloto, Hora Salida)
- **Problema:** Faltan 8 campos adicionales mostrados en la imagen

**Estructura Actual:**
```html
<div class="card card-info card-outline">
    <div class="card-header" data-bs-toggle="collapse" data-bs-target="#collapseDetalleBus">
        <h3 class="card-title">Detalle de Ruta y Bus</h3>
    </div>
    <div id="collapseDetalleBus" class="collapse">
        <div class="card-body">
            <!-- Solo 4 campos implementados -->
        </div>
    </div>
</div>
```

**Campos Implementados:**
1. ✅ Placa (`#txtDetallePlaca`)
2. ✅ Piloto (`#txtDetallePiloto`)
3. ✅ Copiloto (`#txtDetalleCopiloto`)
4. ✅ Hora Salida (`#txtDetalleHora`)

**Campos Faltantes:**
5. ❌ Libres
6. ❌ Vendidos
7. ❌ Reservados
8. ❌ Total
9. ❌ Día
10. ❌ Fecha de Viaje
11. ❌ Hora Partida
12. ❌ Terminal (botón)

---

## 3. ANÁLISIS DE BASE DE DATOS

### 3.1. Tabla `viajes`

**Estructura Relevante:**
```sql
CREATE TABLE `viajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ruta_id` int(11) NOT NULL,
  `tipo_bus_id` int(11) DEFAULT NULL,
  `terminal_origen_id` int(11) DEFAULT NULL,
  `terminal_destino_id` int(11) DEFAULT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `chofer_id` int(11) DEFAULT NULL,
  `fecha_salida` datetime NOT NULL,
  `hora_salida` time DEFAULT NULL,
  `fecha_llegada_estimada` datetime DEFAULT NULL,
  `hora_llegada` time DEFAULT NULL,
  `precio_base` decimal(10,2) DEFAULT 0.00,
  `tipo_servicio` varchar(50) DEFAULT 'Ejecutivo',
  `estado` enum('Activo','Programado',...) DEFAULT 'Programado',
  ...
)
```

**Campos Disponibles para el Panel:**
- ✅ `fecha_salida` → DÍA y FECHA DE VIAJE
- ✅ `hora_salida` → HORA SALIDA y HORA PARTIDA
- ✅ `terminal_origen_id` → TERMINAL (FK a tabla `terminales`)
- ⚠️ `bus_id` → PLACA (FK a tabla `buses`, actualmente NULL en datos)
- ⚠️ `chofer_id` → PILOTO (FK a tabla `usuarios`, actualmente NULL)

### 3.2. Tabla `buses`

```sql
CREATE TABLE `buses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `numero_interno` varchar(20) DEFAULT NULL,
  `chofer_default_id` int(11) DEFAULT NULL,
  ...
)
```

**Nota:** La tabla `buses` tiene un campo `chofer_default_id` que podría usarse como fallback.

### 3.3. Tabla `asignaciones_buses`

```sql
CREATE TABLE `asignaciones_buses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chofer_id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `copiloto_id` int(11) NOT NULL,
  `fecha_asignacion` datetime DEFAULT current_timestamp(),
  `estado` tinyint(4) DEFAULT 1,
  ...
)
```

**Uso:** Esta tabla relaciona choferes, copilotos y buses. Puede usarse para obtener el copiloto.

### 3.4. Tabla `boletos`

```sql
CREATE TABLE `boletos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `viaje_id` int(11) NOT NULL,
  `numero_asiento` int(11) NOT NULL,
  `estado` enum('reservado','vendido','cancelado','abordado'),
  ...
)
```

**Uso:** Para calcular LIBRES, VENDIDOS, RESERVADOS y TOTAL.

### 3.5. Tabla `terminales`

```sql
CREATE TABLE `terminales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_sede` varchar(100) NOT NULL,
  ...
)
```

**Uso:** Para obtener el nombre de la terminal de origen.

---

## 4. ANÁLISIS DEL MODELO `RutaModel.php`

### 4.1. Método Existente: `obtenerViajePorId($id)`

**Ubicación:** Líneas 396-421

**SQL Actual:**
```sql
SELECT 
    v.*,
    r.origen,
    r.destino,
    tb.nombre as tipo_bus,
    COALESCE(tb.capacidad, 0) as capacidad,
    COALESCE(term_origen.nombre_sede, 'Sin Asignar') as terminal_origen,
    COALESCE(term_destino.nombre_sede, 'Sin Asignar') as terminal_destino,
    COALESCE(CONCAT(u1.nombres, ' ', u1.apellidos), 'Sin Conductor') as chofer_nombre,
    COALESCE(b.placa, 'S/P') as bus_placa,
    COALESCE(b.numero_interno, 'S/N') as bus_numero
FROM viajes v
INNER JOIN rutas r ON v.ruta_id = r.id
LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
LEFT JOIN terminales term_origen ON v.terminal_origen_id = term_origen.id
LEFT JOIN terminales term_destino ON v.terminal_destino_id = term_destino.id
LEFT JOIN usuarios u1 ON v.chofer_id = u1.id
LEFT JOIN buses b ON v.bus_id = b.id
WHERE v.id = :id
```

**Campos que Retorna:**
- ✅ `bus_placa` → PLACA
- ✅ `chofer_nombre` → PILOTO
- ✅ `hora_salida` → HORA SALIDA
- ✅ `terminal_origen` → TERMINAL
- ✅ `fecha_salida` → DÍA y FECHA DE VIAJE
- ❌ **Falta:** COPILOTO
- ❌ **Falta:** LIBRES, VENDIDOS, RESERVADOS, TOTAL (se calculan con `obtenerConteoAsientos()`)

### 4.2. Método Existente: `obtenerConteoAsientos($viajeId)`

**Ubicación:** Líneas 746-785

**Retorna:**
```php
[
    'libres' => $libres,
    'reservados' => $reservados,
    'vendidos' => $vendidos
]
```

**Uso:** Este método ya calcula LIBRES, VENDIDOS y RESERVADOS. Solo falta calcular TOTAL.

---

## 5. PROBLEMAS IDENTIFICADOS

### 5.1. Datos Faltantes en la Base de Datos

**Problema Principal:** Los viajes actuales NO tienen asignados:
- `bus_id` = NULL
- `chofer_id` = NULL

**Ejemplo de Datos Reales:**
```sql
INSERT INTO `viajes` VALUES
(1, 11, 5, 9, 11, NULL, NULL, '2025-12-21 07:00:00', '07:00:00', ...),
(2, 12, 4, 8, 10, NULL, NULL, '2025-12-21 07:40:00', '07:40:00', ...)
```

**Consecuencia:**
- El JOIN con `buses` retorna NULL → Placa = "S/P"
- El JOIN con `usuarios` (chofer) retorna NULL → Piloto = "Sin Conductor"
- No hay forma de obtener el COPILOTO sin asignación

### 5.2. Copiloto No Disponible en Query Actual

El método `obtenerViajePorId()` NO incluye el copiloto. Se necesita:

**Opción 1:** JOIN con `asignaciones_buses`
```sql
LEFT JOIN asignaciones_buses ab ON v.bus_id = ab.bus_id AND ab.estado = 1
LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
```

**Opción 2:** Agregar campo `copiloto_id` a la tabla `viajes`

### 5.3. Campos Duplicados en la Imagen

La imagen muestra campos que parecen duplicados:
- **DÍA** y **FECHA DE VIAJE**: Ambos muestran `2025-12-23 09:00:00`
- **HORA SALIDA** y **HORA PARTIDA**: Valores similares (09:00:00 vs 09:05:00)

**Interpretación:**
- **DÍA:** Fecha completa de salida (`fecha_salida`)
- **FECHA DE VIAJE:** Podría ser la misma o `fecha_llegada_estimada`
- **HORA SALIDA:** Hora de salida programada (`hora_salida`)
- **HORA PARTIDA:** Podría ser hora real de partida (no existe en BD, usar `hora_salida`)

---

## 6. PLAN DE IMPLEMENTACIÓN

### 6.1. Modificaciones en la Base de Datos

**NO SE REQUIEREN** cambios en la estructura. Solo se necesita:

1. **Asignar buses y choferes a los viajes existentes:**
   ```sql
   UPDATE viajes SET bus_id = 1, chofer_id = 3 WHERE id = 1;
   UPDATE viajes SET bus_id = 2, chofer_id = 4 WHERE id = 2;
   ```

2. **Crear asignaciones de copiloto:**
   ```sql
   INSERT INTO asignaciones_buses (chofer_id, bus_id, copiloto_id, estado)
   VALUES (3, 1, 7, 1), (4, 2, 8, 1);
   ```

### 6.2. Modificaciones en `RutaModel.php`

**Acción:** Actualizar método `obtenerViajePorId()` para incluir copiloto.

**Nuevo SQL:**
```sql
SELECT 
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
LEFT JOIN buses b ON v.bus_id = b.id
LEFT JOIN usuarios u1 ON v.chofer_id = u1.id
LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
WHERE v.id = :id
```

**Cambios:**
- ✅ Agregado JOIN con `asignaciones_buses` y `usuarios` (u2) para copiloto
- ✅ Cambiado "Sin Conductor" → "PENDIENTE DE ASIGNACIÓN"
- ✅ Cambiado "S/P" → "PENDIENTE DE ASIGNACIÓN"

### 6.3. Modificaciones en `venta_pasajes.php`

**Acción:** Agregar los 8 campos faltantes al panel "Detalle de Ruta y Bus".

**HTML a Agregar (después de la línea 567):**

```html
<!-- Segunda Fila: Contadores -->
<div class="row mt-3">
    <div class="col-md-3">
        <div class="form-group mb-0">
            <label class="text-secondary small text-uppercase font-weight-bold mb-0">Libres</label>
            <input type="text" readonly class="form-control-plaintext font-weight-bold text-success" id="txtDetalleLibres" value="">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-0">
            <label class="text-secondary small text-uppercase font-weight-bold mb-0">Vendidos</label>
            <input type="text" readonly class="form-control-plaintext font-weight-bold text-info" id="txtDetalleVendidos" value="">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-0">
            <label class="text-secondary small text-uppercase font-weight-bold mb-0">Reservados</label>
            <input type="text" readonly class="form-control-plaintext font-weight-bold text-warning" id="txtDetalleReservados" value="">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-0">
            <label class="text-secondary small text-uppercase font-weight-bold mb-0">Total</label>
            <input type="text" readonly class="form-control-plaintext font-weight-bold text-dark" id="txtDetalleTotal" value="">
        </div>
    </div>
</div>

<!-- Tercera Fila: Fechas y Hora -->
<div class="row mt-3">
    <div class="col-md-3">
        <div class="form-group mb-0">
            <label class="text-secondary small text-uppercase font-weight-bold mb-0">Día</label>
            <input type="text" readonly class="form-control-plaintext font-weight-bold text-dark" id="txtDetalleDia" value="">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-0">
            <label class="text-secondary small text-uppercase font-weight-bold mb-0">Fecha de Viaje</label>
            <input type="text" readonly class="form-control-plaintext font-weight-bold text-dark" id="txtDetalleFechaViaje" value="">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-0">
            <label class="text-secondary small text-uppercase font-weight-bold mb-0">Hora Partida</label>
            <input type="text" readonly class="form-control-plaintext font-weight-bold text-primary" id="txtDetalleHoraPartida" value="">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group mb-0">
            <label class="text-secondary small text-uppercase font-weight-bold mb-0">Terminal</label>
            <button type="button" class="btn btn-info btn-sm btn-block font-weight-bold" id="btnDetalleTerminal" disabled>
                <i class="fas fa-building mr-1"></i> <span id="txtDetalleTerminal">-</span>
            </button>
        </div>
    </div>
</div>
```

### 6.4. Modificaciones en JavaScript

**Archivo:** Probablemente `venta_pasajes.php` (script inline) o archivo JS separado

**Función a Modificar:** La que carga los datos del viaje (probablemente en el evento `change` del select de viajes)

**Código JavaScript a Agregar:**

```javascript
function cargarDetallesViaje(viajeId) {
    $.ajax({
        url: 'ruta/obtener_ruta_viaje',
        method: 'POST',
        data: { viaje_id: viajeId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const viaje = response.viaje;
                const conteo = response.conteo;
                
                // Campos existentes
                $('#txtDetallePlaca').val(viaje.bus_placa || 'PENDIENTE DE ASIGNACIÓN');
                $('#txtDetallePiloto').val(viaje.chofer_nombre || 'PENDIENTE DE ASIGNACIÓN');
                $('#txtDetalleCopiloto').val(viaje.copiloto_nombre || 'PENDIENTE DE ASIGNACIÓN');
                $('#txtDetalleHora').val(viaje.hora_salida || '');
                
                // Nuevos campos: Contadores
                $('#txtDetalleLibres').val(conteo.libres || 0);
                $('#txtDetalleVendidos').val(conteo.vendidos || 0);
                $('#txtDetalleReservados').val(conteo.reservados || 0);
                $('#txtDetalleTotal').val(viaje.capacidad || 0);
                
                // Nuevos campos: Fechas
                $('#txtDetalleDia').val(viaje.fecha_salida || '');
                $('#txtDetalleFechaViaje').val(viaje.fecha_salida || '');
                $('#txtDetalleHoraPartida').val(viaje.hora_salida || '');
                $('#txtDetalleTerminal').text(viaje.terminal_origen || 'Sin Asignar');
            }
        },
        error: function() {
            console.error('Error al cargar detalles del viaje');
        }
    });
}
```

### 6.5. Modificaciones en el Controlador (Backend)

**Archivo:** Probablemente `app/controllers/Ruta.php` o similar

**Método:** `obtener_ruta_viaje()`

**Código PHP a Modificar:**

```php
public function obtener_ruta_viaje() {
    $viajeId = $_POST['viaje_id'] ?? 0;
    
    if (!$viajeId) {
        echo json_encode(['success' => false, 'message' => 'ID de viaje no proporcionado']);
        return;
    }
    
    // Obtener datos del viaje
    $viaje = $this->rutaModel->obtenerViajePorId($viajeId);
    
    if (!$viaje) {
        echo json_encode(['success' => false, 'message' => 'Viaje no encontrado']);
        return;
    }
    
    // Obtener conteo de asientos
    $conteo = $this->rutaModel->obtenerConteoAsientos($viajeId);
    
    // Obtener asientos ocupados
    $asientosOcupados = $this->rutaModel->obtenerAsientosOcupados($viajeId);
    
    echo json_encode([
        'success' => true,
        'viaje' => $viaje,
        'conteo' => $conteo,
        'asientos_ocupados' => $asientosOcupados
    ]);
}
```

---

## 7. GARANTÍAS DE NO AFECTACIÓN

### 7.1. Modal "Mapa de Asientos"

**Ubicación:** Líneas 450-709 en `venta_pasajes.php`

**Elementos Críticos:**
- Canvas del bus: `#canvasBus`
- Contenedor: `#contenedor-bus`
- Tabs de pisos: `#floor-tabs-container`
- Formulario de venta: `#formVenta`

**Garantía:**
- ✅ NO se modificará ninguna línea dentro del tab `#tab-mapa`
- ✅ Solo se agregarán campos en el panel colapsable (líneas 526-571)
- ✅ El JavaScript de renderizado del bus NO se tocará
- ✅ Los eventos de clic en asientos NO se modificarán

### 7.2. Modal "Manifiesto de Pasajeros"

**Ubicación:** Líneas 713-768 en `venta_pasajes.php`

**Elementos Críticos:**
- Modal: `#modalManifiesto`
- Tabla: `#tabla_manifiesto`
- Tbody: `#tbody_manifiesto`
- Total recaudado: `#txtTotalRecaudado`

**Garantía:**
- ✅ NO se modificará ninguna línea del modal
- ✅ El botón de apertura (`#btnAbrirManifiesto`) NO se tocará
- ✅ La lógica de carga de pasajeros NO se modificará
- ✅ El cálculo del total recaudado NO se afectará

---

## 8. RESUMEN DE CAMBIOS

### Archivos a Modificar:

1. **`app/models/RutaModel.php`**
   - Línea 398-420: Actualizar SQL de `obtenerViajePorId()` para incluir copiloto
   - Cambio: Agregar 2 JOINs y 1 campo en SELECT

2. **`app/views/ventas/venta_pasajes.php`**
   - Línea 567: Agregar 8 nuevos campos al panel "Detalle de Ruta y Bus"
   - Cambio: Insertar ~60 líneas de HTML

3. **JavaScript (inline o archivo separado)**
   - Modificar función de carga de viaje para poblar nuevos campos
   - Cambio: Agregar ~15 líneas de código

4. **Controlador (opcional, si no existe)**
   - Asegurar que el endpoint retorne `copiloto_nombre` y `conteo`

### SQL Opcional (Datos de Prueba):

```sql
-- Asignar buses y choferes a viajes
UPDATE viajes SET bus_id = 1, chofer_id = 3 WHERE id = 1;
UPDATE viajes SET bus_id = 2, chofer_id = 4 WHERE id = 2;

-- Crear asignaciones de copiloto
INSERT INTO asignaciones_buses (chofer_id, bus_id, copiloto_id, estado)
VALUES (3, 1, 7, 1), (4, 2, 8, 1);
```

---

## 9. RIESGOS Y MITIGACIONES

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Datos NULL en BD | Alta | Medio | Usar COALESCE con "PENDIENTE DE ASIGNACIÓN" |
| Conflicto con JS existente | Baja | Alto | No modificar funciones existentes, solo agregar |
| Error en JOIN de copiloto | Media | Bajo | Usar LEFT JOIN para evitar pérdida de registros |
| Campos duplicados confusos | Baja | Bajo | Usar mismos valores para DÍA y FECHA DE VIAJE |

---

## 10. CRONOGRAMA DE IMPLEMENTACIÓN

### Fase 1: Preparación de Datos (5 minutos)
- Ejecutar SQL para asignar buses y choferes
- Crear asignaciones de copiloto

### Fase 2: Backend (10 minutos)
- Modificar `obtenerViajePorId()` en `RutaModel.php`
- Verificar que retorne `copiloto_nombre`

### Fase 3: Frontend (15 minutos)
- Agregar 8 campos al HTML del panel
- Modificar JavaScript para poblar campos

### Fase 4: Pruebas (10 minutos)
- Verificar que el panel se expanda/colapse correctamente
- Verificar que los datos se carguen dinámicamente
- Verificar que NO afecte modales existentes

**Tiempo Total Estimado:** 40 minutos

---

## 11. CONCLUSIÓN

La implementación del formulario desplegable es **VIABLE** y de **BAJO RIESGO**. Los cambios son:

✅ **Mínimos:** Solo 3 archivos afectados  
✅ **Aislados:** No tocan lógica de modales existentes  
✅ **Reversibles:** Fácil rollback si hay problemas  
✅ **Escalables:** Estructura preparada para futuros campos  

**Recomendación:** Proceder con la implementación siguiendo el plan detallado.

---

**Fin del Informe**
