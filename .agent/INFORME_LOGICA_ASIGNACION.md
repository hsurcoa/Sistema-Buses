# 🧠 INFORME: LÓGICA DEFINITIVA PARA ASIGNACIÓN DE RECURSOS

**Fecha:** 25 de Diciembre de 2025  
**Objetivo:** Implementar la asignación de buses y choferes al programar un viaje.  
**Módulo Objetivo:** `app/views/ventas/crear_ruta.php` (Programación de Viajes).

---

## 1. ANÁLISIS DEL PROBLEMA ACTUAL
El sistema actual permite crear un viaje ("Programar Ruta") definiendo origen, destino, horario y tipo de bus, pero **omite** la asignación del recurso físico (la unidad de bus específica y el conductor).
Esto genera registros en la tabla `viajes` con `bus_id = NULL` y `chofer_id = NULL`, causando el mensaje "PENDIENTE DE ASIGNACIÓN" en la venta de pasajes.

El módulo `asignar_buses.php` existe, pero funciona como una *tabla de maestros* (vincula estáticamente un chofer a un bus), no asigna el bus a un viaje específico.

---

## 2. LA SOLUCIÓN DEFINITIVA (ALGORITMO)

La lógica debe implementarse en el momento de crear/programar el viaje.

### Algoritmo Paso a Paso:

1.  **Selección de Tipo de Bus (Existente):**
    El usuario selecciona el tipo de bus (ej. "Bus Cama").

2.  **Filtrado de Unidades (NUEVO):**
    Al seleccionar el tipo, el sistema busca vía AJAX todos los buses activos que coincidan con ese tipo (ej. "Bus #10 - Placa ABC-123").

3.  **Selección de Unidad (NUEVO):**
    El usuario selecciona el bus específico del dropdown.

4.  **Auto-asignación de Tripulación (NUEVO - Lógica Inteligente):**
    *   Al seleccionar el bus, el sistema consulta la tabla `asignaciones_buses`.
    *   Si el bus tiene tripulación fija asignada, **automáticamente** pre-selecciona al Chofer y Copiloto en los campos correspondientes.
    *   Permite al usuario cambiar el chofer manualmente si es un reemplazo.

5.  **Guardado:**
    El controlador `Ventas::guardar_ruta_viaje` recibe `bus_id` y `chofer_id` y los guarda en la tabla `viajes`.

---

## 3. PLAN DE IMPLEMENTACIÓN TÉCNICA

### A. Frontend (`crear_ruta.php`)
Agregar un nuevo bloque HTML bajo los campos de fecha/hora:

```html
<!-- BLOQUE DE ASIGNACIÓN DE RECURSOS -->
<div class="card p-3 mt-3 border-primary">
    <h6 class="text-primary fw-bold">Asignación de Recursos (Operaciones)</h6>
    <div class="row">
        <!-- 1. Seleccionar Bus -->
        <div class="col-md-4">
            <label>Unidad / Bus:</label>
            <select name="bus_id" id="selectBus" class="form-select" onchange="cargarTripulacion(this.value)">
                <option value="">Seleccione Bus...</option>
                <!-- Llenar vía AJAX según Tipo de Bus -->
            </select>
        </div>
        
        <!-- 2. Chofer (Auto-llenado) -->
        <div class="col-md-4">
            <label>Conductor:</label>
            <select name="chofer_id" id="selectChofer" class="form-select">
                <!-- Se llena automáticamente -->
            </select>
        </div>
    </div>
</div>
```

### B. Backend (`RutaModel.php` y `Ventas.php`)

1.  **Nuevo Endpoint AJAX:** `obtener_buses_por_tipo($tipo_id)`
    *   Retorna lista de buses disponibles de ese tipo.

2.  **Nuevo Endpoint AJAX:** `obtener_tripulacion_bus($bus_id)`
    *   Retorna el `chofer_id` asignado a ese bus en `asignaciones_buses`.

3.  **Modificar `guardar_ruta_viaje`:**
    *   Agregar `bus_id` y `chofer_id` al array `$data`.
    *   Incluirlos en el `INSERT INTO viajes`.

---

## 4. REFERENCIA VISUAL
Se ha generado un mockup visual detallando exactamente dónde deben ir estos campos. Por favor revise el archivo adjunto: `mockup_solucion_asignacion.png`.

---

## 5. CONCLUSIÓN
Implementar esta lógica en el formulario de creación garantiza que cada viaje nazca con sus recursos asignados, eliminando el error de "PENDIENTE DE ASIGNACIÓN" y permitiendo un control operativo real.
