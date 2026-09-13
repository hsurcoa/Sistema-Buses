# 📚 GUÍA COMPLETA: Cómo Registrar Buses de Dos Pisos Correctamente

**Fecha:** 25 de diciembre de 2025 - 15:56 hrs  
**Problema Identificado:** ❌ El formulario NO tiene campo para seleccionar el tipo de bus

---

## 🔍 **PROBLEMA RAÍZ IDENTIFICADO**

### **El Formulario de Registro de Buses NO Tiene el Campo `tipo_bus_id`**

Al revisar el archivo `registrar_buses.php`, encontré que el formulario tiene:
- ✅ Placa
- ✅ Marca, modelo, año
- ✅ Asientos, pasajeros
- ✅ Tipo de combustible
- ✅ Color
- ❌ **FALTA: Tipo de Bus** (TURISMO 01, EJECUTIVO, etc.)

**Consecuencia:**  
Cuando registras un bus, el sistema **NO sabe si es de 1 piso o 2 pisos** porque no se está guardando el `tipo_bus_id`.

---

## 🎯 **CÓMO FUNCIONA LA LÓGICA ACTUAL**

### **Flujo Correcto (Como DEBERÍA ser):**

```
1. TIPOS DE BUSES (Catálogo)
   ├─ TURISMO 01 (59 asientos, 2 pisos) → ID: 2
   ├─ EJECUTIVO (40 asientos, 1 piso) → ID: 1
   └─ MINIBUS (20 asientos, 1 piso) → ID: 3

2. REGISTRAR BUS (Unidad física)
   ├─ Placa: TUR-001
   ├─ Marca: Mercedes Benz
   ├─ Modelo: O500
   └─ tipo_bus_id: 2 ← ⭐ ESTO ES LO QUE FALTA

3. SISTEMA IDENTIFICA
   ├─ Bus TUR-001 es tipo_bus_id = 2
   ├─ Busca en tipos_buses: ID 2 = TURISMO 01 (2 pisos)
   └─ Muestra el bus cuando seleccionas "TURISMO 01"
```

### **Flujo Actual (INCORRECTO):**

```
1. REGISTRAR BUS
   ├─ Placa: TUR-001
   ├─ Marca: Mercedes Benz
   ├─ Modelo: O500
   └─ tipo_bus_id: NULL ← ❌ NO SE GUARDA

2. SISTEMA NO PUEDE IDENTIFICAR
   ├─ Bus TUR-001 no tiene tipo_bus_id
   ├─ No aparece en ningún filtro por tipo
   └─ Mensaje: "No hay buses disponibles de este tipo"
```

---

## ✅ **SOLUCIÓN: AGREGAR CAMPO DE TIPO DE BUS**

### **Ubicación del Formulario:**
- **Archivo:** `app/views/admin/registrar_buses.php`
- **Línea:** Aproximadamente 343-371 (Sección "Capacidad y Tipo de Servicio")

### **Campo que Falta Agregar:**

```html
<!-- AGREGAR ESTE CAMPO EN EL PASO 3 DEL WIZARD -->
<div class="col-md-4">
    <div class="form-floating">
        <select class="form-select" id="tipoBusSelect" name="tipo_bus_id" required>
            <option value="">Seleccione...</option>
            <?php if (!empty($data['tipos_buses'])): ?>
                <?php foreach ($data['tipos_buses'] as $tipo): ?>
                    <option value="<?php echo $tipo->id; ?>">
                        <?php echo $tipo->nombre; ?> 
                        (<?php echo $tipo->capacidad; ?> as., <?php echo $tipo->pisos; ?> piso<?php echo $tipo->pisos > 1 ? 's' : ''; ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <label for="tipoBusSelect">Tipo de Bus ⭐</label>
    </div>
</div>
```

---

## 📍 **DÓNDE AGREGAR EL CAMPO**

### **Paso 1: Ubicar la Sección**

Busca en `registrar_buses.php` la línea que dice:

```html
<h6 class="fw-bold text-secondary text-uppercase small mb-3">
    <i class="bi bi-people-fill me-2"></i>Capacidad y Tipo de Servicio
</h6>
```

### **Paso 2: Modificar la Estructura**

**ANTES (3 columnas):**
```html
<div class="row g-3">
    <div class="col-md-4"><!-- Total Asientos --></div>
    <div class="col-md-4"><!-- Máx. Pasajeros --></div>
    <div class="col-md-4"><!-- Clase de Servicio --></div>
</div>
```

**DESPUÉS (4 columnas):**
```html
<div class="row g-3">
    <div class="col-md-3"><!-- Total Asientos --></div>
    <div class="col-md-3"><!-- Máx. Pasajeros --></div>
    <div class="col-md-3"><!-- ⭐ TIPO DE BUS (NUEVO) --></div>
    <div class="col-md-3"><!-- Clase de Servicio --></div>
</div>
```

---

## 🔧 **MODIFICACIÓN COMPLETA DEL CÓDIGO**

### **Archivo:** `app/views/admin/registrar_buses.php`
### **Líneas:** 343-371

**Reemplaza esto:**

```html
<div class="bg-light p-3 rounded-3 mb-4">
    <h6 class="fw-bold text-secondary text-uppercase small mb-3">
        <i class="bi bi-people-fill me-2"></i>Capacidad y Tipo de Servicio
    </h6>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="form-floating">
                <input type="number" class="form-control" id="asientosInput" name="asientos" placeholder="40" required>
                <label for="asientosInput">Total Asientos</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <input type="number" class="form-control" id="pasajerosInput" name="pasajeros" placeholder="40">
                <label for="pasajerosInput">Máx. Pasajeros</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <select class="form-select" id="servicioInput" name="tipo_servicio">
                    <option value="Normal">Normal</option>
                    <option value="Semicama">Semicama</option>
                    <option value="Leito">Leito / Cama</option>
                    <option value="Suite">Suite</option>
                </select>
                <label for="servicioInput">Clase de Servicio</label>
            </div>
        </div>
    </div>
</div>
```

**Por esto:**

```html
<div class="bg-light p-3 rounded-3 mb-4">
    <h6 class="fw-bold text-secondary text-uppercase small mb-3">
        <i class="bi bi-people-fill me-2"></i>Capacidad y Tipo de Servicio
    </h6>
    <div class="row g-3">
        <!-- ⭐ NUEVO: Tipo de Bus (PRIMERO) -->
        <div class="col-md-6">
            <div class="form-floating">
                <select class="form-select" id="tipoBusSelect" name="tipo_bus_id" required>
                    <option value="">Seleccione el tipo de bus...</option>
                    <?php if (!empty($data['tipos_buses'])): ?>
                        <?php foreach ($data['tipos_buses'] as $tipo): ?>
                            <option value="<?php echo $tipo->id; ?>" 
                                    data-capacidad="<?php echo $tipo->capacidad; ?>"
                                    data-pisos="<?php echo $tipo->pisos; ?>">
                                <?php echo $tipo->nombre; ?> 
                                (<?php echo $tipo->capacidad; ?> asientos, 
                                 <?php echo $tipo->pisos; ?> piso<?php echo $tipo->pisos > 1 ? 's' : ''; ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>No hay tipos de buses registrados</option>
                    <?php endif; ?>
                </select>
                <label for="tipoBusSelect">
                    <i class="bi bi-bus-front me-1"></i>Tipo de Bus 
                    <span class="text-danger">*</span>
                </label>
            </div>
            <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Selecciona si es bus de 1 piso, 2 pisos, etc.
            </small>
        </div>
        
        <!-- Clase de Servicio -->
        <div class="col-md-6">
            <div class="form-floating">
                <select class="form-select" id="servicioInput" name="tipo_servicio">
                    <option value="Normal">Normal</option>
                    <option value="Semicama">Semicama</option>
                    <option value="Leito">Leito / Cama</option>
                    <option value="Suite">Suite</option>
                </select>
                <label for="servicioInput">Clase de Servicio</label>
            </div>
        </div>
        
        <!-- Total Asientos (Auto-llenado opcional) -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="number" class="form-control" id="asientosInput" name="asientos" placeholder="40" required>
                <label for="asientosInput">Total Asientos</label>
            </div>
            <small class="text-muted">
                <i class="bi bi-lightbulb me-1"></i>
                Se auto-completa según el tipo de bus
            </small>
        </div>
        
        <!-- Máx. Pasajeros -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="number" class="form-control" id="pasajerosInput" name="pasajeros" placeholder="40">
                <label for="pasajerosInput">Máx. Pasajeros</label>
            </div>
        </div>
    </div>
</div>
```

---

## 🎨 **MEJORA OPCIONAL: Auto-Completar Asientos**

Agrega este JavaScript al final del archivo para auto-completar los asientos según el tipo de bus:

```javascript
<script>
// Auto-completar asientos según el tipo de bus seleccionado
document.getElementById('tipoBusSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const capacidad = selectedOption.getAttribute('data-capacidad');
    const pisos = selectedOption.getAttribute('data-pisos');
    
    if (capacidad) {
        // Auto-llenar el campo de asientos
        document.getElementById('asientosInput').value = capacidad;
        document.getElementById('pasajerosInput').value = capacidad;
        
        // Feedback visual
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
        Toast.fire({
            icon: 'info',
            title: `Bus de ${pisos} piso${pisos > 1 ? 's' : ''} - ${capacidad} asientos`
        });
    }
});
</script>
```

---

## 🔄 **MODIFICAR EL CONTROLADOR**

### **Archivo:** `app/controllers/Vehiculos.php` (o similar)

Asegúrate de que el controlador pase los tipos de buses a la vista:

```php
public function index()
{
    // Cargar modelo de tipos de buses
    $tipoBusModel = $this->model('TipoBusModel');
    
    $data = [
        'vehiculos' => $this->vehiculoModel->obtenerTodos(),
        'tipos_buses' => $tipoBusModel->obtenerTodos(), // ⭐ AGREGAR ESTO
        'title' => 'Gestión de Flota'
    ];
    
    $this->view('admin/registrar_buses', $data);
}
```

### **Archivo:** `app/controllers/Vehiculos.php` → Método `guardar()`

Asegúrate de que se guarde el `tipo_bus_id`:

```php
public function guardar()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = [
            'placa' => $_POST['placa'],
            'marca' => $_POST['marca'],
            'modelo' => $_POST['modelo'],
            'tipo_bus_id' => $_POST['tipo_bus_id'], // ⭐ AGREGAR ESTO
            'asientos' => $_POST['asientos'],
            'tipo_servicio' => $_POST['tipo_servicio'],
            // ... resto de campos
        ];
        
        if ($this->vehiculoModel->registrar($data)) {
            redirect('/vehiculos?msg=exito');
        } else {
            redirect('/vehiculos?msg=error');
        }
    }
}
```

---

## 📊 **RESUMEN VISUAL**

### **ANTES (Formulario Incompleto):**
```
┌─────────────────────────────────────────┐
│ Registrar Nuevo Bus                     │
├─────────────────────────────────────────┤
│ Placa: TUR-001                          │
│ Marca: Mercedes Benz                    │
│ Modelo: O500                            │
│ Asientos: 59                            │
│ Servicio: Leito                         │
│ ❌ tipo_bus_id: NULL                    │
└─────────────────────────────────────────┘
        ↓
Bus registrado pero SIN TIPO
No aparece en filtros
```

### **DESPUÉS (Formulario Completo):**
```
┌─────────────────────────────────────────┐
│ Registrar Nuevo Bus                     │
├─────────────────────────────────────────┤
│ ⭐ Tipo de Bus: TURISMO 01 (59 as., 2p) │
│ Placa: TUR-001                          │
│ Marca: Mercedes Benz                    │
│ Modelo: O500                            │
│ Asientos: 59 (auto-completado)         │
│ Servicio: Leito                         │
│ ✅ tipo_bus_id: 2                       │
└─────────────────────────────────────────┘
        ↓
Bus registrado CORRECTAMENTE
Aparece cuando seleccionas "TURISMO 01"
```

---

## 🎯 **PASOS PARA IMPLEMENTAR**

### **Paso 1: Modificar la Vista**
1. Abre: `app/views/admin/registrar_buses.php`
2. Busca la línea 343 (sección "Capacidad y Tipo de Servicio")
3. Reemplaza el código según lo indicado arriba

### **Paso 2: Modificar el Controlador**
1. Abre: `app/controllers/Vehiculos.php`
2. Agrega `'tipos_buses' => $tipoBusModel->obtenerTodos()` en el método `index()`
3. Agrega `'tipo_bus_id' => $_POST['tipo_bus_id']` en el método `guardar()`

### **Paso 3: Verificar el Modelo**
1. Abre: `app/models/TipoBusModel.php`
2. Verifica que exista el método `obtenerTodos()`

### **Paso 4: Probar**
1. Ve a: Gestión de Flota → Registrar Nuevo Bus
2. Verifica que aparezca el campo "Tipo de Bus"
3. Selecciona "TURISMO 01 (59 asientos, 2 pisos)"
4. Completa el resto del formulario
5. Guarda

---

## ✅ **VERIFICACIÓN POST-IMPLEMENTACIÓN**

Después de implementar, ejecuta esta consulta SQL:

```sql
SELECT 
    b.id,
    b.placa,
    b.tipo_bus_id,
    tb.nombre as tipo_bus,
    tb.pisos,
    CASE 
        WHEN b.tipo_bus_id IS NULL THEN '❌ SIN TIPO'
        ELSE '✅ CON TIPO'
    END as estado
FROM buses b
LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
ORDER BY b.id DESC
LIMIT 5;
```

**Resultado esperado:**
```
┌────┬─────────┬──────────────┬────────────┬───────┬───────────┐
│ id │  placa  │ tipo_bus_id  │  tipo_bus  │ pisos │  estado   │
├────┼─────────┼──────────────┼────────────┼───────┼───────────┤
│ 5  │ TUR-001 │      2       │ TURISMO 01 │   2   │ ✅ CON TIPO│
└────┴─────────┴──────────────┴────────────┴───────┴───────────┘
```

---

## 🎉 **RESULTADO FINAL**

Una vez implementado:

1. ✅ El formulario tendrá un campo para seleccionar el tipo de bus
2. ✅ Los buses se registrarán con su `tipo_bus_id` correcto
3. ✅ Los buses de dos pisos aparecerán cuando selecciones "TURISMO 01"
4. ✅ El sistema funcionará correctamente

---

**Última actualización:** 25/12/2025 15:56 hrs  
**Archivos a modificar:**
- `app/views/admin/registrar_buses.php` (línea 343-371)
- `app/controllers/Vehiculos.php` (métodos `index()` y `guardar()`)
