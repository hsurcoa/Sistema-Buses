# 🎯 PLAN DE IMPLEMENTACIÓN SWEETALERT2 - SISTEMA COMPLETO

## 📊 Estado Actual

### ✅ Módulos YA Implementados (con SweetAlert2):
1. ✅ **Registro de Buses** (`/admin/registrar_buses`)
2. ✅ **Asignar Buses** (`/admin/asignar_buses`)
3. ✅ **Mapa de Asientos** (`/admin/mapa_asientos`)
4. ✅ **Reportes de Pasajeros** (`/reportes/pasajeros`)
5. ✅ **Crear Ruta** (`/ventas/crear_ruta`)
6. ✅ **Personal Form** (`/admin/personal_form`)
7. ✅ **Selector de Asientos** (componente)
8. ✅ **Mapa Asientos Dinámico** (componente)

### ❌ Módulos PENDIENTES (con alert() nativo):

#### 1. **Módulo de Backup** (`app/views/backup/index.php`)
- ❌ Línea 229: Error en operaciones
- **Controlador**: `app/controllers/Backup.php`
  - ❌ Línea 107: Archivo no encontrado
  - ❌ Línea 115: Backup eliminado
  - ❌ Línea 120: Error al eliminar
  - ❌ Línea 243: Backup exitoso

#### 2. **Módulo de Tipos de Buses** (`app/views/admin/tipos_buses.php`)
- ❌ Línea 450: Validación de nombre
- ❌ Línea 457: Validación de capacidad
- **Controlador**: `app/controllers/Admin.php`
  - ❌ Líneas 814-828: CRUD de tipos de buses

#### 3. **Módulo de Terminales** (`app/views/admin/registrar_terminal.php`)
- ❌ Línea 149: Confirmación de cambio de estado
- **Controlador**: `app/controllers/Admin.php`
  - ❌ Líneas 477-493: CRUD de terminales

#### 4. **Módulo de Series de Boletos** (`app/views/admin/registrar_serie_boletos.php`)
- **Controlador**: `app/controllers/Admin.php`
  - ❌ Líneas 663-677: CRUD de series

#### 5. **Módulo de Rutas y Paradas** (`app/views/admin/registrar_rutas_paradas.php`)
- **Controlador**: `app/controllers/Admin.php`
  - ❌ Líneas 730-777: CRUD de rutas

#### 6. **Módulo de Personal** (`app/controllers/Admin.php`)
- ❌ Línea 90: Validación de perfil
- ❌ Línea 117: Email duplicado

#### 7. **Módulo de Series** (`app/controllers/Series.php`)
- ❌ Líneas 44-58: CRUD de series

#### 8. **Módulo de Venta de Pasajes** (`app/views/ventas/venta_pasajes.php`)
- ❌ Línea 2145: Confirmación de cancelación de boleto

---

## 🎯 ESTRATEGIA DE IMPLEMENTACIÓN

### Fase 1: Controladores Backend (PHP)
Convertir todos los `alert()` de PHP a respuestas JSON

### Fase 2: Vistas Frontend (JavaScript)
Implementar SweetAlert2 en formularios y confirmaciones

### Fase 3: Pruebas y Validación
Verificar funcionamiento en todos los módulos

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### Módulo 1: Backup ⏳
- [ ] Backend: Convertir `Backup.php` a JSON
- [ ] Frontend: Implementar SweetAlert2 en `backup/index.php`
- [ ] Pruebas: Crear, descargar y eliminar backup

### Módulo 2: Tipos de Buses ⏳
- [ ] Backend: Convertir métodos en `Admin.php` (tipos_buses)
- [ ] Frontend: Implementar SweetAlert2 en `tipos_buses.php`
- [ ] Pruebas: CRUD completo

### Módulo 3: Terminales ⏳
- [ ] Backend: Convertir métodos en `Admin.php` (terminales)
- [ ] Frontend: Implementar SweetAlert2 en `registrar_terminal.php`
- [ ] Pruebas: CRUD completo

### Módulo 4: Series de Boletos ⏳
- [ ] Backend: Convertir métodos en `Admin.php` (series)
- [ ] Frontend: Implementar SweetAlert2 en `registrar_serie_boletos.php`
- [ ] Pruebas: CRUD completo

### Módulo 5: Rutas y Paradas ⏳
- [ ] Backend: Convertir métodos en `Admin.php` (rutas)
- [ ] Frontend: Implementar SweetAlert2 en `registrar_rutas_paradas.php`
- [ ] Pruebas: CRUD completo

### Módulo 6: Personal ⏳
- [ ] Backend: Convertir validaciones en `Admin.php` (personal)
- [ ] Pruebas: Registro y validaciones

### Módulo 7: Series ⏳
- [ ] Backend: Convertir `Series.php` a JSON
- [ ] Pruebas: CRUD completo

### Módulo 8: Venta de Pasajes ⏳
- [ ] Frontend: Reemplazar `confirm()` por SweetAlert2
- [ ] Pruebas: Cancelación de boletos

---

## 🚀 ORDEN DE IMPLEMENTACIÓN

1. **Backup** (Prioridad Alta - Funcionalidad crítica)
2. **Tipos de Buses** (Prioridad Alta - Base del sistema)
3. **Terminales** (Prioridad Alta - Base del sistema)
4. **Series de Boletos** (Prioridad Media)
5. **Rutas y Paradas** (Prioridad Media)
6. **Personal** (Prioridad Media)
7. **Series** (Prioridad Baja)
8. **Venta de Pasajes** (Prioridad Alta - UX crítica)

---

## 📝 PATRÓN A SEGUIR

### Backend (PHP):
```php
// ANTES:
echo "<script>alert('Mensaje'); window.location.href='...';< /script>";

// DESPUÉS:
ob_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status' => 'success', // o 'error'
    'message' => 'Mensaje descriptivo'
]);
exit;
```

### Frontend (JavaScript):
```javascript
// ANTES:
alert('Mensaje');

// DESPUÉS:
Swal.fire({
    icon: 'success', // o 'error', 'warning', 'info'
    title: 'Título',
    text: 'Mensaje descriptivo',
    confirmButtonColor: '#3085d6'
});
```

### Confirmaciones:
```javascript
// ANTES:
if (confirm('¿Está seguro?')) { ... }

// DESPUÉS:
Swal.fire({
    title: '¿Está seguro?',
    text: 'Esta acción no se puede deshacer',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, continuar',
    cancelButtonText: 'Cancelar'
}).then((result) => {
    if (result.isConfirmed) {
        // Acción confirmada
    }
});
```

---

## 🎨 BENEFICIOS ESPERADOS

- ✅ **100% del sistema** con SweetAlert2
- ✅ **Experiencia de usuario consistente** en todos los módulos
- ✅ **Sin recargas innecesarias** (AJAX)
- ✅ **Feedback visual profesional**
- ✅ **Manejo robusto de errores**

---

**Fecha de creación**: 2025-12-30
**Estado**: En progreso
**Progreso**: 8/16 módulos completados (50%)
