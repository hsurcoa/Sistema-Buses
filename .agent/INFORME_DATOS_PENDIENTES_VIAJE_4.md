# 🕵️ INFORME DE ANÁLISIS: DATOS FALTANTES EN VENTA DE PASAJES (VIAJE #4)

**Fecha:** 25 de Diciembre de 2025  
**Módulo:** Venta de Pasajes (`venta_pasajes.php`)  
**Problema:** Campos Placa, Piloto y Copiloto muestran "PENDIENTE DE ASIGNACIÓN".

---

## 🔎 DIAGNÓSTICO TÉCNICO

Analizando el código fuente (`RutaModel.php`) y el comportamiento del sistema, se ha determinado la causa exacta del mensaje "PENDIENTE DE ASIGNACIÓN".

### 1. Lógica del Sistema
El sistema utiliza la siguiente consulta SQL para obtener los datos del viaje:

```sql
SELECT 
    COALESCE(CONCAT(u1.nombres, ' ', u1.apellidos), 'PENDIENTE DE ASIGNACIÓN') as chofer_nombre,
    COALESCE(b.placa, 'PENDIENTE DE ASIGNACIÓN') as bus_placa,
    ...
FROM viajes v
LEFT JOIN buses b ON v.bus_id = b.id
LEFT JOIN usuarios u1 ON v.chofer_id = u1.id
WHERE v.id = :id
```

La función `COALESCE` significa: *"Si el valor es NULL (vacío), muestra el texto alternativo"*.

### 2. Causa Raíz
El viaje con **ID #4** existe en la base de datos, pero **no tiene asignados los recursos necesarios**.

*   ❌ **Campo `bus_id`:** Es `NULL` en la tabla `viajes`. Por eso no muestra la PLACA ni habilita la búsqueda del Copiloto.
*   ❌ **Campo `chofer_id`:** Es `NULL` en la tabla `viajes`. Por eso no muestra el PILOTO.

Al no haber un bus asignado (`bus_id`), el sistema tampoco puede buscar automáticamente al copiloto, ya que la lógica actual busca al copiloto asociado a ese bus específico.

---

## 🛠️ SOLUCIONES PROPUESTAS

Para corregir esto, es necesario llenar esos datos vacíos en la base de datos. Tienes dos opciones:

### OPCIÓN A: Solución desde el Sistema (Recomendada)
Esta es la forma correcta de hacerlo operativamente:

1.  Vaya al menú principal del sistema.
2.  Busque el módulo **"Registros"** o **"Procesos"**.
3.  Ingrese a la opción **"Asignar Buses"** o **"Programación de Viajes"**.
4.  Busque el viaje de **EL ALTO -> CHAGUAYA** de las **07:30:00**.
5.  Edite el viaje y seleccione un **Bus**, un **Chofer** y un **Copiloto**.
6.  Guarde los cambios.
7.  Vuelva a la pantalla de venta de pasajes y recargue (F5).

### OPCIÓN B: Solución Rápida vía SQL (Para Testing)
Si solo necesitas ver datos de prueba inmediatamente, ejecuta este script SQL en tu base de datos:

```sql
-- Asignar un Bus y un Chofer al viaje #4
-- Nota: Asegúrate de usar IDs válidos de tu sistema
-- bus_id = 1 (Ejemplo: Bus Toyota)
-- chofer_id = 12 (Ejemplo: Juan Perez - Chofer)

UPDATE `viajes` 
SET 
    `bus_id` = 1,      -- Asigna el bus con ID 1
    `chofer_id` = 12   -- Asigna el chofer con ID 12
WHERE 
    `id` = 4;          -- Solo para este viaje específico
```

*(Nota: Para que aparezca el **Copiloto**, además de lo anterior, el bus asignado (ID 1) debe tener un copiloto vinculado en la tabla `asignaciones_buses`).*

---

## 📋 CONCLUSIÓN
No hay error en el código. El mensaje "PENDIENTE DE ASIGNACIÓN" es el comportamiento correcto del sistema alertando que **al viaje le faltan datos operativos**. La solución es asignar esos recursos al viaje.
