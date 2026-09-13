# 🔍 INFORME DE ANÁLISIS: ROL "CHOFER" NO APARECE EN DROPDOWN

**Fecha:** 25 de Diciembre de 2025  
**Analista:** Antigravity AI  
**Módulo Afectado:** Registrar Personal (`personal_form.php`)  
**Severidad:** 🔴 ALTA - Funcionalidad crítica afectada

---

## 📋 RESUMEN EJECUTIVO

El rol **"Chofer"** no aparece en el dropdown de "Perfil" del formulario de registro de personal, a pesar de que existen registros de personal con este perfil en la base de datos. El análisis revela que el problema radica en la **ausencia del rol "Chofer" en la tabla `roles`** de la base de datos.

---

## 🔎 ANÁLISIS DETALLADO

### 1. **EVIDENCIA VISUAL**
Según la imagen proporcionada, el dropdown de "Perfil" muestra las siguientes opciones:
- ✅ Administrador
- ✅ Contador
- ✅ Copiloto
- ✅ Supervisor
- ✅ Vendedor
- ❌ **Chofer** (AUSENTE)

### 2. **ANÁLISIS DE LA BASE DE DATOS**

#### 2.1 Tabla `roles` (Estado Actual)
```sql
-- Roles existentes en la tabla roles
INSERT INTO `roles` VALUES
(1,'Administrador','Acceso total al sistema',1,'2025-12-20 10:40:54',NULL),
(2,'Vendedor','Acceso a módulos de venta y consultas',1,'2025-12-20 10:40:54',NULL),
(3,'Supervisor','Acceso a reportes y supervisión',1,'2025-12-20 10:40:54',NULL),
(4,'Contador','Acceso a módulos financieros y reportes',1,'2025-12-20 10:40:54',NULL),
(5,'Copiloto','Personal de apoyo encargado de asistir al conductor y pasajeros',1,'2025-12-24 20:55:33',NULL);
```

**🔴 PROBLEMA IDENTIFICADO:** No existe ningún registro con el nombre **"Chofer"** en la tabla `roles`.

#### 2.2 Tabla `personal` (Inconsistencia Detectada)
```sql
-- Registros de personal con perfil "Chofer"
(12,'Juan','Perez','DNI','10000001','Masculino','1990-01-01','999999999','juan@test.com','Direccion Test',NULL,NULL,NULL,'Chofer',NULL,1,'2025-12-20 13:40:52','2025-12-20 13:40:52'),
(13,'Carlos','Gomez','DNI','10000002','Masculino','1990-01-01','999999999','carlos@test.com','Direccion Test',NULL,NULL,NULL,'Chofer',NULL,1,'2025-12-20 13:40:52','2025-12-20 13:40:52'),
(14,'Luis','Soto','DNI','10000003','Masculino','1990-01-01','999999999','luis@test.com','Direccion Test',NULL,NULL,NULL,'Chofer',NULL,1,'2025-12-20 13:40:52','2025-12-20 13:40:52'),
(18,'Miguel Angel','Mendez Jimenez','CI','3206492','Masculino','1330-06-10','71956889','miguemendez@gmail.com','Zona las Viviendas Mercado Carmen','La Paz','Murillo','4','Chofer','1766239691_user02.jpg',1,'2025-12-20 14:08:11','2025-12-20 14:08:11'),
(19,'Grover','Surco Mamani','CI','6242551','Masculino','1988-07-14','73069547','groversm@gmail.com','Zona Santiago I','La Paz','Murillo','2','Chofer','1766424057_simon bolivar 3d.jpeg',1,'2025-12-22 17:20:57','2025-12-22 17:20:57')
```

**⚠️ INCONSISTENCIA:** Existen **5 registros de personal** con el perfil "Chofer" almacenado directamente en el campo `perfil` de la tabla `personal`, pero este rol no existe en la tabla `roles`.

### 3. **ANÁLISIS DEL CÓDIGO**

#### 3.1 Controlador (`Admin.php`)
```php
public function registrar_personal()
{
    $personal = $this->personalModel->obtenerPersonal();
    $departamentos = $this->personalModel->obtenerDepartamentos();
    $roles = $this->rolPermisoModel->obtenerRoles(); // ← Obtiene roles desde la tabla roles
    
    $data = [
        'title' => 'Registrar Personal',
        'personal' => $personal,
        'departamentos' => $departamentos,
        'roles' => $roles  // ← Pasa los roles a la vista
    ];
    
    $this->view('layouts/header', $data);
    $this->view('layouts/sidebar', $data);
    $this->view('admin/personal_form', $data);
    $this->view('layouts/footer', $data);
}
```

#### 3.2 Modelo (`RolPermiso.php`)
```php
public function obtenerRoles()
{
    $this->db->query("SELECT * FROM roles WHERE activo = 1 ORDER BY nombre");
    return $this->db->resultSet();
}
```

**✅ CÓDIGO CORRECTO:** El código está funcionando correctamente. Obtiene todos los roles activos de la tabla `roles` y los pasa a la vista.

#### 3.3 Vista (`personal_form.php`)
```php
<div class="col-md-3">
    <label class="form-label small text-muted text-uppercase fw-bold">Perfil:</label>
    <select class="form-select" name="perfil" required>
        <option value="" selected disabled>Seleccione</option>
        <?php if (!empty($data['roles'])): ?>
            <?php foreach ($data['roles'] as $rol): ?>
                <option value="<?php echo htmlspecialchars($rol->nombre); ?>">
                    <?php echo htmlspecialchars($rol->nombre); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
</div>
```

**✅ VISTA CORRECTA:** La vista itera correctamente sobre los roles disponibles y los muestra en el dropdown.

---

## 🎯 CAUSA RAÍZ

### **Problema Principal:**
El rol **"Chofer"** no existe en la tabla `roles` de la base de datos, por lo que no puede ser mostrado en el dropdown del formulario.

### **Problema Secundario (Inconsistencia de Datos):**
Existen 5 registros de personal con el perfil "Chofer" almacenado directamente en el campo `perfil` de la tabla `personal`. Esto indica que:
1. El rol "Chofer" fue eliminado de la tabla `roles` en algún momento
2. O nunca fue creado formalmente en la tabla `roles`
3. Los registros de personal fueron creados con un valor hardcodeado "Chofer" en lugar de usar una referencia a la tabla `roles`

### **Diseño de Base de Datos:**
El sistema tiene una **inconsistencia de diseño**:
- La tabla `personal` almacena el perfil como un campo de texto (`VARCHAR`)
- No existe una relación de clave foránea entre `personal.perfil` y `roles.nombre`
- Esto permite almacenar valores que no existen en la tabla `roles`

---

## 💡 SOLUCIONES PROPUESTAS

### **SOLUCIÓN 1: INSERTAR EL ROL "CHOFER" EN LA TABLA ROLES** ⭐ (RECOMENDADA)

#### Ventajas:
- ✅ Solución rápida y directa
- ✅ Mantiene la consistencia con los datos existentes
- ✅ No requiere modificar código
- ✅ Permite asignar permisos específicos al rol Chofer

#### Desventajas:
- ⚠️ No resuelve el problema de diseño de base de datos

#### Implementación:
```sql
-- Script SQL para insertar el rol Chofer
INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `activo`, `fecha_creacion`) 
VALUES (6, 'Chofer', 'Personal encargado de conducir los vehículos de transporte', 1, NOW());
```

#### Pasos adicionales:
1. Asignar permisos básicos al rol Chofer en la tabla `rol_permiso`
2. Verificar que el rol aparezca en el dropdown
3. Crear un usuario de prueba con rol Chofer para validar

---

### **SOLUCIÓN 2: NORMALIZAR LA BASE DE DATOS** (SOLUCIÓN A LARGO PLAZO)

#### Ventajas:
- ✅ Elimina inconsistencias de datos
- ✅ Mejora la integridad referencial
- ✅ Facilita el mantenimiento futuro
- ✅ Previene errores de validación

#### Desventajas:
- ⚠️ Requiere modificaciones significativas en la base de datos
- ⚠️ Requiere actualizar el código existente
- ⚠️ Necesita migración de datos
- ⚠️ Mayor tiempo de implementación

#### Implementación:

##### Paso 1: Modificar la tabla `personal`
```sql
-- Agregar columna para la relación con roles
ALTER TABLE `personal` 
ADD COLUMN `rol_id` INT(11) NULL AFTER `perfil`,
ADD CONSTRAINT `fk_personal_rol` 
    FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;
```

##### Paso 2: Migrar datos existentes
```sql
-- Insertar el rol Chofer si no existe
INSERT INTO `roles` (`nombre`, `descripcion`, `activo`) 
VALUES ('Chofer', 'Personal encargado de conducir los vehículos de transporte', 1)
ON DUPLICATE KEY UPDATE nombre = nombre;

-- Actualizar referencias en la tabla personal
UPDATE `personal` p
INNER JOIN `roles` r ON p.perfil = r.nombre
SET p.rol_id = r.id
WHERE p.perfil IS NOT NULL;
```

##### Paso 3: Verificar integridad de datos
```sql
-- Verificar registros sin rol asignado
SELECT id, nombres, apellidos, perfil, rol_id 
FROM `personal` 
WHERE perfil IS NOT NULL AND rol_id IS NULL;
```

##### Paso 4: Deprecar campo `perfil` (opcional)
```sql
-- Renombrar el campo antiguo para mantener historial
ALTER TABLE `personal` 
CHANGE COLUMN `perfil` `perfil_legacy` VARCHAR(50) NULL;
```

##### Paso 5: Actualizar el código PHP
```php
// En Personal.php (modelo)
public function guardarPersonal($datos) {
    $this->db->query("
        INSERT INTO personal (
            nombres, apellidos, tipo_documento, numero_documento,
            genero, fecha_nacimiento, celular, email,
            direccion_domicilio, departamento, provincia, distrito,
            rol_id, foto, estado  -- Cambiar 'perfil' por 'rol_id'
        ) VALUES (
            :nombres, :apellidos, :tipo_documento, :numero_documento,
            :genero, :fecha_nacimiento, :celular, :email,
            :direccion_domicilio, :departamento, :provincia, :distrito,
            :rol_id, :foto, :estado
        )
    ");
    
    // Cambiar el bind de perfil a rol_id
    $this->db->bind(':rol_id', $datos['rol_id']);
    // ... resto de binds
}
```

---

### **SOLUCIÓN 3: CREAR ROLES PREDEFINIDOS ADICIONALES** (COMPLEMENTARIA)

Además del rol "Chofer", se recomienda crear otros roles operativos que podrían ser necesarios:

```sql
-- Script para crear roles operativos completos
INSERT INTO `roles` (`nombre`, `descripcion`, `activo`, `fecha_creacion`) VALUES
('Chofer', 'Personal encargado de conducir los vehículos de transporte', 1, NOW()),
('Mecánico', 'Personal encargado del mantenimiento de vehículos', 1, NOW()),
('Despachador', 'Personal encargado de despachar vehículos', 1, NOW()),
('Cajero', 'Personal encargado de operaciones de caja', 1, NOW())
ON DUPLICATE KEY UPDATE nombre = nombre;
```

---

## 📊 COMPARATIVA DE SOLUCIONES

| Criterio | Solución 1 (Insertar Rol) | Solución 2 (Normalizar BD) | Solución 3 (Roles Adicionales) |
|----------|---------------------------|----------------------------|-------------------------------|
| **Tiempo de Implementación** | 5 minutos | 2-4 horas | 10 minutos |
| **Complejidad** | Baja | Alta | Baja |
| **Impacto en Código** | Ninguno | Alto | Ninguno |
| **Mejora de Diseño** | No | Sí | No |
| **Riesgo** | Muy bajo | Medio | Muy bajo |
| **Escalabilidad** | Media | Alta | Media |
| **Mantenibilidad** | Media | Alta | Media |

---

## 🎯 RECOMENDACIÓN FINAL

### **Enfoque Híbrido (Corto y Largo Plazo):**

#### **FASE 1: SOLUCIÓN INMEDIATA** (Implementar HOY)
1. Ejecutar **Solución 1**: Insertar el rol "Chofer" en la tabla `roles`
2. Ejecutar **Solución 3**: Crear roles operativos adicionales
3. Verificar que el dropdown muestre todos los roles correctamente

#### **FASE 2: MEJORA ESTRUCTURAL** (Planificar para próxima iteración)
1. Implementar **Solución 2**: Normalizar la base de datos
2. Crear un script de migración de datos
3. Actualizar el código para usar `rol_id` en lugar de `perfil`
4. Implementar validaciones de integridad referencial

---

## 📝 SCRIPT SQL COMPLETO PARA SOLUCIÓN INMEDIATA

```sql
-- ============================================
-- SCRIPT DE CORRECCIÓN: ROL CHOFER FALTANTE
-- Fecha: 2025-12-25
-- Descripción: Inserta el rol Chofer y roles operativos adicionales
-- ============================================

-- 1. Insertar el rol Chofer (ID 6)
INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `activo`, `fecha_creacion`) 
VALUES (6, 'Chofer', 'Personal encargado de conducir los vehículos de transporte', 1, NOW());

-- 2. Insertar roles operativos adicionales (opcional pero recomendado)
INSERT INTO `roles` (`nombre`, `descripcion`, `activo`, `fecha_creacion`) VALUES
('Mecánico', 'Personal encargado del mantenimiento de vehículos', 1, NOW()),
('Despachador', 'Personal encargado de despachar vehículos y coordinar salidas', 1, NOW()),
('Cajero', 'Personal encargado de operaciones de caja y cobros', 1, NOW())
ON DUPLICATE KEY UPDATE nombre = nombre;

-- 3. Asignar permisos básicos al rol Chofer
-- (Dashboard principal para que puedan ver información básica)
INSERT INTO `rol_permiso` (`rol_id`, `permiso_id`) 
SELECT 6, id FROM `permisos` WHERE clave = 'dashboard.principal'
ON DUPLICATE KEY UPDATE rol_id = rol_id;

-- 4. Verificar la inserción
SELECT * FROM `roles` WHERE nombre = 'Chofer';

-- 5. Verificar que todos los registros de personal con perfil "Chofer" sean válidos
SELECT id, nombres, apellidos, perfil 
FROM `personal` 
WHERE perfil = 'Chofer';
```

---

## ✅ CHECKLIST DE VALIDACIÓN POST-IMPLEMENTACIÓN

Después de ejecutar el script SQL, verificar:

- [ ] El rol "Chofer" aparece en la tabla `roles`
- [ ] El rol "Chofer" tiene `activo = 1`
- [ ] El dropdown de "Perfil" muestra la opción "Chofer"
- [ ] Se puede crear un nuevo registro de personal con perfil "Chofer"
- [ ] Los 5 registros existentes de personal con perfil "Chofer" siguen siendo válidos
- [ ] El rol "Chofer" tiene al menos un permiso asignado (dashboard.principal)
- [ ] No hay errores en el log de PHP o MySQL

---

## 🔄 IMPACTO EN OTROS MÓDULOS

### Módulos que podrían verse afectados:
1. **Asignar Buses** (`asignar_buses.php`): Usa el modelo `AsignacionModel` que filtra por perfil "Chofer"
   ```php
   // Código actual en AsignacionModel.php
   $this->db->query("SELECT id, nombres, apellidos FROM personal WHERE perfil = 'Chofer' AND estado = 1 ORDER BY apellidos ASC");
   ```
   ✅ **No requiere cambios** - Sigue funcionando porque usa el campo `perfil` directamente

2. **Venta de Pasajes** (`venta_pasajes.php`): Muestra información del chofer
   ✅ **No requiere cambios** - Solo visualiza datos

3. **Reportes**: Podrían filtrar por rol/perfil
   ✅ **Verificar** - Asegurar que los reportes incluyan el rol Chofer

---

## 📚 DOCUMENTACIÓN ADICIONAL

### Referencias de Código:
- **Controlador:** `app/controllers/Admin.php` (línea 32-49)
- **Modelo Roles:** `app/models/RolPermiso.php` (línea 21-25)
- **Vista Formulario:** `app/views/admin/personal_form.php` (línea 143-153)
- **Modelo Asignación:** `app/models/AsignacionModel.php` (línea 17-21)

### Referencias de Base de Datos:
- **Tabla roles:** Línea 1139-1164 en `backup_db_2025-12-24_23-05.sql`
- **Tabla personal:** Línea 867-909 en `backup_db_2025-12-24_23-05.sql`
- **Tabla rol_permiso:** Línea 1085-1129 en `backup_db_2025-12-24_23-05.sql`

---

## 🎓 LECCIONES APRENDIDAS

1. **Integridad Referencial:** Es fundamental usar claves foráneas para mantener la consistencia de datos
2. **Validación de Datos:** Implementar validaciones a nivel de base de datos y aplicación
3. **Auditoría de Cambios:** Documentar todos los cambios en la estructura de la base de datos
4. **Testing:** Verificar la existencia de datos relacionados antes de eliminar registros
5. **Normalización:** Evitar almacenar valores que deberían ser referencias a otras tablas

---

## 📞 CONTACTO Y SOPORTE

Para cualquier duda o problema relacionado con esta implementación, contactar a:
- **Desarrollador:** Antigravity AI
- **Fecha de Análisis:** 25 de Diciembre de 2025

---

**FIN DEL INFORME**
