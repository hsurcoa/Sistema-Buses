# 🎯 Sistema de Mapa de Asientos DINÁMICO - Implementación Final

## ✅ ¿Qué se implementó?

He creado un **sistema completamente dinámico** que genera el diagrama de asientos automáticamente basándose en los datos del bus (capacidad, pisos, etc.) **sin valores hardcodeados**.

---

## 🚀 Características Principales

### 1. **Generación Automática del Layout**
El sistema calcula automáticamente:
- ✅ Número de filas según la capacidad
- ✅ Distribución de asientos por piso (60% piso 1, 40% piso 2)
- ✅ Asientos premium en cabina (2 asientos)
- ✅ Layout 2-2 con pasillo central
- ✅ Servicios traseros (BAÑO, ESCALERA, DORMITORIO)

### 2. **Configuración Flexible**
```php
$busData = [
    'id' => 1,
    'nombre' => 'Bus de 49 asientos',
    'capacidad' => 49,  // ← Cualquier número
    'pisos' => 2,        // ← 1 o 2 pisos
    'configuracion_asientos' => null  // ← Opcional: JSON personalizado
];
```

### 3. **Estados Dinámicos desde BD**
Los estados de los asientos se obtienen de la base de datos:
- **Disponible**: Asientos libres
- **Ocupado**: Asientos ya vendidos/reservados
- **Seleccionado**: Asientos elegidos por el usuario
- **Premium**: Asientos especiales (cabina)

---

## 📁 Archivos Creados/Modificados

### ✅ Nuevo Componente Dinámico
```
app/views/admin/componentes/mapa_asientos_dinamico.php
```
**Características:**
- Genera el layout automáticamente
- Calcula filas y distribución
- Renderiza asientos con estados desde BD
- Soporte para 1 o 2 pisos
- ~800 líneas de código

### ✅ Vista Principal Actualizada
```
app/views/admin/mapa_asientos.php
```
**Cambios:**
- Ahora usa el componente dinámico
- Agregado panel de información del bus
- Botones de acciones (generar, estadísticas, exportar)
- Breadcrumb y navegación

---

## 🎨 Cómo Funciona

### Paso 1: Datos del Bus
```php
// En el controlador
$busData = [
    'id' => 1,
    'nombre' => 'Bus Ejecutivo',
    'capacidad' => 35,  // Puede ser cualquier número
    'pisos' => 1
];
```

### Paso 2: Generación Automática
```php
// El componente calcula automáticamente:
$asientosPremium = 2;
$asientosNormales = $capacidad - 2;  // 35 - 2 = 33
$filas = ceil($asientosNormales / 4);  // 33 / 4 = 9 filas
```

### Paso 3: Renderizado Dinámico
```php
// Loop que genera las filas
for ($fila = 0; $fila < $filas; $fila++) {
    // Genera 4 asientos por fila (2-pasillo-2)
    // Asigna estados desde la BD
    // Numera secuencialmente
}
```

---

## 💡 Ejemplos de Uso

### Ejemplo 1: Bus de 1 Piso (35 asientos)
```php
$busData = [
    'id' => 1,
    'nombre' => 'Bus Estándar',
    'capacidad' => 35,
    'pisos' => 1
];
```
**Resultado:**
- 2 asientos premium (cabina)
- 33 asientos normales
- 9 filas de 4 asientos (2-2)
- Servicios traseros

### Ejemplo 2: Bus de 2 Pisos (49 asientos)
```php
$busData = [
    'id' => 2,
    'nombre' => 'Bus VIP',
    'capacidad' => 49,
    'pisos' => 2
];
```
**Resultado:**
- **Piso 1:** 30 asientos (2 premium + 28 normales)
- **Piso 2:** 19 asientos normales
- Tabs para cambiar entre pisos
- Indicador visual de piso

### Ejemplo 3: Bus Personalizado (60 asientos)
```php
$busData = [
    'id' => 3,
    'nombre' => 'Bus Doble Piso',
    'capacidad' => 60,
    'pisos' => 2
];
```
**Resultado:**
- **Piso 1:** 36 asientos (60% del total)
- **Piso 2:** 24 asientos (40% del total)
- Se ajusta automáticamente

---

## 🔧 Integración con Base de Datos

### Obtener Estados desde BD
```php
// En el controlador
$asientos = $this->asientoModel->obtenerAsientosPorBus($tipoBusId);

// Pasar a la vista
$data = [
    'busData' => $busData,
    'asientos' => $asientos  // ← Estados reales de BD
];
```

### Estructura de Datos
```php
$asientos = [
    [
        'id' => 1,
        'numero' => '1',
        'piso' => 1,
        'estado' => 'disponible',  // ← Estado real
        'tipo' => 'premium'
    ],
    [
        'id' => 2,
        'numero' => '2',
        'piso' => 1,
        'estado' => 'ocupado',  // ← Ya vendido
        'tipo' => 'premium'
    ],
    // ...
];
```

---

## 📊 Algoritmo de Distribución

### Para 1 Piso:
```
Total asientos: N
Premium: 2
Normales: N - 2
Filas: ceil((N - 2) / 4)
```

### Para 2 Pisos:
```
Total asientos: N
Piso 1: ceil(N * 0.6) = 60% del total
Piso 2: N - Piso1 = 40% del total

Piso 1:
  - Premium: 2
  - Normales: Piso1 - 2
  - Filas: ceil((Piso1 - 2) / 4)

Piso 2:
  - Normales: Piso2
  - Filas: ceil(Piso2 / 4)
```

---

## 🎯 Ventajas del Sistema Dinámico

| Característica | Antes | Ahora |
|----------------|-------|-------|
| **Flexibilidad** | Valores fijos (35, 49) | Cualquier capacidad |
| **Mantenimiento** | Editar código HTML | Cambiar solo datos |
| **Escalabilidad** | Crear nuevo layout | Automático |
| **Precisión** | Puede haber errores | Cálculo exacto |
| **Tiempo** | Horas de desarrollo | Segundos |

---

## 🔄 Flujo Completo

```
1. Usuario crea tipo de bus
   ↓
2. Ingresa: nombre, capacidad, pisos
   ↓
3. Sistema calcula distribución automática
   ↓
4. Genera layout visual dinámico
   ↓
5. Opción: Generar asientos en BD
   ↓
6. Asientos listos para venta
```

---

## 📝 Código de Ejemplo Completo

### En el Controlador:
```php
public function mapa_asientos($tipoBusId) {
    // Obtener tipo de bus
    $tipoBus = $this->tipoBusModel->obtenerTipoBus($tipoBusId);
    
    // Obtener asientos (si existen)
    $asientos = $this->asientoModel->obtenerAsientosPorBus($tipoBusId);
    
    // Preparar datos
    $data = [
        'busData' => [
            'id' => $tipoBus->id,
            'nombre' => $tipoBus->nombre,
            'capacidad' => $tipoBus->capacidad,  // ← Dinámico
            'pisos' => $tipoBus->pisos,          // ← Dinámico
            'configuracion_asientos' => $tipoBus->configuracion_asientos
        ],
        'asientos' => $asientos
    ];
    
    // Renderizar vista
    $this->view('admin/mapa_asientos', $data);
}
```

### En la Vista:
```php
<?php
// Incluir componente dinámico
include __DIR__ . '/componentes/mapa_asientos_dinamico.php';
?>
```

**¡Eso es todo!** El componente hace el resto automáticamente.

---

## 🧪 Pruebas

### Probar con diferentes capacidades:
```bash
# Bus pequeño (20 asientos)
http://localhost/venta-pasajes/admin/mapa_asientos/1

# Bus mediano (35 asientos)
http://localhost/venta-pasajes/admin/mapa_asientos/2

# Bus grande (49 asientos)
http://localhost/venta-pasajes/admin/mapa_asientos/3

# Bus extra grande (60 asientos)
http://localhost/venta-pasajes/admin/mapa_asientos/4
```

---

## 🎨 Personalización

### Cambiar distribución de pisos:
```php
// En mapa_asientos_dinamico.php, línea ~50
$asientosPiso1 = ceil($capacidad * 0.7); // 70% en piso 1
$asientosPiso2 = $capacidad - $asientosPiso1; // 30% en piso 2
```

### Cambiar layout (3-2 en lugar de 2-2):
```php
// En mapa_asientos_dinamico.php, línea ~60
$config['pisos'][1] = [
    'asientos_por_fila' => 5,  // 3 + 2
    // ...
];
```

### Agregar más servicios:
```php
// En mapa_asientos_dinamico.php, línea ~200
<div class="service-item-dinamico wifi">
    <i class="bi bi-wifi"></i>
    <span>WIFI</span>
</div>
```

---

## 📞 Soporte

Si necesitas ayuda:
1. Revisa `docs/INTEGRACION_MAPA_ASIENTOS.md`
2. Revisa `docs/README_ASIENTOS.md`
3. Consulta el código en `mapa_asientos_dinamico.php`

---

## ✨ Resumen

**Antes:**
- Valores hardcodeados (7 filas, 8 filas, etc.)
- Layout fijo para cada tipo de bus
- Difícil de mantener

**Ahora:**
- ✅ **100% dinámico**
- ✅ Calcula automáticamente según capacidad
- ✅ Soporta cualquier número de asientos
- ✅ Estados desde base de datos
- ✅ Fácil de mantener y escalar

---

**Última actualización**: 2025-12-20  
**Versión**: 2.0.0 (Dinámica)  
**Autor**: Desarrollador Full Stack Senior
