# 🔐 Sistema de Roles y Permisos - BusDriver

## 📋 Descripción

Sistema completo de gestión de roles y permisos para el sistema de transporte BusDriver. Permite asignar permisos granulares a diferentes roles de usuario mediante una interfaz visual intuitiva con toggle switches.

---

## 🎯 Características

✅ **Gestión Visual de Permisos**: Interfaz con toggle switches para activar/desactivar permisos  
✅ **Roles Predefinidos**: Administrador, Vendedor, Supervisor, Contador  
✅ **Permisos Agrupados**: Organizados por módulos (Dashboard, Procesos, Reportes, Registros, Administrador)  
✅ **Creación de Roles**: Modal para crear nuevos roles dinámicamente  
✅ **Actualización en Tiempo Real**: Cambios guardados mediante AJAX  
✅ **Diseño Responsivo**: Compatible con Bootstrap 5  
✅ **Alertas Elegantes**: Integración con SweetAlert2  

---

## 📁 Estructura de Archivos

```
venta-pasajes/
├── app/
│   ├── controllers/
│   │   └── Admin.php                    # Controlador con métodos de roles y permisos
│   ├── models/
│   │   └── RolPermiso.php              # Modelo para gestión de roles y permisos
│   ├── views/
│   │   ├── admin/
│   │   │   └── roles_permisos.php      # Vista principal de roles y permisos
│   │   └── layouts/
│   │       └── sidebar.php             # Sidebar actualizado con enlace
│   ├── core/
│   │   └── Database.php                # Clase Database con métodos de transacciones
│   └── sql/
│       ├── roles_permisos.sql          # Script SQL completo (con vistas y procedimientos)
│       ├── roles_permisos_install.sql  # Script SQL con manejo de FK
│       └── roles_permisos_simple.sql   # Script SQL simplificado (RECOMENDADO)
```

---

## 🚀 Instalación

### 1️⃣ Ejecutar Script SQL

**Opción A: Desde PowerShell (Recomendado)**
```powershell
Get-Content "c:\xampp\htdocs\venta-pasajes\app\sql\roles_permisos_simple.sql" | c:\xampp\mysql\bin\mysql.exe -u root -h localhost sistema_transportes
```

**Opción B: Desde phpMyAdmin**
1. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Seleccionar base de datos: `sistema_transportes`
3. Ir a pestaña "SQL"
4. Copiar y pegar contenido de `roles_permisos_simple.sql`
5. Ejecutar

### 2️⃣ Verificar Instalación

Ejecutar en MySQL:
```sql
-- Ver roles creados
SELECT * FROM roles;

-- Ver permisos creados
SELECT * FROM permisos ORDER BY grupo, orden;

-- Ver permisos del Administrador
SELECT r.nombre AS rol, p.grupo, p.vista
FROM roles r
INNER JOIN rol_permiso rp ON r.id = rp.rol_id
INNER JOIN permisos p ON rp.permiso_id = p.id
WHERE r.nombre = 'Administrador';
```

---

## 🎨 Uso de la Interfaz

### Acceder al Módulo

1. Iniciar sesión en el sistema
2. En el sidebar, ir a: **Administrador → Roles y Permisos**
3. URL directa: `http://localhost/venta-pasajes/admin/roles_permisos`

### Gestionar Permisos

1. **Seleccionar Rol**: Usar el dropdown "PERFILES" para elegir un rol
2. **Activar/Desactivar Permisos**: Hacer clic en los toggle switches
3. **Guardar Cambios**: Hacer clic en el botón "Guardar Cambios"
4. **Confirmación**: Se mostrará un mensaje de éxito con SweetAlert2

### Crear Nuevo Rol

1. Hacer clic en el botón **"+"** junto al selector de perfiles
2. Ingresar nombre del rol (obligatorio)
3. Ingresar descripción (opcional)
4. Hacer clic en "Crear Rol"
5. El nuevo rol aparecerá en el dropdown

---

## 🗄️ Estructura de Base de Datos

### Tabla: `roles`
```sql
id              INT(11) PK AUTO_INCREMENT
nombre          VARCHAR(50) UNIQUE
descripcion     TEXT
activo          TINYINT(1) DEFAULT 1
fecha_creacion  TIMESTAMP
```

### Tabla: `permisos`
```sql
id              INT(11) PK AUTO_INCREMENT
grupo           VARCHAR(50)
vista           VARCHAR(100)
clave           VARCHAR(100) UNIQUE
descripcion     TEXT
orden           INT(11)
activo          TINYINT(1) DEFAULT 1
```

### Tabla: `rol_permiso`
```sql
id                INT(11) PK AUTO_INCREMENT
rol_id            INT(11) FK → roles(id)
permiso_id        INT(11) FK → permisos(id)
fecha_asignacion  TIMESTAMP
```

---

## 📊 Roles Predefinidos

| Rol | Descripción | Permisos |
|-----|-------------|----------|
| **Administrador** | Acceso total | Todos los permisos |
| **Vendedor** | Ventas y consultas | Dashboard, Ventas Boletos, Encomiendas |
| **Supervisor** | Reportes y supervisión | Dashboard, Reportes, Asignar Buses |
| **Contador** | Finanzas | Dashboard, Reportes de Ventas |

---

## 🔧 API Endpoints

### GET `/admin/roles_permisos`
Muestra la vista principal de roles y permisos

### GET `/admin/get_permisos_rol/{rol_id}`
Obtiene los permisos de un rol específico (AJAX)

**Respuesta:**
```json
{
  "success": true,
  "permisos": [1, 2, 5, 8, 13]
}
```

### POST `/admin/guardar_permisos`
Guarda los permisos de un rol (AJAX)

**Request:**
```json
{
  "rol_id": 2,
  "permisos": [1, 2, 5, 8]
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Permisos actualizados correctamente"
}
```

### POST `/admin/crear_rol`
Crea un nuevo rol (AJAX)

**Request:**
```json
{
  "nombre": "Supervisor de Flota",
  "descripcion": "Gestión de buses y rutas"
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Rol creado correctamente",
  "rol_id": 5
}
```

---

## 🎨 Personalización

### Cambiar Colores del Header de Tabla

En `roles_permisos.php`, línea 68:
```html
<tr style="background: linear-gradient(135deg, #1e7e34 0%, #28a745 100%); color: white;">
```

Cambiar valores hexadecimales para personalizar el degradado verde.

### Modificar Grupos de Permisos

En `roles_permisos_simple.sql`, agregar nuevos permisos:
```sql
INSERT INTO `permisos` (`grupo`, `vista`, `clave`, `descripcion`, `orden`) VALUES
('NuevoGrupo', 'Nueva Vista', 'nuevo_grupo.nueva_vista', 'Descripción', 50);
```

---

## 🐛 Solución de Problemas

### Error: "Cannot load from mysql.proc"
**Solución**: Usar `roles_permisos_simple.sql` en lugar de `roles_permisos.sql`

### Error: "Foreign key constraint fails"
**Solución**: El script ya incluye `SET FOREIGN_KEY_CHECKS = 0`

### Los switches no se activan
**Verificar**:
1. Que el rol esté seleccionado en el dropdown
2. Que la consola del navegador no muestre errores JavaScript
3. Que SweetAlert2 esté cargado (verificar en footer.php)

### Error 404 al acceder a `/admin/roles_permisos`
**Verificar**:
1. Que el modelo `RolPermiso.php` exista en `app/models/`
2. Que el método `roles_permisos()` esté en `Admin.php`
3. Que la vista exista en `app/views/admin/roles_permisos.php`

---

## 📝 Notas Técnicas

- **Transacciones**: El modelo usa transacciones para garantizar integridad de datos
- **AJAX**: Todas las operaciones de guardado usan AJAX para evitar recargas
- **Seguridad**: Los permisos se validan en el backend
- **Responsive**: La tabla es responsive y se adapta a móviles

---

## 🔄 Próximas Mejoras

- [ ] Middleware de verificación de permisos en rutas
- [ ] Logs de auditoría de cambios de permisos
- [ ] Exportar/Importar configuración de roles
- [ ] Permisos a nivel de acción (crear, editar, eliminar)
- [ ] Interfaz para gestionar permisos individuales

---

## 👨‍💻 Autor

**Sistema BusDriver**  
Desarrollado con ❤️ usando PHP, MySQL, Bootstrap 5 y SweetAlert2

---

## 📄 Licencia

Este módulo es parte del sistema BusDriver y está sujeto a sus términos de licencia.

---

## 📞 Soporte

Para reportar problemas o sugerencias, contactar al equipo de desarrollo.

---

**Última actualización**: 2025-12-20
