# 🚌 BusLayoutDesigner - Documentación Técnica

## 📋 Descripción General

`BusLayoutDesigner` es una clase JavaScript moderna (ES6) que gestiona el dibujo interactivo de distribución de asientos para buses utilizando **Fabric.js**. Diseñada específicamente para sistemas de transporte con soporte para buses de 2 pisos.

---

## ✨ Características Principales

### 🎨 Diseño Horizontal (Landscape)
- **Orientación**: Frente a la izquierda, Motor a la derecha
- **Dimensiones**: 900x350px (configurable)
- **Optimizado**: Para modales anchos sin scroll

### 🏢 Manejo de 2 Pisos
- **Contenedores separados**: `layerPiso1` y `layerPiso2`
- **Cambio dinámico**: Guarda automáticamente al cambiar de piso
- **Persistencia**: Mantiene el estado de cada piso

### 🔢 Contador Global de Asientos
- **Numeración continua**: Si Piso 1 tiene asientos 1-15, Piso 2 comienza en 16
- **Auto-incremento**: Gestión automática de números
- **Actualización dinámica**: Recalcula al eliminar asientos

### 🧲 Grid Snapping Magnético
- **Tamaño de grilla**: 20px
- **Alineación automática**: Los objetos se ajustan al mover
- **Evento**: `object:moving` de Fabric.js

---

## 🚀 Instalación y Uso

### 1. Incluir Fabric.js

```html
<!-- CDN de Fabric.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>

<!-- Clase BusLayoutDesigner -->
<script src="js/bus-layout-designer-fabric.js"></script>
```

### 2. HTML Container

```html
<div id="canvas-container"></div>
```

### 3. Inicializar

```javascript
const designer = new BusLayoutDesigner('canvas-container', {
    width: 900,
    height: 350
});
```

---

## 📚 API Pública

### Constructor

```javascript
new BusLayoutDesigner(canvasId, options)
```

**Parámetros:**
- `canvasId` (string): ID del contenedor HTML
- `options` (object): Configuración opcional
  - `width` (number): Ancho del canvas (default: 900)
  - `height` (number): Alto del canvas (default: 350)

**Ejemplo:**
```javascript
const designer = new BusLayoutDesigner('canvas-container', {
    width: 900,
    height: 350
});
```

---

### Métodos Principales

#### `init(canvasId)`
Configura el canvas de Fabric.js.

```javascript
designer.init('canvas-container');
```

---

#### `setFloor(floorNumber)`
Cambia la vista al piso especificado.

**Parámetros:**
- `floorNumber` (number): 1 o 2

**Comportamiento:**
1. Guarda el estado del piso actual
2. Limpia el canvas (excepto chasis)
3. Carga los objetos del nuevo piso

**Ejemplo:**
```javascript
designer.setFloor(2); // Cambiar a Piso 2
```

---

#### `addSeat()`
Agrega un asiento en una posición libre automática.

**Comportamiento:**
- Incrementa el contador global
- Busca una posición libre en la grilla
- Aplica grid snapping automático

**Ejemplo:**
```javascript
designer.addSeat(); // Agrega asiento con número auto-incrementado
```

---

#### `addGadget(type)`
Agrega un elemento especial (escalera, baño, conductor).

**Parámetros:**
- `type` (string): `'stairs'`, `'toilet'`, o `'driver'`

**Restricciones:**
- **Conductor**: Solo visible en Piso 1, máximo 1 por piso

**Ejemplo:**
```javascript
designer.addGadget('stairs');   // Agregar escalera
designer.addGadget('toilet');   // Agregar baño
designer.addGadget('driver');   // Agregar conductor (solo Piso 1)
```

---

#### `exportConfiguration()`
Exporta la configuración completa como JSON.

**Retorna:**
- `string`: JSON con toda la configuración

**Estructura del JSON:**
```json
{
  "capacity": 63,
  "floors": 2,
  "distribution": {
    "floor1": [
      {
        "type": "seat",
        "number": 1,
        "left": 200,
        "top": 100,
        "width": 40,
        "height": 40
      }
    ],
    "floor2": []
  },
  "metadata": {
    "globalSeatCounter": 63,
    "createdAt": "2025-12-20T15:30:00.000Z",
    "version": "2.0"
  }
}
```

**Ejemplo:**
```javascript
const json = designer.exportConfiguration();
console.log(json);

// Enviar al backend
fetch('/admin/guardar_tipo_bus', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: json
});
```

---

#### `importConfiguration(jsonString)`
Importa una configuración desde JSON.

**Parámetros:**
- `jsonString` (string): JSON con la configuración

**Ejemplo:**
```javascript
const config = '{"capacity":63,"floors":2,...}';
designer.importConfiguration(config);
```

---

#### `destroy()`
Destruye la instancia y libera recursos.

**Ejemplo:**
```javascript
designer.destroy();
designer = null;
```

---

## 🎨 Objetos Disponibles

### 1. Asiento (Seat)

**Especificaciones:**
- **Dimensiones**: 40x40px
- **Color**: Blanco (#ffffff)
- **Borde**: Azul (#3498db)
- **Respaldo**: Rectángulo fino superior
- **Texto**: Número centrado

**Propiedades:**
```javascript
{
    type: 'seat',
    number: 1,
    left: 200,
    top: 100,
    width: 40,
    height: 40
}
```

**Comportamiento:**
- Draggable con grid snapping
- Numeración automática
- Seleccionable

---

### 2. Conductor (Driver)

**Especificaciones:**
- **Dimensiones**: 80x60px
- **Color**: Amarillo claro (#fff3cd)
- **Icono**: Volante con cruz
- **Restricción**: Solo Piso 1, máximo 1

**Propiedades:**
```javascript
{
    type: 'driver',
    left: 60,
    top: 100,
    width: 80,
    height: 60
}
```

---

### 3. Escalera (Stairs)

**Especificaciones:**
- **Dimensiones**: 60x100px
- **Color**: Gris claro (#e9ecef)
- **Efecto**: Líneas horizontales (peldaños)

**Propiedades:**
```javascript
{
    type: 'stairs',
    left: 300,
    top: 150,
    width: 60,
    height: 100
}
```

---

### 4. Baño (Toilet)

**Especificaciones:**
- **Dimensiones**: 60x80px
- **Color**: Azul claro (#d1ecf1)
- **Texto**: "WC" y "BAÑO"

**Propiedades:**
```javascript
{
    type: 'toilet',
    left: 400,
    top: 150,
    width: 60,
    height: 80
}
```

---

## 🔧 Métodos Internos

### `drawChassis()`
Dibuja el contorno del bus con elementos decorativos.

### `drawGrid()`
Dibuja la grilla de referencia (20px).

### `setupEvents()`
Configura eventos de Fabric.js:
- `object:moving`: Grid snapping
- `object:modified`: Guardar estado
- `selection:created`: Highlight
- `selection:cleared`: Remove highlight

### `saveCurrentFloor()`
Guarda el estado del piso actual en `layerPiso1` o `layerPiso2`.

### `loadCurrentFloor()`
Carga los objetos del piso actual desde los datos guardados.

### `findFreePosition()`
Busca una posición libre en la grilla para nuevos objetos.

### `updateCapacity()`
Actualiza el contador de capacidad total.

### `getTotalSeats()`
Retorna el total de asientos en ambos pisos.

---

## 🎯 Integración con el Sistema

### Ejemplo Completo

```javascript
// 1. Inicializar
let busDesigner = null;

function inicializarDesignador() {
    busDesigner = new BusLayoutDesigner('canvas-container', {
        width: 900,
        height: 350
    });
}

// 2. Agregar elementos
function agregarAsiento() {
    if (busDesigner) {
        busDesigner.addSeat();
    }
}

function agregarBano() {
    if (busDesigner) {
        busDesigner.addGadget('toilet');
    }
}

// 3. Cambiar de piso
function mostrarPiso(numeroPiso) {
    if (busDesigner) {
        busDesigner.setFloor(numeroPiso);
    }
}

// 4. Guardar configuración
function guardarTipoBus() {
    if (!busDesigner) {
        alert('Error: El diseñador no está inicializado');
        return;
    }
    
    const configuracionJSON = busDesigner.exportConfiguration();
    document.getElementById('configuracionAsientos').value = configuracionJSON;
    
    const totalAsientos = busDesigner.getTotalSeats();
    document.getElementById('capacidadTipoBus').value = totalAsientos;
    
    if (totalAsientos === 0) {
        alert('Debe agregar al menos un asiento');
        return;
    }
    
    document.getElementById('formTipoBus').submit();
}

// 5. Cargar configuración existente
function cargarConfiguracion(config) {
    if (busDesigner && config) {
        try {
            busDesigner.importConfiguration(config);
        } catch (error) {
            console.error('Error al cargar:', error);
        }
    }
}

// 6. Limpiar al cerrar modal
document.getElementById('modalTipoBus').addEventListener('hidden.bs.modal', function() {
    if (busDesigner) {
        busDesigner.destroy();
        busDesigner = null;
    }
});
```

---

## 🎨 Personalización de Colores

```javascript
const designer = new BusLayoutDesigner('canvas-container');

// Modificar colores después de inicializar
designer.colors.seat = '#e3f2fd';
designer.colors.seatBorder = '#2196f3';
designer.colors.driver = '#fff9c4';
```

---

## 🐛 Manejo de Errores

```javascript
try {
    const designer = new BusLayoutDesigner('canvas-container');
    const config = designer.exportConfiguration();
} catch (error) {
    console.error('Error en BusLayoutDesigner:', error);
    alert('Ocurrió un error al procesar el diseño del bus');
}
```

---

## 📊 Eventos y Callbacks

### Callback de Capacidad

```javascript
// Definir callback global
window.actualizarCapacidad = function(total) {
    console.log('Total de asientos:', total);
    document.getElementById('capacidadTipoBus').value = total;
};
```

---

## 🔍 Debugging

```javascript
// Ver estado actual
console.log('Piso actual:', designer.currentFloor);
console.log('Contador global:', designer.globalSeatCounter);
console.log('Datos Piso 1:', designer.layerPiso1);
console.log('Datos Piso 2:', designer.layerPiso2);
console.log('Total asientos:', designer.getTotalSeats());
```

---

## 📦 Estructura de Archivos

```
public/
├── js/
│   └── bus-layout-designer-fabric.js  (Clase principal)
└── bus-layout-designer-demo.html      (Demo completo)
```

---

## 🌐 Compatibilidad

- **Navegadores**: Chrome, Firefox, Safari, Edge (últimas versiones)
- **Fabric.js**: v5.3.0+
- **ES6**: Requiere navegadores modernos

---

## 📝 Notas Importantes

1. **Grid Snapping**: El tamaño de grilla es de 20px, no modificable sin editar la clase.
2. **Contador Global**: El contador de asientos es compartido entre pisos.
3. **Conductor**: Solo puede existir en el Piso 1.
4. **Persistencia**: Los datos se guardan automáticamente al cambiar de piso.
5. **Eliminación**: Use la tecla DELETE o doble clic para eliminar objetos.

---

## 🚀 Próximas Mejoras

- [ ] Re-numeración automática al eliminar asientos intermedios
- [ ] Rotación de asientos (90°, 180°, 270°)
- [ ] Templates predefinidos (2-2, 2-1, 1-2)
- [ ] Zoom in/out
- [ ] Deshacer/Rehacer (Undo/Redo)
- [ ] Exportar como imagen PNG

---

## 📞 Soporte

Para reportar bugs o solicitar nuevas características, contacte al equipo de desarrollo.

---

**Versión**: 2.0  
**Última actualización**: 2025-12-20  
**Autor**: Sistema de Transporte
