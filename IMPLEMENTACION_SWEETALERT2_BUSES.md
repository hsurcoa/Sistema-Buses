# 🎨 IMPLEMENTACIÓN SWEETALERT2 - REGISTRO DE BUSES

## 📋 Descripción General

Se ha implementado exitosamente **SweetAlert2** en el módulo de registro de buses, eliminando los `alert()` nativos y las recargas de página. Ahora el sistema utiliza **AJAX (Fetch API)** para enviar datos y mostrar confirmaciones visuales modernas.

---

## 🎯 Características Implementadas

### ✅ Frontend (JavaScript)
- **Interceptación del formulario**: Previene la recarga con `e.preventDefault()`
- **Envío AJAX**: Usa Fetch API para comunicación asíncrona
- **Indicador de carga**: Spinner en el botón mientras se procesa
- **Validación de respuesta**: Verifica que el servidor devuelva JSON válido
- **Manejo de errores**: Captura errores de red y parsing

### ✅ Backend (PHP)
- **Respuestas JSON**: El controlador devuelve `json_encode()` en lugar de redirecciones
- **Headers correctos**: `Content-Type: application/json`
- **Buffer limpio**: `ob_clean()` para evitar corrupción del JSON
- **Detección de errores**: Identifica duplicados de placa automáticamente

### ✅ Visual (SweetAlert2)
- **Icono animado**: Check verde grande con animación
- **Título claro**: "¡Registro Exitoso!"
- **Mensaje descriptivo**: Confirma la acción realizada
- **Botón personalizado**: Color azul profesional (#3085d6)
- **Redirección automática**: Al hacer clic en "OK" vuelve a la lista

---

## 📁 Archivos Modificados

### 1. **Controlador Backend** (`app/controllers/Vehiculos.php`)

```php
<?php
class Vehiculos extends Controller
{
    private $vehiculoModel;

    public function __construct()
    {
        $this->vehiculoModel = $this->model('VehiculoModel');
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            // Limpiar cualquier salida previa para evitar corrupción del JSON
            ob_clean();
            
            // Establecer headers para respuesta JSON
            header('Content-Type: application/json; charset=utf-8');

            // 1. Recoger datos del formulario
            $datos = [
                'propietario_nombres' => trim($_POST['propietario_nombres'] ?? ''),
                'propietario_apellidos' => trim($_POST['propietario_apellidos'] ?? ''),
                'tarjeta_circulacion' => trim($_POST['tarjeta_circulacion'] ?? ''),
                'placa' => trim($_POST['placa'] ?? ''),
                'clase' => trim($_POST['clase'] ?? ''),
                'marca' => trim($_POST['marca'] ?? ''),
                'anio' => trim($_POST['anio'] ?? ''),
                'modelo' => trim($_POST['modelo'] ?? ''),
                'tipo_combustible' => trim($_POST['tipo_combustible'] ?? ''),
                'carroceria' => trim($_POST['carroceria'] ?? ''),
                'ejes' => trim($_POST['ejes'] ?? ''),
                'color' => trim($_POST['color'] ?? ''),
                'nro_motor' => trim($_POST['nro_motor'] ?? ''),
                'cilindros' => trim($_POST['cilindros'] ?? ''),
                'nro_serie' => trim($_POST['nro_serie'] ?? ''),
                'ruedas' => trim($_POST['ruedas'] ?? ''),
                'peso_seco' => trim($_POST['peso_seco'] ?? ''),
                'peso_bruto' => trim($_POST['peso_bruto'] ?? ''),
                'longitud' => trim($_POST['longitud'] ?? ''),
                'altura' => trim($_POST['altura'] ?? ''),
                'ancho' => trim($_POST['ancho'] ?? ''),
                'pasajeros' => trim($_POST['pasajeros'] ?? ''),
                'asientos' => trim($_POST['asientos'] ?? ''),
                'tipo_servicio' => trim($_POST['tipo_servicio'] ?? '')
            ];

            // 2. Validación Básica
            if (empty($datos['placa']) || empty($datos['tarjeta_circulacion'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Los campos Placa y Tarjeta de Circulación son obligatorios.'
                ]);
                exit;
            }

            // 3. Intentar guardar en la base de datos
            try {
                if ($this->vehiculoModel->registrarVehiculo($datos)) {
                    // ✅ ÉXITO
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'El bus ha sido registrado correctamente en el sistema.',
                        'placa' => $datos['placa']
                    ]);
                } else {
                    // ❌ FALLO GENÉRICO
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Ocurrió un error al registrar el bus. Por favor, intente nuevamente.'
                    ]);
                }
            } catch (Exception $e) {
                // ⚠️ ERROR DE DUPLICADO
                $errorMessage = 'Error al registrar el bus.';
                
                if (strpos($e->getMessage(), '23000') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
                    $errorMessage = 'La placa ingresada ya se encuentra registrada en el sistema.';
                }
                
                echo json_encode([
                    'status' => 'error',
                    'message' => $errorMessage
                ]);
            }
            exit;
        } else {
            header('Location: ' . URLROOT . '/admin/registrar_buses');
        }
    }
}
```

#### 🔑 Puntos Clave del Backend:

1. **`ob_clean()`**: Limpia cualquier salida previa (espacios, warnings) que corromperían el JSON
2. **`header('Content-Type: application/json')`**: Indica al navegador que la respuesta es JSON
3. **Estructura de respuesta**:
   ```json
   {
     "status": "success" | "error",
     "message": "Mensaje descriptivo",
     "placa": "ABC-1234" // Solo en éxito
   }
   ```
4. **Detección inteligente de duplicados**: Busca el código de error SQL `23000` o la palabra "Duplicate"

---

### 2. **Vista Frontend** (`app/views/admin/registrar_buses.php`)

Se agregó el siguiente script al final del archivo (después del código del wizard):

```javascript
<script>
    // ... código del wizard existente ...

    // ==========================================
    // 🚀 IMPLEMENTACIÓN AJAX CON SWEETALERT2
    // ==========================================
    
    const wizardForm = document.getElementById('wizardForm');
    
    wizardForm.addEventListener('submit', function(e) {
        // 1. Prevenir la recarga de la página
        e.preventDefault();
        
        // 2. Obtener el botón de submit y deshabilitarlo
        const btnFinish = document.getElementById('btnFinish');
        const btnOriginalText = btnFinish.innerHTML;
        btnFinish.disabled = true;
        btnFinish.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        
        // 3. Recopilar los datos del formulario
        const formData = new FormData(this);
        
        // 4. Enviar la petición AJAX usando Fetch API
        fetch('<?php echo URLROOT; ?>/vehiculos/guardar', {
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
                    title: '¡Registro Exitoso!',
                    text: data.message,
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'animated fadeInDown'
                    }
                }).then((result) => {
                    // 6. Redirigir a la lista de buses al hacer clic en OK
                    if (result.isConfirmed) {
                        window.location.href = '<?php echo URLROOT; ?>/admin/registrar_buses';
                    }
                });
            } else {
                // ❌ ERROR - Mostrar mensaje de error
                Swal.fire({
                    icon: 'error',
                    title: 'Error al Registrar',
                    text: data.message || 'Ocurrió un error inesperado. Por favor, intente nuevamente.',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Entendido'
                });
                
                // Restaurar el botón
                btnFinish.disabled = false;
                btnFinish.innerHTML = btnOriginalText;
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
            btnFinish.disabled = false;
            btnFinish.innerHTML = btnOriginalText;
        });
    });
</script>
```

#### 🔑 Puntos Clave del Frontend:

1. **`e.preventDefault()`**: Evita que el formulario se envíe de forma tradicional (sin recarga)
2. **Feedback visual**: Cambia el texto del botón a "Guardando..." con spinner
3. **Fetch API**: Método moderno para peticiones AJAX (reemplaza XMLHttpRequest)
4. **Validación de Content-Type**: Asegura que el servidor devolvió JSON
5. **Promesas encadenadas**: `.then()` para manejar respuesta exitosa, `.catch()` para errores
6. **Restauración del botón**: Si hay error, vuelve a habilitar el botón

---

## 🎨 Configuración de SweetAlert2

### Opciones Utilizadas:

```javascript
Swal.fire({
    icon: 'success',              // Tipo: success, error, warning, info, question
    title: '¡Registro Exitoso!',  // Título principal (grande y destacado)
    text: 'Mensaje descriptivo',  // Subtítulo/descripción
    confirmButtonColor: '#3085d6', // Color del botón (azul profesional)
    confirmButtonText: 'OK',       // Texto del botón
    allowOutsideClick: false,      // No cerrar al hacer clic fuera
    customClass: {
        popup: 'animated fadeInDown' // Animación de entrada
    }
})
```

### Variaciones de Iconos:

| Icono | Uso | Color |
|-------|-----|-------|
| `success` | Operación exitosa | Verde |
| `error` | Error o fallo | Rojo |
| `warning` | Advertencia | Amarillo |
| `info` | Información | Azul |
| `question` | Pregunta/Confirmación | Gris |

---

## 🔄 Flujo Completo de la Operación

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Usuario completa el formulario wizard (3 pasos)         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Hace clic en "Finalizar Registro"                       │
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
│ 5. Fetch API envía FormData a /vehiculos/guardar           │
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
│ Título: Éxito   │    │ Título: Error   │
└────────┬────────┘    └────────┬────────┘
         │                      │
         ▼                      ▼
┌─────────────────┐    ┌─────────────────┐
│ Usuario clic OK │    │ Restaurar botón │
└────────┬────────┘    └─────────────────┘
         │
         ▼
┌─────────────────┐
│ Redirige a      │
│ /registrar_buses│
└─────────────────┘
```

---

## 🧪 Casos de Prueba

### ✅ Caso 1: Registro Exitoso
**Pasos:**
1. Abrir `/admin/registrar_buses`
2. Hacer clic en "Registrar Nuevo Bus"
3. Completar todos los campos requeridos
4. Hacer clic en "Finalizar Registro"

**Resultado Esperado:**
- Botón muestra "Guardando..." con spinner
- Aparece SweetAlert2 con:
  - Icono: Check verde animado
  - Título: "¡Registro Exitoso!"
  - Mensaje: "El bus ha sido registrado correctamente en el sistema."
  - Botón azul: "OK"
- Al hacer clic en OK → Redirige a la lista de buses
- El nuevo bus aparece en la tabla

---

### ❌ Caso 2: Placa Duplicada
**Pasos:**
1. Intentar registrar un bus con una placa ya existente

**Resultado Esperado:**
- SweetAlert2 con:
  - Icono: X rojo
  - Título: "Error al Registrar"
  - Mensaje: "La placa ingresada ya se encuentra registrada en el sistema."
  - Botón rojo: "Entendido"
- El botón vuelve a estar habilitado
- El modal permanece abierto para corregir

---

### ⚠️ Caso 3: Campos Vacíos
**Pasos:**
1. Dejar vacíos los campos "Placa" o "Tarjeta de Circulación"
2. Intentar enviar

**Resultado Esperado:**
- Validación HTML5 impide el envío (antes de AJAX)
- Si se desactiva validación HTML5:
  - SweetAlert2 con mensaje de error
  - "Los campos Placa y Tarjeta de Circulación son obligatorios."

---

### 🌐 Caso 4: Error de Conexión
**Pasos:**
1. Desactivar el servidor Apache/PHP
2. Intentar registrar

**Resultado Esperado:**
- SweetAlert2 con:
  - Icono: X rojo
  - Título: "Error de Conexión"
  - Mensaje: "No se pudo conectar con el servidor. Verifique su conexión a internet."

---

## 🎯 Personalización Avanzada

### Cambiar Colores del Botón

```javascript
// Botón verde
confirmButtonColor: '#28a745'

// Botón naranja
confirmButtonColor: '#fd7e14'

// Botón morado
confirmButtonColor: '#6f42c1'
```

### Agregar Botón de Cancelar

```javascript
Swal.fire({
    icon: 'success',
    title: '¡Registro Exitoso!',
    text: 'El bus ha sido registrado correctamente.',
    showCancelButton: true,
    confirmButtonText: 'Ver Lista',
    cancelButtonText: 'Registrar Otro',
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#6c757d'
}).then((result) => {
    if (result.isConfirmed) {
        window.location.href = '<?php echo URLROOT; ?>/admin/registrar_buses';
    } else if (result.dismiss === Swal.DismissReason.cancel) {
        // Limpiar formulario y volver al paso 1
        wizardForm.reset();
        currentStep = 1;
        updateWizard();
    }
});
```

### Agregar Timer Automático

```javascript
Swal.fire({
    icon: 'success',
    title: '¡Registro Exitoso!',
    text: 'Redirigiendo en 3 segundos...',
    timer: 3000,
    timerProgressBar: true,
    showConfirmButton: false
}).then(() => {
    window.location.href = '<?php echo URLROOT; ?>/admin/registrar_buses';
});
```

### Mostrar Información Adicional

```javascript
Swal.fire({
    icon: 'success',
    title: '¡Registro Exitoso!',
    html: `
        <p>El bus ha sido registrado correctamente.</p>
        <hr>
        <p><strong>Placa:</strong> ${data.placa}</p>
        <p><strong>ID Interno:</strong> #${data.id}</p>
    `,
    confirmButtonColor: '#3085d6'
});
```

---

## 🔧 Solución de Problemas

### Problema 1: "La respuesta del servidor no es JSON válido"

**Causa:** El servidor está devolviendo HTML o texto plano en lugar de JSON

**Solución:**
1. Verificar que `ob_clean()` esté al inicio del método `guardar()`
2. Asegurarse de que no haya `echo` o `print` antes del `json_encode()`
3. Revisar que no haya errores PHP (warnings/notices) que corrompan la salida
4. Verificar el header: `header('Content-Type: application/json')`

**Debug:**
```javascript
.then(response => {
    console.log('Content-Type:', response.headers.get('content-type'));
    return response.text(); // Cambiar temporalmente a .text()
})
.then(text => {
    console.log('Respuesta cruda:', text); // Ver qué está devolviendo
    const data = JSON.parse(text);
    // ... resto del código
})
```

---

### Problema 2: SweetAlert2 no se muestra

**Causa:** La librería no está cargada

**Solución:**
1. Verificar en `app/views/layouts/header.php`:
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
```

2. Verificar en `app/views/layouts/footer.php`:
```html
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
```

3. Abrir la consola del navegador (F12) y escribir:
```javascript
console.log(typeof Swal); // Debe mostrar "object"
```

---

### Problema 3: El formulario se envía normalmente (recarga la página)

**Causa:** El `e.preventDefault()` no se está ejecutando

**Solución:**
1. Verificar que el ID del formulario sea `wizardForm`
2. Asegurarse de que el script esté dentro de `<script>` tags
3. Verificar que no haya errores JavaScript en la consola
4. Confirmar que el evento `submit` se esté capturando:
```javascript
wizardForm.addEventListener('submit', function(e) {
    console.log('Submit capturado'); // Debug
    e.preventDefault();
    // ... resto del código
});
```

---

### Problema 4: El botón queda deshabilitado después de un error

**Causa:** No se está restaurando el botón en el bloque `catch`

**Solución:**
Verificar que en TODOS los casos de error se ejecute:
```javascript
btnFinish.disabled = false;
btnFinish.innerHTML = btnOriginalText;
```

---

## 📚 Recursos Adicionales

### Documentación Oficial
- **SweetAlert2**: https://sweetalert2.github.io/
- **Fetch API**: https://developer.mozilla.org/es/docs/Web/API/Fetch_API
- **FormData**: https://developer.mozilla.org/es/docs/Web/API/FormData

### Ejemplos de SweetAlert2
- Galería de ejemplos: https://sweetalert2.github.io/#examples
- Configurador interactivo: https://sweetalert2.github.io/#configuration

---

## ✅ Checklist de Implementación

- [x] Backend devuelve JSON con `status` y `message`
- [x] Headers JSON configurados correctamente
- [x] `ob_clean()` implementado
- [x] Frontend intercepta el submit con `e.preventDefault()`
- [x] Fetch API envía FormData
- [x] Validación de Content-Type
- [x] SweetAlert2 muestra éxito con icono verde
- [x] SweetAlert2 muestra errores con icono rojo
- [x] Redirección después de éxito
- [x] Restauración del botón después de error
- [x] Manejo de errores de red
- [x] Detección de placas duplicadas

---

## 🎉 Resultado Final

### Antes (Método Tradicional)
```
Usuario → Submit → Recarga Página → alert() nativo → Pantalla en blanco
```

### Después (Método Moderno)
```
Usuario → Submit → AJAX → SweetAlert2 animado → Redirección suave
```

**Beneficios:**
- ✅ Sin recargas de página (mejor UX)
- ✅ Feedback visual profesional
- ✅ Animaciones suaves
- ✅ Mensajes descriptivos
- ✅ Manejo robusto de errores
- ✅ Código mantenible y escalable

---

## 📝 Notas Finales

Esta implementación sigue las mejores prácticas de desarrollo web moderno:

1. **Separación de responsabilidades**: Backend maneja lógica, Frontend maneja presentación
2. **Comunicación asíncrona**: No bloquea la interfaz
3. **Manejo de errores**: Cubre todos los casos posibles
4. **Experiencia de usuario**: Feedback claro y profesional
5. **Código limpio**: Comentado y estructurado

Puedes replicar este patrón en otros formularios del sistema (rutas, personal, clientes, etc.) cambiando únicamente:
- La URL del endpoint (`fetch('...')`)
- Los mensajes de SweetAlert2
- La URL de redirección

---

**Desarrollado con ❤️ para modernizar el sistema de venta de pasajes**
