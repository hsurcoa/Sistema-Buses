# 🚌 Sistema de Mapa de Asientos Interactivo - Resumen de Implementación

## ✅ Componentes Creados

### 📄 Archivos de Vista

1. **`app/views/admin/mapa_asientos.php`**
   - Vista standalone completa del mapa de asientos
   - Incluye estilos CSS integrados
   - JavaScript para interactividad
   - Soporte para 1 y 2 pisos
   - Diseño visual que replica las imágenes de referencia

2. **`app/views/admin/componentes/selector_asientos.php`**
   - Componente reutilizable para selección de asientos
   - Sistema de tabs para cambiar entre pisos
   - Panel de resumen con total a pagar
   - Leyenda de estados de asientos
   - Integración con SweetAlert2 para alertas

3. **`app/views/admin/componentes/layouts/piso1_default.php`**
   - Layout por defecto para piso 1
   - Incluye cabina del conductor con volante
   - 2 asientos premium en cabina
   - Grid de asientos 2-2 con pasillo central
   - Sección trasera con BAÑO, ESCALERA y DORMITORIO

4. **`app/views/admin/componentes/layouts/piso2_default.php`**
   - Layout por defecto para piso 2
   - Grid simplificado 2-2 con pasillo
   - Indicador visual de "PISO 2"
   - Colores diferenciados del piso 1

### 🔧 Backend (PHP)

5. **`app/models/AsientoModel.php`**
   - Modelo completo para gestión de asientos
   - Métodos principales:
     * `obtenerAsientosPorBus()` - Obtener asientos de un bus
     * `obtenerAsientosDisponibles()` - Verificar disponibilidad por ruta/fecha
     * `verificarDisponibilidad()` - Validar si un asiento está libre
     * `reservarAsientos()` - Crear reservas temporales
     * `crearAsientosPorTipo()` - Generar asientos para un tipo de bus
     * `generarConfiguracionDefecto()` - Crear configuración automática
     * `limpiarReservasExpiradas()` - Eliminar reservas vencidas

6. **`app/controllers/Admin.php`** (Actualizado)
   - Agregado `$asientoModel` al constructor
   - Nuevos métodos:
     * `mapa_asientos($tipoBusId)` - Vista del mapa
     * `obtener_asientos_disponibles()` - Endpoint AJAX
     * `reservar_asientos()` - Endpoint AJAX para reservas
     * `generar_asientos($tipoBusId)` - Generar asientos automáticamente
     * `limpiar_reservas_expiradas()` - Limpieza de reservas
     * `estadisticas_asientos($tipoBusId)` - Estadísticas

### 🗄️ Base de Datos

7. **`app/sql/setup_asientos.sql`**
   - Tabla `asientos` con campos:
     * id, tipo_bus_id, numero, piso, fila, columna
     * tipo (normal, premium, vip)
     * estado (disponible, ocupado, mantenimiento, bloqueado)
     * precio_adicional
   
   - Tabla `reservas_temporales`:
     * Gestión de reservas con expiración (15 minutos)
     * Estados: activa, confirmada, expirada, cancelada
   
   - Tabla `asientos_historial`:
     * Auditoría de cambios en asientos
   
   - Procedimientos almacenados:
     * `limpiar_reservas_expiradas()`
     * `obtener_asientos_disponibles()`
   
   - Evento programado:
     * Limpieza automática cada 5 minutos

### 📚 Documentación

8. **`docs/INTEGRACION_MAPA_ASIENTOS.md`**
   - Guía completa de integración
   - Instrucciones paso a paso
   - Ejemplos de código
   - Casos de uso
   - Troubleshooting

9. **`docs/README_ASIENTOS.md`** (Este archivo)
   - Resumen de implementación
   - Lista de archivos creados
   - Próximos pasos

---

## 🎨 Características Visuales Implementadas

### ✨ Diseño del Bus

- **Chasis del bus**: Fondo gris con bordes redondeados y sombra
- **Cabina del conductor**: Fondo oscuro con ícono de volante
- **Asientos con apoyabrazos**: Diseño 3D con sombras
- **Pasillo central**: Líneas punteadas y efecto de profundidad
- **Servicios traseros**: Bloques estilizados para BAÑO, ESCALERA, DORMITORIO

### 🎨 Estados de Asientos

| Estado | Color | Descripción |
|--------|-------|-------------|
| **Disponible** | Azul (`#3b82f6`) | Asiento libre para seleccionar |
| **Ocupado** | Gris (`#9ca3af`) | Asiento ya reservado/vendido |
| **Seleccionado** | Verde (`#10b981`) | Asiento elegido por el usuario |
| **Premium** | Naranja (`#f59e0b`) | Asientos especiales (cabina) |

### 🎭 Animaciones

- **Hover**: Escala 1.15 y elevación
- **Pulse**: Efecto de brillo en asientos seleccionados
- **Slide In**: Transición suave al cambiar de piso

---

## 🔌 Cómo Usar el Sistema

### Opción 1: Vista Standalone

```php
// En tu controlador
public function seleccionar_asientos($rutaId, $fecha) {
    $data = [
        'busData' => [
            'id' => 1,
            'nombre' => 'Bus de 49 asientos',
            'capacidad' => 49,
            'pisos' => 2
        ]
    ];
    
    $this->view('admin/mapa_asientos', $data);
}
```

### Opción 2: Componente Reutilizable

```php
// En cualquier vista
<?php
$busData = [
    'id' => $tipoBus->id,
    'nombre' => $tipoBus->nombre,
    'capacidad' => $tipoBus->capacidad,
    'pisos' => $tipoBus->pisos,
    'configuracion_asientos' => $tipoBus->configuracion_asientos
];

include APPROOT . '/views/admin/componentes/selector_asientos.php';
?>
```

### Opción 3: En Modal

```html
<div class="modal fade" id="modalAsientos">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-body">
                <?php include APPROOT . '/views/admin/componentes/selector_asientos.php'; ?>
            </div>
        </div>
    </div>
</div>
```

---

## 🚀 Endpoints API Disponibles

### 1. Obtener Asientos Disponibles (GET)
```
GET /admin/obtener_asientos_disponibles?ruta_id=1&fecha=2025-12-25&bus_id=1
```

**Respuesta:**
```json
{
    "success": true,
    "asientos": [
        {
            "id": 1,
            "numero": "1",
            "piso": 1,
            "tipo": "premium",
            "estado": "disponible"
        }
    ]
}
```

### 2. Reservar Asientos (POST)
```
POST /admin/reservar_asientos
Content-Type: application/json

{
    "asientos": [
        {"numero": "5", "piso": "1"},
        {"numero": "6", "piso": "1"}
    ],
    "ruta_id": 1,
    "fecha": "2025-12-25"
}
```

**Respuesta:**
```json
{
    "success": true,
    "message": "Asientos reservados exitosamente",
    "expira_en": 900
}
```

### 3. Generar Asientos Automáticamente (GET)
```
GET /admin/generar_asientos/1
```

### 4. Estadísticas de Asientos (GET)
```
GET /admin/estadisticas_asientos/1
```

---

## 📦 Instalación Rápida

### Paso 1: Ejecutar SQL
```bash
mysql -u root -p sistema_transportes < app/sql/setup_asientos.sql
```

### Paso 2: Verificar Archivos
Asegúrate de que todos los archivos estén en su lugar:
- ✅ Modelo: `app/models/AsientoModel.php`
- ✅ Vistas: `app/views/admin/mapa_asientos.php`
- ✅ Componentes: `app/views/admin/componentes/selector_asientos.php`
- ✅ Layouts: `app/views/admin/componentes/layouts/piso1_default.php`
- ✅ Layouts: `app/views/admin/componentes/layouts/piso2_default.php`

### Paso 3: Probar
```
http://localhost/venta-pasajes/admin/mapa_asientos/1
```

---

## 🔄 Flujo de Venta Completo

```
1. Usuario selecciona Ruta y Fecha
   ↓
2. Sistema carga mapa de asientos del bus asignado
   ↓
3. Usuario selecciona asientos (máximo 15 minutos)
   ↓
4. Sistema reserva temporalmente en BD
   ↓
5. Usuario completa datos de pasajeros
   ↓
6. Usuario procede al pago
   ↓
7. Sistema confirma venta y libera reserva temporal
   ↓
8. Sistema genera boleto y envía por email
```

---

## 🎯 Próximos Pasos Sugeridos

### Corto Plazo
- [ ] Integrar con sistema de pagos
- [ ] Implementar impresión de boletos
- [ ] Agregar notificaciones por email
- [ ] Crear panel de administración de asientos

### Mediano Plazo
- [ ] Implementar reportes de ocupación
- [ ] Agregar sistema de descuentos
- [ ] Crear app móvil para selección de asientos
- [ ] Implementar check-in digital

### Largo Plazo
- [ ] Sistema de fidelización de clientes
- [ ] Integración con GPS para tracking de buses
- [ ] Predicción de demanda con IA
- [ ] Sistema de recomendación de asientos

---

## 🐛 Troubleshooting

### Problema: Asientos no se muestran
**Solución**: Verificar que existan asientos en la tabla para ese tipo de bus
```sql
SELECT * FROM asientos WHERE tipo_bus_id = 1;
```

### Problema: Error al reservar
**Solución**: Verificar que el usuario esté logueado
```php
var_dump($_SESSION['usuario_id']);
```

### Problema: Reservas no expiran
**Solución**: Verificar que el evento esté activo
```sql
SHOW EVENTS;
SET GLOBAL event_scheduler = ON;
```

---

## 📞 Soporte

Para dudas o problemas:
- 📧 Email: soporte@sistema-transportes.com
- 📚 Documentación: `/docs/INTEGRACION_MAPA_ASIENTOS.md`
- 🐛 Issues: Crear issue en el repositorio

---

## 📄 Licencia

Este sistema fue desarrollado para el proyecto de Venta de Pasajes de Bus.
Todos los derechos reservados © 2025

---

**Última actualización**: 2025-12-20  
**Versión**: 1.0.0  
**Autor**: Desarrollador Full Stack Senior
