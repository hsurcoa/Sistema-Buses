# 🚌 Bus Designer - Documentación Técnica

## Descripción General
Sistema profesional de diseño visual de distribución de asientos para buses, implementado con Konva.js y estilo arquitectónico técnico.

## Características Implementadas

### ✅ Estilo Visual Arquitectónico
- **Asientos**: Diseño con respaldo visible, bordes suaves, sombras sutiles
- **Elementos técnicos**: Baño, escalera, conductor con iconografía clara
- **Chasis del bus**: Contorno guía con líneas punteadas
- **Grilla de referencia**: Sistema de cuadrícula visual (opcional)
- **Paleta de colores profesional**: Tonos neutros y técnicos

### ✅ Funcionalidades Interactivas

#### Gestión de Elementos
- **Agregar Asiento**: Crea asientos numerados automáticamente
- **Agregar Baño**: Bloque gris con texto "BAÑO" vertical
- **Agregar Escalera**: Elemento con efecto de escalones
- **Agregar Conductor**: Área del conductor con icono de volante
- **Eliminar**: Doble clic sobre cualquier elemento

#### Sistema de Pisos
- **Piso 1 y Piso 2**: Gestión independiente de configuraciones
- **Cambio de piso**: Guarda automáticamente el estado actual
- **Persistencia**: Mantiene elementos al cambiar entre pisos

#### Grilla Magnética (Snapping)
- **Alineación automática**: Los elementos se ajustan a múltiplos de 10px
- **Precisión**: Garantiza distribución ordenada y profesional
- **Activación**: Al soltar cualquier elemento arrastrado

### ✅ Interacciones del Usuario

#### Arrastrar y Soltar
- Todos los elementos son `draggable: true`
- Cursor cambia a "move" al pasar sobre elementos
- Efecto hover con cambio de opacidad/color

#### Efectos Visuales
- **Hover**: Cambio de color en bordes de asientos
- **Sombras**: Profundidad en elementos principales
- **Transiciones**: Suaves y profesionales

### ✅ Persistencia de Datos

#### Formato JSON
```json
{
  "piso1": [
    {
      "id": "asiento-1",
      "tipo": "asiento",
      "numero": 1,
      "x": 100,
      "y": 150
    },
    {
      "id": "bano-1234567890",
      "tipo": "bano",
      "x": 200,
      "y": 300
    }
  ],
  "piso2": [...],
  "totalAsientos": 45,
  "metadata": {
    "fechaCreacion": "2025-12-20T15:22:00.000Z",
    "version": "1.0"
  }
}
```

#### Métodos de Exportación/Importación
- `exportarConfiguracion()`: Genera JSON completo
- `importarConfiguracion(jsonString)`: Carga diseño guardado
- `guardarEstadoPiso()`: Guarda automáticamente al cambiar
- `cargarEstadoPiso()`: Restaura elementos del piso

### ✅ Validaciones

#### Al Guardar
- ✓ Nombre del tipo de bus obligatorio
- ✓ Al menos 1 asiento en el diseño
- ✓ Configuración JSON válida
- ✓ Capacidad total calculada automáticamente

#### Restricciones
- Solo 1 conductor por piso
- Elementos no pueden salir del chasis
- Números de asiento únicos y consecutivos

## Arquitectura del Código

### Clase Principal: `BusDesigner`

```javascript
class BusDesigner {
    constructor(containerId, options)
    
    // Métodos de Inicialización
    initKonva()
    dibujarFondo()
    dibujarChasis()
    dibujarGrilla()
    
    // Métodos de Creación de Elementos
    crearAsiento(id, x, y)
    crearBano(x, y)
    crearEscalera(x, y)
    crearConductor(x, y)
    
    // Métodos de Interacción
    agregarInteracciones(grupo)
    snapToGrid(elemento)
    eliminarElemento(elemento)
    
    // Métodos de Gestión de Pisos
    cambiarPiso(numeroPiso)
    guardarEstadoPiso()
    cargarEstadoPiso()
    
    // Métodos de Persistencia
    exportarConfiguracion()
    importarConfiguracion(jsonString)
    contarAsientosTotales()
    
    // Utilidades
    obtenerTipoElemento(id)
    recalcularAsientos()
    destruir()
}
```

### Integración con el Frontend

#### Archivos Modificados
1. **`app/controllers/Admin.php`**
   - Agregado `tipoBusModel`
   - Métodos: `tipos_buses()`, `guardar_tipo_bus()`, `cambiar_estado_tipo_bus()`

2. **`app/models/TipoBusModel.php`**
   - CRUD completo para tipos de buses
   - Métodos: `listarTiposBuses()`, `agregarTipoBus()`, `actualizarTipoBus()`

3. **`app/views/admin/tipos_buses.php`**
   - Modal con canvas Konva
   - Integración con clase BusDesigner
   - Formulario de configuración

4. **`public/js/bus-designer.js`**
   - Clase completa BusDesigner
   - ~600 líneas de código
   - Totalmente documentado

#### Base de Datos
```sql
CREATE TABLE tipos_buses (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  capacidad INT NOT NULL DEFAULT 0,
  pisos INT NOT NULL DEFAULT 1,
  configuracion_asientos TEXT NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Uso del Sistema

### 1. Crear Nuevo Tipo de Bus
```javascript
// Al hacer clic en "Agregar Nuevo Tipo"
abrirModalNuevoTipoBus()
  → Inicializa BusDesigner
  → Canvas vacío listo para diseñar
```

### 2. Diseñar Distribución
```javascript
// Agregar elementos
agregarAsiento()    // Asiento numerado
agregarBano()       // Baño
agregarEscalera()   // Escalera (para buses de 2 pisos)
agregarConductor()  // Área del conductor

// Interactuar
- Arrastrar elementos
- Doble clic para eliminar
- Cambiar entre pisos
```

### 3. Guardar Configuración
```javascript
guardarTipoBus()
  → Valida datos
  → Exporta JSON
  → Envía formulario a servidor
  → Guarda en base de datos
```

### 4. Editar Tipo Existente
```javascript
abrirModalTipoBus(id, nombre, capacidad, pisos, config)
  → Carga datos del formulario
  → Importa configuración JSON
  → Renderiza elementos en canvas
  → Listo para editar
```

## Ventajas del Sistema

### 🎨 Diseño Profesional
- Estilo arquitectónico técnico
- Interfaz intuitiva y moderna
- Feedback visual inmediato

### 🔧 Técnicamente Robusto
- Código modular y reutilizable
- Clase encapsulada
- Manejo de errores
- Validaciones completas

### 💾 Persistencia Confiable
- JSON estructurado
- Versionado de configuraciones
- Importación/exportación sin pérdida de datos

### 🚀 Escalable
- Fácil agregar nuevos tipos de elementos
- Extensible para más funcionalidades
- Preparado para múltiples pisos

## Próximas Mejoras Sugeridas

1. **Plantillas Predefinidas**: Buses comunes (45, 47, 49, 63 asientos)
2. **Zoom y Pan**: Para buses muy grandes
3. **Deshacer/Rehacer**: Historial de cambios
4. **Copiar/Pegar**: Duplicar elementos
5. **Rotación**: Asientos en diferentes orientaciones
6. **Exportar Imagen**: PNG del diseño final
7. **Modo Vista Previa**: Ver sin editar

## Soporte Técnico

### Requisitos
- PHP 7.4+
- MySQL 5.7+
- Navegador moderno (Chrome, Firefox, Edge)
- Konva.js 9.x

### Compatibilidad
- ✅ Desktop: Chrome, Firefox, Safari, Edge
- ✅ Tablet: iPad, Android tablets
- ⚠️ Mobile: Funcional pero limitado por tamaño de pantalla

---

**Desarrollado por**: Equipo de Desarrollo
**Versión**: 1.0.0
**Fecha**: Diciembre 2025
