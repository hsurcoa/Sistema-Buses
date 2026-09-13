# 🎨 IMPLEMENTACIÓN SWEETALERT2 - ASIGNAR BUSES

## 📋 Resumen de la Implementación

Se ha implementado exitosamente **SweetAlert2** en el módulo de **Asignar Buses** (`/admin/asignar_buses`), eliminando los `alert()` nativos y las recargas de página. El sistema ahora utiliza **AJAX (Fetch API)** para enviar datos y mostrar confirmaciones visuales modernas.

---

## 🎯 Características Implementadas

### ✅ Frontend (JavaScript)
- **Interceptación del formulario**: Previene la recarga con `e.preventDefault()`
- **Envío AJAX**: Usa Fetch API para comunicación asíncrona
- **Indicador de carga**: Spinner en el botón "Guardar" mientras se procesa
- **Validación de respuesta**: Verifica que el servidor devuelva JSON válido
- **Manejo de errores**: Captura errores de red y parsing
- **Bonus**: También se mejoró la función `eliminarAsignacion()` con SweetAlert2

### ✅ Backend (PHP)
- **Respuestas JSON**: El controlador devuelve `json_encode()` en lugar de `<script>alert()</script>`
- **Headers correctos**: `Content-Type: application/json`
- **Buffer limpio**: `ob_clean()` para evitar corrupción del JSON
- **Manejo de excepciones**: Try-catch para errores específicos

### ✅ Visual (SweetAlert2)
- **Icono animado**: Check verde grande con animación
- **Título claro**: "¡Asignación Exitosa!"
- **Mensaje descriptivo**: "El bus ha sido asignado a la ruta correctamente."
- **Botón personalizado**: Color azul profesional (#3085d6) con texto "Aceptar"
- **Redirección automática**: Al hacer clic en "Aceptar" vuelve a `/admin/asignar_buses`

---

## 📁 Archivos Modificados

### 1. **Backend - Controlador** (`app/controllers/Admin.php`)

#### ANTES (Método `guardar_asignacion()`):
```php
public function guardar_asignacion()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $chofer_id = $_POST['chofer_id'] ?? '';
        $bus_id = $_POST['bus_id'] ?? '';
        $copiloto_id = $_POST['copiloto_id'] ?? '';

        if (empty($chofer_id) || empty($bus_id) || empty($copiloto_id)) {
            echo "<script>alert('Todos los campos son obligatorios'); window.location.href='" . URLROOT . "/admin/asignar_buses';</script>";
            return;
        }

        if ($this->asignacionModel->crearAsignacion($chofer_id, $bus_id, $copiloto_id)) {
            echo "<script>alert('Asignación guardada correctamente'); window.location.href='" . URLROOT . "/admin/asignar_buses';</script>";
        } else {
            echo "<script>alert('Error al guardar asignación'); window.location.href='" . URLROOT . "/admin/asignar_buses';</script>";
        }
    } else {
        header('Location: ' . URLROOT . '/admin/asignar_buses');
    }
}
```

#### DESPUÉS (Método `guardar_asignacion()` con JSON):
```php
public function guardar_asignacion()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        // Limpiar cualquier salida previa para evitar corrupción del JSON
        ob_clean();
        
        // Establecer headers para respuesta JSON
        header('Content-Type: application/json; charset=utf-8');
        
        // Recoger datos del formulario
        $chofer_id = $_POST['chofer_id'] ?? '';
        $bus_id = $_POST['bus_id'] ?? '';
        $copiloto_id = $_POST['copiloto_id'] ?? '';

        // Validación básica
        if (empty($chofer_id) || empty($bus_id) || empty($copiloto_id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Todos los campos son obligatorios. Por favor, complete el formulario.'
            ]);
            exit;
        }

        // Intentar guardar la asignación
        try {
            if ($this->asignacionModel->crearAsignacion($chofer_id, $bus_id, $copiloto_id)) {
                // ✅ ÉXITO
                echo json_encode([
                    'status' => 'success',
                    'message' => 'El bus ha sido asignado a la ruta correctamente.'
                ]);
            } else {
                // ❌ ERROR GENÉRICO
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al guardar la asignación. Por favor, intente nuevamente.'
                ]);
            }
        } catch (Exception $e) {
            // ⚠️ ERROR ESPECÍFICO
            echo json_encode([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    } else {
        header('Location: ' . URLROOT . '/admin/asignar_buses');
    }
}
```

#### 🔑 Cambios Clave en el Backend:

1. **`ob_clean()`**: Limpia el buffer de salida antes de enviar JSON
2. **`header('Content-Type: application/json')`**: Indica que la respuesta es JSON
3. **`json_encode()`**: Convierte arrays PHP a JSON
4. **`exit;`**: Detiene la ejecución después de enviar la respuesta
5. **Estructura de respuesta**:
   ```json
   {
     "status": "success" | "error",
     "message": "Mensaje descriptivo"
   }
   ```

---

### 2. **Frontend - Vista** (`app/views/admin/asignar_buses.php`)

#### Cambio 1: Agregar ID al formulario (línea 20)

**ANTES:**
```html
<form action="<?php echo URLROOT; ?>/admin/guardar_asignacion" method="POST">
```

**DESPUÉS:**
```html
<form id="formAsignacion" action="<?php echo URLROOT; ?>/admin/guardar_asignacion" method="POST">
```

#### Cambio 2: Script completo con AJAX y SweetAlert2 (líneas 166-272)

**ANTES:**
```javascript
<script>
    function eliminarAsignacion(id) {
        if (confirm('¿Desea eliminar este registro? Esta acción se hará definitivamente.')) {
            window.location.href = '<?php echo URLROOT; ?>/admin/eliminar_asignacion/' + id;
        }
    }
</script>
```

**DESPUÉS:**
```javascript
<script>
    // ==========================================
    // 🚀 IMPLEMENTACIÓN AJAX CON SWEETALERT2
    // ==========================================
    
    const formAsignacion = document.getElementById('formAsignacion');
    
    formAsignacion.addEventListener('submit', function(e) {
        // 1. Prevenir la recarga de la página
        e.preventDefault();
        
        // 2. Obtener el botón de submit y deshabilitarlo
        const btnSubmit = this.querySelector('[type="submit"]');
        const textoOriginal = btnSubmit.innerHTML;
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        
        // 3. Recopilar los datos del formulario
        const formData = new FormData(this);
        
        // 4. Enviar la petición AJAX usando Fetch API
        fetch('<?php echo URLROOT; ?>/admin/guardar_asignacion', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Verificar que la respuesta sea JSON válido
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('La respuesta del servidor no es JSON válido');
            }
            return response.json();
        })
        .then(data => {
            // 5. Procesar la respuesta del servidor
            if (data.status === 'success') {
                // ✅ ÉXITO - Mostrar SweetAlert2 con diseño moderno
                Swal.fire({
                    icon: 'success',
                    title: '¡Asignación Exitosa!',
                    text: data.message,
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Aceptar',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'animated fadeInDown'
                    }
                }).then((result) => {
                    // 6. Redirigir al hacer clic en "Aceptar"
                    if (result.isConfirmed) {
                        window.location.href = '<?php echo URLROOT; ?>/admin/asignar_buses';
                    }
                });
            } else {
                // ❌ ERROR - Mostrar mensaje de error
                Swal.fire({
                    icon: 'error',
                    title: 'Error al Asignar',
                    text: data.message || 'Ocurrió un error inesperado. Por favor, intente nuevamente.',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Entendido'
                });
                
                // Restaurar el botón
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = textoOriginal;
            }
        })
        .catch(error => {
            // ⚠️ ERROR DE RED O PARSING
            console.error('Error:', error);
            
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudo conectar con el servidor. Verifique su conexión a internet.',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Entendido'
            });
            
            // Restaurar el botón
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = textoOriginal;
        });
    });
    
    // ==========================================
    // 🗑️ FUNCIÓN ELIMINAR CON SWEETALERT2
    // ==========================================
    
    function eliminarAsignacion(id) {
        Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción eliminará la asignación definitivamente",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?php echo URLROOT; ?>/admin/eliminar_asignacion/' + id;
            }
        });
    }
</script>
```

---

## 🔄 Flujo Completo de la Operación

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Usuario selecciona Chofer, Bus y Copiloto               │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Hace clic en "Guardar"                                  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. JavaScript intercepta el submit (e.preventDefault())    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. Botón cambia a "Guardando..." con spinner               │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. Fetch API envía FormData a /admin/guardar_asignacion    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 6. PHP procesa datos y devuelve JSON                       │
│    • ob_clean() limpia buffer                              │
│    • header() establece Content-Type                       │
│    • json_encode() crea respuesta                          │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 7. JavaScript recibe y parsea JSON                         │
└────────────────────┬────────────────────────────────────────┘
                     │
         ┌───────────┴───────────┐
         │                       │
         ▼                       ▼
┌─────────────────┐    ┌─────────────────┐
│ status=success  │    │ status=error    │
└────────┬────────┘    └────────┬────────┘
         │                      │
         ▼                      ▼
┌─────────────────┐    ┌─────────────────┐
│ SweetAlert2     │    │ SweetAlert2     │
│ Icono: ✓ Verde  │    │ Icono: ✗ Rojo   │
│ Título: ¡Éxito! │    │ Título: Error   │
│ Botón: Aceptar  │    │ Botón: Entendido│
└────────┬────────┘    └────────┬────────┘
         │                      │
         ▼                      ▼
┌─────────────────┐    ┌─────────────────┐
│ Usuario clic    │    │ Restaurar botón │
│ "Aceptar"       │    │ (puede reintentar)│
└────────┬────────┘    └─────────────────┘
         │
         ▼
┌─────────────────┐
│ Redirige a      │
│ /asignar_buses  │
│ (lista actualiz)│
└─────────────────┘
```

---

## 🧪 Casos de Prueba

### ✅ Caso 1: Asignación Exitosa
**Pasos:**
1. Abrir `/admin/asignar_buses`
2. Seleccionar un **Chofer** del dropdown
3. Seleccionar un **Bus** del dropdown
4. Seleccionar un **Copiloto** del dropdown
5. Hacer clic en "Guardar"

**Resultado Esperado:**
- Botón muestra "Guardando..." con spinner
- Aparece SweetAlert2 con:
  - Icono: Check verde animado
  - Título: "¡Asignación Exitosa!"
  - Mensaje: "El bus ha sido asignado a la ruta correctamente."
  - Botón azul: "Aceptar"
- Al hacer clic en "Aceptar" → Redirige a `/admin/asignar_buses`
- La nueva asignación aparece en la tabla

---

### ❌ Caso 2: Campos Vacíos
**Pasos:**
1. Dejar uno o más campos sin seleccionar
2. Hacer clic en "Guardar"

**Resultado Esperado:**
- Validación HTML5 impide el envío (atributo `required`)
- Si se desactiva validación HTML5:
  - SweetAlert2 con mensaje de error
  - "Todos los campos son obligatorios. Por favor, complete el formulario."

---

### ⚠️ Caso 3: Error del Servidor
**Pasos:**
1. Simular un error en el modelo (ej: base de datos caída)
2. Intentar guardar

**Resultado Esperado:**
- SweetAlert2 con:
  - Icono: X rojo
  - Título: "Error al Asignar"
  - Mensaje: Descripción del error
  - Botón rojo: "Entendido"
- El botón vuelve a estar habilitado

---

### 🗑️ Caso 4: Eliminar Asignación (BONUS)
**Pasos:**
1. Hacer clic en el icono de basura (🗑️) de una asignación
2. Observar el modal de confirmación

**Resultado Esperado:**
- SweetAlert2 con:
  - Icono: ⚠️ Advertencia amarilla
  - Título: "¿Está seguro?"
  - Mensaje: "Esta acción eliminará la asignación definitivamente"
  - Botones: "Sí, eliminar" (rojo) y "Cancelar" (gris)
- Si hace clic en "Sí, eliminar" → Elimina el registro
- Si hace clic en "Cancelar" → Cierra el modal sin hacer nada

---

## 🎨 Configuración de SweetAlert2

### Modal de Éxito:
```javascript
Swal.fire({
    icon: 'success',              // Icono verde con check
    title: '¡Asignación Exitosa!', // Título principal
    text: 'El bus ha sido asignado a la ruta correctamente.', // Descripción
    confirmButtonColor: '#3085d6', // Color azul profesional
    confirmButtonText: 'Aceptar',  // Texto del botón
    allowOutsideClick: false,      // No cerrar al hacer clic fuera
    customClass: {
        popup: 'animated fadeInDown' // Animación de entrada
    }
})
```

### Modal de Error:
```javascript
Swal.fire({
    icon: 'error',                 // Icono rojo con X
    title: 'Error al Asignar',     // Título
    text: data.message,            // Mensaje del servidor
    confirmButtonColor: '#d33',    // Color rojo
    confirmButtonText: 'Entendido' // Texto del botón
})
```

### Modal de Confirmación (Eliminar):
```javascript
Swal.fire({
    title: '¿Está seguro?',
    text: "Esta acción eliminará la asignación definitivamente",
    icon: 'warning',               // Icono amarillo de advertencia
    showCancelButton: true,        // Mostrar botón cancelar
    confirmButtonColor: '#d33',    // Botón confirmar rojo
    cancelButtonColor: '#6c757d',  // Botón cancelar gris
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
})
```

---

## 📝 Código Listo para Copiar

### 📄 Backend PHP (Controlador)

Copia este código en tu método `guardar_asignacion()` del controlador `Admin.php`:

```php
public function guardar_asignacion()
{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        // Limpiar buffer y establecer headers JSON
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        // Recoger datos
        $chofer_id = $_POST['chofer_id'] ?? '';
        $bus_id = $_POST['bus_id'] ?? '';
        $copiloto_id = $_POST['copiloto_id'] ?? '';

        // Validación
        if (empty($chofer_id) || empty($bus_id) || empty($copiloto_id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Todos los campos son obligatorios. Por favor, complete el formulario.'
            ]);
            exit;
        }

        // Guardar
        try {
            if ($this->asignacionModel->crearAsignacion($chofer_id, $bus_id, $copiloto_id)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'El bus ha sido asignado a la ruta correctamente.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al guardar la asignación. Por favor, intente nuevamente.'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    } else {
        header('Location: ' . URLROOT . '/admin/asignar_buses');
    }
}
```

### 📄 Frontend JavaScript (Vista)

Copia este código en tu vista `asignar_buses.php` (reemplaza el `<script>` existente):

```javascript
<script>
    const formAsignacion = document.getElementById('formAsignacion');
    
    formAsignacion.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btnSubmit = this.querySelector('[type="submit"]');
        const textoOriginal = btnSubmit.innerHTML;
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        
        const formData = new FormData(this);
        
        fetch('<?php echo URLROOT; ?>/admin/guardar_asignacion', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('Respuesta inválida del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Asignación Exitosa!',
                    text: data.message,
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Aceptar',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = '<?php echo URLROOT; ?>/admin/asignar_buses';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al Asignar',
                    text: data.message,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Entendido'
                });
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = textoOriginal;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudo conectar con el servidor.',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Entendido'
            });
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = textoOriginal;
        });
    });
    
    function eliminarAsignacion(id) {
        Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción eliminará la asignación definitivamente",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?php echo URLROOT; ?>/admin/eliminar_asignacion/' + id;
            }
        });
    }
</script>
```

**No olvides agregar el ID al formulario:**
```html
<form id="formAsignacion" action="<?php echo URLROOT; ?>/admin/guardar_asignacion" method="POST">
```

---

## ✅ Beneficios Obtenidos

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Experiencia de Usuario** | alert() nativo feo | Modal moderno animado |
| **Recarga de Página** | Sí (pantalla en blanco) | No (AJAX) |
| **Feedback Visual** | Texto simple | Icono + título + mensaje |
| **Confirmación de Eliminación** | confirm() nativo | Modal SweetAlert2 con botones |
| **Profesionalismo** | Bajo | Alto |

---

## 🎉 Resultado Final

### Antes:
```
Usuario → Submit → Recarga Página → alert() nativo → Pantalla en blanco
```

### Después:
```
Usuario → Submit → AJAX → SweetAlert2 animado → Redirección suave
```

**¡Implementación completada con éxito! 🚀**

Tu módulo de asignación de buses ahora tiene una experiencia de usuario moderna y profesional, idéntica a las aplicaciones web más avanzadas.

---

**Desarrollado con ❤️ para modernizar el sistema de venta de pasajes**
