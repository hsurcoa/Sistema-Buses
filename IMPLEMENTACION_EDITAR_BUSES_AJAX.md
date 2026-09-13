# 📝 IMPLEMENTACIÓN COMPLETA: EDITAR BUS CON MODAL Y AJAX

## 🎯 Objetivo Cumplido
Se ha implementado exitosamente la funcionalidad de **EDITAR** buses existentes usando un Modal de Bootstrap y AJAX, sin recargar la página.

---

## 📦 ENTREGABLES

### 1️⃣ **Código HTML del Botón en la Tabla**

El botón de edición en la tabla ahora incluye el atributo `data-id` con el ID del bus:

```html
<button class="btn btn-sm btn-light text-primary" 
        data-id="<?php echo $bus->id; ?>" 
        onclick="abrirModalEditar(this)" 
        title="Editar Bus">
    <i class="bi bi-pencil"></i>
</button>
```

**Ubicación:** `app/views/admin/registrar_buses.php` (línea ~135)

---

### 2️⃣ **Código HTML del Modal de Edición**

Modal completo con diseño moderno, organizado en secciones:

- **Identidad y Legalidad**: Propietario, Placa, Tarjeta de Circulación
- **Ficha Técnica**: Marca, Modelo, Año, Clase, Carrocería, Ejes, Ruedas
- **Detalles Mecánicos** (Accordion): Motor, Serie, Dimensiones, Pesos
- **Servicio y Características**: Asientos, Pasajeros, Tipo de Servicio, Combustible, Color

**Características del Modal:**
- ✅ Campo oculto para el ID del bus
- ✅ Todos los campos del formulario con IDs únicos (prefijo `edit`)
- ✅ Diseño responsive (modal-xl)
- ✅ Scrollable para contenido largo
- ✅ Backdrop estático (no se cierra al hacer clic fuera)
- ✅ Estilos consistentes con el modal de registro

**Ubicación:** `app/views/admin/registrar_buses.php` (después del modal wizard)

---

### 3️⃣ **Código JavaScript Completo**

#### **Función `abrirModalEditar(button)`**

Esta función se ejecuta al hacer clic en el botón de editar:

1. **Obtiene el ID** del bus desde el atributo `data-id`
2. **Muestra un loading** con SweetAlert2
3. **Hace una petición AJAX** a `/vehiculos/obtener_bus` enviando el ID
4. **Procesa la respuesta JSON** y rellena todos los campos del formulario
5. **Maneja los selectores especiales**:
   - Radio buttons de combustible (Diesel, Gasolina, GNV)
   - Radio buttons de color (Blanco, Rojo, Azul, Negro, Plata)
   - Select de Clase y Tipo de Servicio
6. **Abre el modal** de edición

#### **Event Listener del Botón "Guardar Cambios"**

1. **Valida el formulario** usando HTML5 validation
2. **Deshabilita el botón** y muestra spinner
3. **Envía los datos** via AJAX a `/vehiculos/editar_bus`
4. **Procesa la respuesta**:
   - ✅ **Éxito**: Cierra el modal, muestra SweetAlert2 de éxito, recarga la página
   - ❌ **Error**: Muestra SweetAlert2 de error, restaura el botón

**Ubicación:** `app/views/admin/registrar_buses.php` (dentro del `<script>`)

---

### 4️⃣ **Código PHP: `obtener_bus()` (Lectura)**

**Archivo:** `app/controllers/Vehiculos.php`

**Método:** `public function obtener_bus()`

**Funcionalidad:**
- Recibe el ID del bus por POST
- Valida que se haya enviado el ID
- Consulta la base de datos usando `obtenerBusPorId($id)`
- Devuelve los datos en formato JSON:

```json
{
  "status": "success",
  "bus": {
    "id": 1,
    "placa": "ABC-1234",
    "propietario_nombres": "Juan",
    "propietario_apellidos": "Pérez",
    ...
  }
}
```

**Manejo de Errores:**
- ID no proporcionado
- Bus no encontrado

---

### 5️⃣ **Código PHP: `editar_bus()` (Escritura/Update)**

**Archivo:** `app/controllers/Vehiculos.php`

**Método:** `public function editar_bus()`

**Funcionalidad:**
- Recibe todos los datos del formulario por POST
- Valida campos obligatorios (ID, Placa, Tarjeta de Circulación)
- Llama al modelo `actualizarBus($datos)`
- Devuelve respuesta JSON:

```json
{
  "status": "success",
  "message": "El bus ha sido actualizado correctamente.",
  "placa": "ABC-1234"
}
```

**Manejo de Errores:**
- Campos obligatorios vacíos
- Error de duplicado de placa
- Error genérico de base de datos

---

### 6️⃣ **Métodos en el Modelo**

**Archivo:** `app/models/VehiculoModel.php`

#### **Método `obtenerBusPorId($id)`**

```php
public function obtenerBusPorId($id)
{
    $this->db->query('SELECT * FROM vehiculos WHERE id = :id');
    $this->db->bind(':id', $id);
    return $this->db->single();
}
```

**Retorna:** Objeto con los datos del bus o `false` si no existe.

#### **Método `actualizarBus($datos)`**

```php
public function actualizarBus($datos)
{
    $this->db->query('UPDATE vehiculos SET
        propietario_nombres = :propietario_nombres,
        propietario_apellidos = :propietario_apellidos,
        tarjeta_circulacion = :tarjeta_circulacion,
        placa = :placa,
        clase = :clase,
        marca = :marca,
        anio = :anio,
        modelo = :modelo,
        tipo_combustible = :tipo_combustible,
        carroceria = :carroceria,
        ejes = :ejes,
        color = :color,
        nro_motor = :nro_motor,
        cilindros = :cilindros,
        nro_serie = :nro_serie,
        ruedas = :ruedas,
        peso_seco = :peso_seco,
        peso_bruto = :peso_bruto,
        longitud = :longitud,
        altura = :altura,
        ancho = :ancho,
        pasajeros = :pasajeros,
        asientos = :asientos,
        tipo_servicio = :tipo_servicio
    WHERE id = :id');
    
    // Bind de todos los parámetros...
    
    return $this->db->execute();
}
```

**Retorna:** `true` si se actualizó correctamente, `false` en caso contrario.

---

## 🎨 Características de Diseño

### **Modal de Edición**
- ✅ Header azul con icono de lápiz
- ✅ Secciones organizadas con iconos
- ✅ Accordion para detalles opcionales
- ✅ Radio buttons visuales para combustible y color
- ✅ Input estilo placa boliviana (BOL + campo)
- ✅ Footer con botones de acción

### **SweetAlert2**
- ✅ Loading durante la carga de datos
- ✅ Alerta de éxito con icono `success`
- ✅ Título: "¡Actualizado!"
- ✅ Botón azul (#3085d6)
- ✅ Recarga automática al cerrar

---

## 🔄 Flujo de Funcionamiento

### **Editar un Bus**

1. Usuario hace clic en el botón de editar (lápiz) en la tabla
2. JavaScript captura el `data-id` del botón
3. Se muestra un loading de SweetAlert2
4. Se hace petición AJAX a `obtener_bus.php` con el ID
5. Backend consulta la base de datos y devuelve JSON
6. JavaScript rellena todos los campos del modal
7. Se abre el modal de edición

### **Guardar Cambios**

1. Usuario modifica los datos y hace clic en "Guardar Cambios"
2. JavaScript valida el formulario
3. Se deshabilita el botón y muestra spinner
4. Se envían los datos via AJAX a `editar_bus.php`
5. Backend valida y actualiza en la base de datos
6. Se devuelve respuesta JSON
7. JavaScript cierra el modal y muestra SweetAlert2 de éxito
8. La página se recarga automáticamente

---

## 💡 Consejo Extra Implementado

### **Manejo de Selectores (Dropdowns y Radio Buttons)**

El código JavaScript incluye lógica específica para seleccionar correctamente las opciones basadas en el ID/valor que viene de la base de datos:

#### **Select (Dropdown)**
```javascript
document.getElementById('editTipoServicio').value = data.bus.tipo_servicio || 'Normal';
```

#### **Radio Buttons (Combustible)**
```javascript
const combustible = data.bus.tipo_combustible || 'Diesel';
if (combustible === 'Diesel') {
    document.getElementById('editCombustibleDiesel').checked = true;
} else if (combustible === 'Gasolina') {
    document.getElementById('editCombustibleGasolina').checked = true;
} else if (combustible === 'GNV') {
    document.getElementById('editCombustibleGNV').checked = true;
}
```

#### **Radio Buttons (Color)**
```javascript
const color = data.bus.color || 'Blanco';
if (color === 'Blanco') {
    document.getElementById('editColorBlanco').checked = true;
} else if (color === 'Rojo') {
    document.getElementById('editColorRojo').checked = true;
}
// ... etc
```

---

## ✅ Checklist de Implementación

- [x] Botón de edición con `data-id` en la tabla
- [x] Modal de edición con todos los campos
- [x] Campo oculto para el ID del bus
- [x] JavaScript para abrir modal y cargar datos
- [x] JavaScript para guardar cambios
- [x] Método `obtener_bus()` en el controlador
- [x] Método `editar_bus()` en el controlador
- [x] Método `obtenerBusPorId()` en el modelo
- [x] Método `actualizarBus()` en el modelo
- [x] Manejo de selectores (dropdowns y radio buttons)
- [x] SweetAlert2 para feedback visual
- [x] Recarga de página al cerrar la alerta
- [x] Validación de formularios
- [x] Manejo de errores
- [x] Respuestas JSON limpias

---

## 🚀 Cómo Probar

1. Navega a la página de **Gestión de Flota**
2. Haz clic en el botón de **editar** (lápiz) de cualquier bus
3. Verás un loading mientras se cargan los datos
4. El modal se abrirá con todos los campos rellenados
5. Modifica cualquier campo (por ejemplo, cambia el color o el año)
6. Haz clic en **"Guardar Cambios"**
7. Verás un spinner en el botón mientras se procesa
8. Si todo es correcto, aparecerá un SweetAlert2 de éxito
9. Al hacer clic en "OK", la página se recargará mostrando los cambios

---

## 🎓 Arquitectura MVC Aplicada

### **Model (VehiculoModel.php)**
- `obtenerBusPorId($id)`: Consulta SELECT
- `actualizarBus($datos)`: Consulta UPDATE

### **View (registrar_buses.php)**
- Botón de editar con `data-id`
- Modal de edición HTML
- JavaScript para interacción

### **Controller (Vehiculos.php)**
- `obtener_bus()`: Endpoint AJAX para lectura
- `editar_bus()`: Endpoint AJAX para escritura

---

## 📌 Notas Importantes

1. **Sin Recarga de Página**: Todo el proceso de carga y guardado se hace via AJAX
2. **Validación en Ambos Lados**: HTML5 en frontend + PHP en backend
3. **Feedback Visual**: SweetAlert2 para todas las acciones
4. **Manejo de Errores**: Try-catch para errores de base de datos
5. **JSON Limpio**: `ob_clean()` antes de enviar respuestas JSON
6. **Seguridad**: Uso de prepared statements con PDO

---

## 🎉 Resultado Final

Ahora tienes un sistema completo de edición de buses que:
- ✅ Es rápido (sin recargas innecesarias)
- ✅ Es intuitivo (modal con diseño moderno)
- ✅ Es robusto (manejo de errores)
- ✅ Es profesional (SweetAlert2 para feedback)
- ✅ Sigue las mejores prácticas (MVC, AJAX, validación)

---

**Desarrollado por:** Desarrollador Full Stack Senior  
**Fecha:** 23 de Diciembre, 2025  
**Framework:** PHP MVC Custom + Bootstrap 5 + SweetAlert2
