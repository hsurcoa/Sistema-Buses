# 📋 Guía de Integración: Sistema de Mapa de Asientos Interactivo

## 🎯 Descripción General

Este sistema proporciona un mapa de asientos interactivo para buses con soporte de 1 o 2 pisos, diseñado con **PHP 8**, **Tailwind CSS** y **JavaScript Vanilla**.

---

## 📁 Estructura de Archivos Creados

```
app/
├── views/
│   └── admin/
│       ├── mapa_asientos.php                    # Vista completa standalone
│       └── componentes/
│           ├── selector_asientos.php            # Componente reutilizable
│           └── layouts/
│               ├── piso1_default.php            # Layout piso 1
│               └── piso2_default.php            # Layout piso 2
├── models/
│   └── AsientoModel.php                         # Modelo de datos
└── sql/
    └── setup_asientos.sql                       # Script de base de datos
```

---

## 🗄️ Paso 1: Configurar la Base de Datos

### 1.1 Ejecutar el Script SQL

```bash
# Opción 1: Desde phpMyAdmin
# - Importar el archivo: app/sql/setup_asientos.sql

# Opción 2: Desde línea de comandos
mysql -u root -p sistema_transportes < app/sql/setup_asientos.sql
```

### 1.2 Verificar las Tablas Creadas

```sql
SHOW TABLES LIKE '%asientos%';
-- Debe mostrar:
-- - asientos
-- - reservas_temporales
-- - asientos_historial
```

---

## 🎨 Paso 2: Integración en el Dashboard

### 2.1 Opción A: Usar el Componente Reutilizable

En tu controlador (ej: `DashboardController.php`):

```php
<?php
class Dashboard extends Controller {
    
    public function seleccionarAsientos($rutaId, $fecha) {
        // Obtener datos del bus
        $asientoModel = $this->model('AsientoModel');
        $tipoBusModel = $this->model('TipoBusModel');
        
        // Obtener información del bus para esta ruta
        $tipoBus = $tipoBusModel->obtenerPorRuta($rutaId);
        
        // Obtener asientos con disponibilidad
        $asientos = $asientoModel->obtenerAsientosDisponibles(
            $rutaId, 
            $fecha, 
            $tipoBus->id
        );
        
        // Preparar datos para la vista
        $data = [
            'titulo' => 'Seleccionar Asientos',
            'busData' => [
                'id' => $tipoBus->id,
                'nombre' => $tipoBus->nombre,
                'capacidad' => $tipoBus->capacidad,
                'pisos' => $tipoBus->pisos,
                'configuracion_asientos' => $tipoBus->configuracion_asientos
            ],
            'asientos' => $asientos,
            'rutaId' => $rutaId,
            'fecha' => $fecha
        ];
        
        $this->view('dashboard/seleccionar_asientos', $data);
    }
}
?>
```

En tu vista (`views/dashboard/seleccionar_asientos.php`):

```php
<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-geo-alt-fill me-2"></i>
                Seleccione sus Asientos
            </h2>
            
            <?php 
            // Incluir el componente de selector
            include APPROOT . '/views/admin/componentes/selector_asientos.php';
            ?>
            
            <div class="mt-4">
                <button class="btn btn-lg btn-primary" onclick="procesarReserva()">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Continuar con la Compra
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function procesarReserva() {
    const asientosSeleccionados = obtenerAsientosSeleccionados();
    
    if (asientosSeleccionados.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin asientos',
            text: 'Debe seleccionar al menos un asiento'
        });
        return;
    }
    
    // Enviar al backend
    fetch('<?php echo URLROOT; ?>/dashboard/reservar_asientos', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            asientos: asientosSeleccionados,
            ruta_id: <?php echo $data['rutaId']; ?>,
            fecha: '<?php echo $data['fecha']; ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '<?php echo URLROOT; ?>/dashboard/confirmar_compra';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    });
}
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
```

### 2.2 Opción B: Usar en un Modal

```php
<!-- En tu vista principal -->
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAsientos">
    Seleccionar Asientos
</button>

<!-- Modal -->
<div class="modal fade" id="modalAsientos" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-bus-front me-2"></i>
                    Seleccione sus Asientos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php include APPROOT . '/views/admin/componentes/selector_asientos.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmarReserva()">
                    Confirmar Selección
                </button>
            </div>
        </div>
    </div>
</div>
```

---

## 🔧 Paso 3: Implementar el Backend

### 3.1 Método para Reservar Asientos

En tu controlador:

```php
public function reservar_asientos() {
    // Verificar que sea POST
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        return;
    }
    
    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    $asientos = $input['asientos'] ?? [];
    $rutaId = $input['ruta_id'] ?? 0;
    $fecha = $input['fecha'] ?? '';
    $usuarioId = $_SESSION['usuario_id'] ?? 0;
    
    // Validar datos
    if (empty($asientos) || !$rutaId || !$fecha || !$usuarioId) {
        echo json_encode([
            'success' => false, 
            'message' => 'Datos incompletos'
        ]);
        return;
    }
    
    // Extraer IDs de asientos
    $asientoIds = array_map(function($a) {
        return $a['numero']; // o el ID según tu estructura
    }, $asientos);
    
    // Reservar en el modelo
    $asientoModel = $this->model('AsientoModel');
    $resultado = $asientoModel->reservarAsientos(
        $asientoIds, 
        $usuarioId, 
        $rutaId, 
        $fecha
    );
    
    if ($resultado) {
        // Guardar en sesión para el siguiente paso
        $_SESSION['asientos_reservados'] = $asientos;
        $_SESSION['reserva_expira'] = time() + (15 * 60); // 15 minutos
        
        echo json_encode([
            'success' => true,
            'message' => 'Asientos reservados exitosamente',
            'expira_en' => 900 // segundos
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al reservar asientos. Algunos pueden estar ocupados.'
        ]);
    }
}
```

### 3.2 Método para Obtener Asientos Disponibles (AJAX)

```php
public function obtener_asientos_disponibles() {
    $rutaId = $_GET['ruta_id'] ?? 0;
    $fecha = $_GET['fecha'] ?? '';
    $busId = $_GET['bus_id'] ?? 0;
    
    if (!$rutaId || !$fecha || !$busId) {
        echo json_encode([
            'success' => false,
            'message' => 'Parámetros faltantes'
        ]);
        return;
    }
    
    $asientoModel = $this->model('AsientoModel');
    $asientos = $asientoModel->obtenerAsientosDisponibles($rutaId, $fecha, $busId);
    
    echo json_encode([
        'success' => true,
        'asientos' => $asientos
    ]);
}
```

---

## 🎨 Paso 4: Personalización Visual

### 4.1 Cambiar Colores de Estados

En el archivo CSS del componente, modifica:

```css
/* Asiento Disponible */
.seat-item-selector.disponible .seat-body {
    background: linear-gradient(135deg, #TU_COLOR_1 0%, #TU_COLOR_2 100%);
    border: 3px solid #TU_BORDE;
}

/* Asiento Seleccionado */
.seat-item-selector.seleccionado .seat-body {
    background: linear-gradient(135deg, #TU_COLOR_3 0%, #TU_COLOR_4 100%);
    border: 3px solid #TU_BORDE_2;
}
```

### 4.2 Modificar Layout de Asientos

Para cambiar la distribución (ej: 3-2 en lugar de 2-2):

En `piso1_default.php`, modifica el loop:

```php
<?php
$columnas = 5; // 3 + pasillo + 2
for ($col = 0; $col < $columnas; $col++):
    if ($col === 3): // Pasillo después de la tercera columna
        echo '<div class="aisle-space"></div>';
    else:
        // Renderizar asiento
    endif;
endfor;
?>
```

---

## 📊 Paso 5: Integración con Tipos de Buses

### 5.1 Actualizar el Modal de Tipos de Buses

En `tipos_buses.php`, reemplaza el área del diagrama:

```php
<!-- Columna Derecha: Diagrama de Bus -->
<div class="col-md-8">
    <div class="mb-3">
        <label class="form-label">Diseño de Asientos</label>
        <div id="contenedorDiagrama">
            <?php
            // Cargar el componente de selector
            $busData = [
                'id' => $tipo->id ?? 0,
                'nombre' => $tipo->nombre ?? 'Nuevo Bus',
                'capacidad' => $tipo->capacidad ?? 35,
                'pisos' => $tipo->pisos ?? 1,
                'configuracion_asientos' => $tipo->configuracion_asientos ?? null
            ];
            include APPROOT . '/views/admin/componentes/selector_asientos.php';
            ?>
        </div>
    </div>
</div>
```

---

## 🔄 Paso 6: Flujo Completo de Venta

### 6.1 Diagrama de Flujo

```
1. Usuario selecciona Ruta y Fecha
   ↓
2. Sistema muestra Mapa de Asientos
   ↓
3. Usuario selecciona asientos
   ↓
4. Sistema reserva temporalmente (15 min)
   ↓
5. Usuario completa datos de pasajeros
   ↓
6. Usuario procede al pago
   ↓
7. Sistema confirma venta y libera otros asientos
```

### 6.2 Implementación del Temporizador

```javascript
// Agregar al selector de asientos
let tiempoRestante = 900; // 15 minutos en segundos

function iniciarTemporizador() {
    const interval = setInterval(() => {
        tiempoRestante--;
        
        const minutos = Math.floor(tiempoRestante / 60);
        const segundos = tiempoRestante % 60;
        
        document.getElementById('temporizador').textContent = 
            `${minutos}:${segundos.toString().padStart(2, '0')}`;
        
        if (tiempoRestante <= 0) {
            clearInterval(interval);
            Swal.fire({
                icon: 'warning',
                title: 'Tiempo Expirado',
                text: 'Su reserva ha expirado. Por favor, seleccione nuevamente.',
                allowOutsideClick: false
            }).then(() => {
                location.reload();
            });
        }
    }, 1000);
}
```

---

## 🧪 Paso 7: Testing

### 7.1 Pruebas Manuales

1. **Verificar visualización**: Abrir el mapa con diferentes tipos de buses
2. **Probar selección**: Seleccionar y deseleccionar asientos
3. **Validar estados**: Verificar que asientos ocupados no se puedan seleccionar
4. **Probar tabs**: Cambiar entre pisos si el bus tiene 2 pisos
5. **Verificar resumen**: Comprobar que el total se calcule correctamente

### 7.2 Pruebas de Base de Datos

```sql
-- Verificar asientos creados
SELECT * FROM asientos WHERE tipo_bus_id = 1;

-- Simular reserva
INSERT INTO reservas_temporales 
(asiento_id, usuario_id, ruta_id, fecha_viaje, expira_en)
VALUES 
(5, 1, 1, '2025-12-25', DATE_ADD(NOW(), INTERVAL 15 MINUTE));

-- Verificar disponibilidad
CALL obtener_asientos_disponibles(1, 1, '2025-12-25');
```

---

## 📝 Notas Importantes

### Dependencias Requeridas

- **Bootstrap 5**: Para el sistema de grid y modales
- **Bootstrap Icons**: Para los iconos
- **SweetAlert2**: Para las alertas (opcional pero recomendado)
- **PHP 8+**: Para las características modernas de PHP
- **MySQL 5.7+**: Para los procedimientos almacenados

### Configuración de Tailwind (Opcional)

Si prefieres usar Tailwind en lugar de los estilos inline:

```bash
npm install -D tailwindcss
npx tailwindcss init
```

Luego reemplaza los `<style>` tags con clases de Tailwind.

---

## 🚀 Próximos Pasos

1. ✅ Implementar sistema de pagos
2. ✅ Agregar notificaciones por email
3. ✅ Implementar impresión de boletos
4. ✅ Agregar panel de administración de asientos
5. ✅ Implementar reportes de ocupación

---

## 📞 Soporte

Para dudas o problemas:
- Revisar los logs en `email_errors.log`
- Verificar la consola del navegador para errores de JavaScript
- Comprobar los logs de PHP en el servidor

---

**Última actualización**: 2025-12-20
**Versión**: 1.0.0
