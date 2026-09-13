# 📋 Instrucciones para Insertar Ejemplos de Viajes

## 🎯 Objetivo
Insertar 2 ejemplos de rutas de viaje en la base de datos y verificar que se muestren correctamente en la tabla de "Rutas Programadas".

---

## 📝 Datos de los Ejemplos

### **Ejemplo 1: La Paz → Copacabana**
- **Ruta:** LA PAZ → COPACABANA
- **Tipo de Bus:** BUS DE 45 ASIENTOS
- **Terminal Origen:** La Paz - Central
- **Terminal Destino:** Copacabana
- **Fecha:** 21/12/2025
- **Hora Salida:** 08:00
- **Hora Llegada:** 11:30
- **Precio:** Bs. 35.00
- **Tipo Servicio:** Ejecutivo
- **Servicios:** WiFi, Aire Acondicionado, Baño, Seguro
- **Estado:** Activo
- **Notas:** Viaje directo sin paradas intermedias. Incluye vista panorámica del Lago Titicaca.

### **Ejemplo 2: La Paz → Sorata**
- **Ruta:** LA PAZ → SORATA
- **Tipo de Bus:** BUS DE 49 ASIENTOS (2 pisos)
- **Terminal Origen:** La Paz - Central
- **Terminal Destino:** El Alto
- **Fecha:** 22/12/2025
- **Hora Salida:** 14:30
- **Hora Llegada:** 18:00
- **Precio:** Bs. 45.00
- **Tipo Servicio:** Platino (Premium)
- **Servicios:** WiFi, A/C, TV, Snacks, Baño, USB, Seguro
- **Estado:** Programado
- **Notas:** Servicio premium con snacks incluidos. Ruta escénica por los Andes.

---

## 🚀 Paso 1: Ejecutar el Script SQL

### **Opción A: Usando phpMyAdmin (Recomendado)**

1. **Abrir phpMyAdmin:**
   ```
   http://localhost/phpmyadmin
   ```

2. **Seleccionar la base de datos:**
   - En el panel izquierdo, haz clic en `venta_pasajes`

3. **Ir a la pestaña SQL:**
   - Haz clic en la pestaña "SQL" en la parte superior

4. **Cargar el script:**
   - Haz clic en "Examinar" o "Browse"
   - Navega a: `c:\xampp\htdocs\venta-pasajes\app\sql\insert_ejemplos_viajes.sql`
   - Selecciona el archivo

   **O copia y pega el contenido:**
   - Abre el archivo `insert_ejemplos_viajes.sql`
   - Copia todo el contenido
   - Pégalo en el editor SQL de phpMyAdmin

5. **Ejecutar:**
   - Haz clic en el botón "Continuar" o "Go"

6. **Verificar el resultado:**
   - Deberías ver un mensaje: "✅ Tabla actualizada y 2 ejemplos insertados correctamente"
   - También verás una tabla con los datos de los 2 viajes insertados

---

### **Opción B: Usando Línea de Comandos**

```bash
# Navegar a la carpeta del proyecto
cd c:\xampp\htdocs\venta-pasajes

# Ejecutar el script SQL
mysql -u root -p venta_pasajes < app/sql/insert_ejemplos_viajes.sql
```

Cuando te pida la contraseña, ingresa la contraseña de tu usuario root de MySQL (por defecto suele estar vacía, solo presiona Enter).

---

## 🔍 Paso 2: Verificar en la Base de Datos

Ejecuta esta consulta en phpMyAdmin para verificar que los datos se insertaron:

```sql
SELECT 
    v.id,
    CONCAT(r.origen, ' → ', r.destino) AS ruta,
    DATE_FORMAT(v.fecha_salida, '%d/%m/%Y') AS fecha,
    v.hora_salida,
    v.hora_llegada,
    CONCAT('Bs. ', v.precio_base) AS precio,
    v.tipo_servicio,
    tb.nombre AS tipo_bus,
    v.estado
FROM viajes v
INNER JOIN rutas r ON v.ruta_id = r.id
LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
ORDER BY v.id DESC
LIMIT 5;
```

**Resultado esperado:**
Deberías ver 2 filas con los datos de los viajes insertados.

---

## 🌐 Paso 3: Verificar en la Aplicación Web

1. **Abrir el navegador:**
   ```
   http://localhost/venta-pasajes/ventas/crear_ruta
   ```

2. **Verificar la tabla "Rutas Programadas":**
   - Deberías ver 2 filas en la tabla
   - Cada fila mostrará:
     - ✅ ID del viaje
     - ✅ Ruta (origen → destino)
     - ✅ Terminales
     - ✅ Fecha y hora de salida
     - ✅ Hora de llegada
     - ✅ Tipo de bus y capacidad
     - ✅ Precio con badge verde
     - ✅ Tipo de servicio
     - ✅ Estado con badge de color
     - ✅ Botones de acción (Ver, Editar, Eliminar)

---

## 🎨 Características Visuales de la Tabla

### **Badges de Estado:**
- 🟢 **Verde (Activo):** Viaje disponible para ventas
- 🔵 **Azul (Programado):** Viaje próximamente
- 🔴 **Rojo (Inactivo/Cancelado):** Viaje no disponible

### **Iconos:**
- 📅 Calendario para fechas
- 🕐 Reloj para horarios
- 🚌 Bus para tipo de vehículo
- 🏢 Edificio para terminales
- 👥 Personas para capacidad

### **Formato de Datos:**
- Fechas: DD/MM/YYYY
- Horas: HH:MM
- Precios: Bs. XX.XX (con 2 decimales)

---

## 🧪 Paso 4: Probar la Funcionalidad

### **Crear un Nuevo Viaje:**
1. Haz clic en el botón "Nueva Ruta"
2. Completa los 3 tabs del formulario
3. Guarda
4. Verifica que aparezca en la tabla

### **Verificar Datos en BD:**
```sql
SELECT * FROM viajes ORDER BY id DESC LIMIT 1;
```

---

## 📊 Estructura de la Tabla Actualizada

La tabla `viajes` ahora tiene estos campos:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único del viaje |
| ruta_id | INT | ID de la ruta (FK) |
| tipo_bus_id | INT | ID del tipo de bus (FK) |
| terminal_origen_id | INT | ID terminal origen (FK) |
| terminal_destino_id | INT | ID terminal destino (FK) |
| fecha_salida | DATETIME | Fecha y hora de salida |
| hora_salida | TIME | Hora de salida |
| fecha_llegada_estimada | DATETIME | Fecha y hora de llegada |
| hora_llegada | TIME | Hora de llegada |
| precio_base | DECIMAL(10,2) | Precio del pasaje |
| tipo_servicio | VARCHAR(50) | Tipo de servicio |
| servicios_incluidos | TEXT | JSON con servicios |
| notas | TEXT | Observaciones |
| estado | ENUM | Estado del viaje |
| fecha_creacion | TIMESTAMP | Fecha de creación |
| fecha_actualizacion | TIMESTAMP | Última actualización |

---

## ✅ Checklist de Verificación

- [ ] Script SQL ejecutado sin errores
- [ ] 2 registros insertados en la tabla `viajes`
- [ ] Página web muestra 2 viajes en la tabla
- [ ] Badges de estado se muestran correctamente
- [ ] Iconos se muestran correctamente
- [ ] Precios formateados con 2 decimales
- [ ] Fechas en formato DD/MM/YYYY
- [ ] Botones de acción visibles

---

## 🐛 Solución de Problemas

### **Error: "Unknown column 'tipo_bus_id'"**
**Causa:** La tabla no se actualizó correctamente.
**Solución:** Ejecuta el script SQL nuevamente.

### **No se muestran los viajes en la tabla**
**Causa:** Los datos no se insertaron o hay un error en la consulta.
**Solución:** 
1. Verifica en phpMyAdmin: `SELECT * FROM viajes;`
2. Verifica que el controlador esté cargando los datos
3. Revisa la consola del navegador (F12) por errores

### **Error: "Cannot add foreign key constraint"**
**Causa:** Las tablas referenciadas no existen o tienen datos incompatibles.
**Solución:**
1. Verifica que existan las tablas: `rutas`, `tipos_buses`, `terminales`
2. Verifica que los IDs en los INSERT existan en las tablas referenciadas

### **Los servicios no se muestran**
**Causa:** El campo `servicios_incluidos` no es de tipo TEXT.
**Solución:** Ejecuta:
```sql
ALTER TABLE viajes MODIFY COLUMN servicios_incluidos TEXT NULL;
```

---

## 📸 Resultado Esperado

Después de ejecutar el script, deberías ver una tabla como esta:

```
+----+------------------------+------------+----------+----------+------------------+---------+----------+
| ID | Ruta                   | Fecha      | Salida   | Llegada  | Bus              | Precio  | Estado   |
+----+------------------------+------------+----------+----------+------------------+---------+----------+
| 2  | LA PAZ → COPACABANA    | 21/12/2025 | 08:00:00 | 11:30:00 | BUS 45 ASIENTOS  | Bs.35.00| Activo   |
| 3  | LA PAZ → SORATA        | 22/12/2025 | 14:30:00 | 18:00:00 | BUS 49 ASIENTOS  | Bs.45.00| Programado|
+----+------------------------+------------+----------+----------+------------------+---------+----------+
```

---

## 🎉 ¡Listo!

Una vez ejecutado el script, tendrás:
- ✅ Tabla `viajes` actualizada con todos los campos necesarios
- ✅ 2 ejemplos de viajes insertados
- ✅ Datos visibles en la interfaz web
- ✅ Sistema completamente funcional

**¡Ahora puedes crear más rutas desde el modal!** 🚀
