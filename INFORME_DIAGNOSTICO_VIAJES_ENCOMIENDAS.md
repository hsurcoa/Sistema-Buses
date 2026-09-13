# INFORME DE DIAGNÓSTICO: No aparecen Viajes Disponibles

**Fecha:** 30 de Diciembre de 2025
**Módulo Afectado:** Encomiendas > Nueva Encomienda
**Problema Reportado:** El selector "Viaje Disponible (Salida)" aparece vacío ("No hay viajes programados") a pesar de haber seleccionado una Ruta.

---

## 1. Análisis Técnico

Tras inspeccionar la base de datos y la lógica del sistema, se han identificado dos causas principales por las que no se listan los viajes:

### Causa A: Fechas Vencidas (Principal)
El sistema está diseñado para mostrar solo **viajes futuros** (que no hayan salido aún).
*   **Fecha Actual del Sistema:** 30 de Diciembre de 2025.
*   **Datos en Base de Datos:** Los viajes registrados para la ruta "EL ALTO - CHAGUAYA" tienen fecha del **25 de Diciembre de 2025**.
*   **Resultado:** El sistema filtra estos viajes porque considera que el bus ya partió hace 5 días.

### Causa B: Filtro de Estado Estricto
La consulta SQL actual busca estrictamente viajes con estado **'Programado'**.
*   **Datos en Base de Datos:** El viaje existente (#4) tiene estado **'Activo'**.
*   **Resultado:** Aunque la fecha fuera correcta, el viaje no aparecería porque el sistema de encomiendas no estaba considerando viajes que ya están en proceso de abordaje ('Activo').

---

## 2. Solución Propuesta

Para solucionar esto, se deben tomar dos medidas: una a nivel de datos y otra a nivel de código para flexibilizar la operación.

### Solución 1: Actualizar Fechas (Inmediata)
Ejecutar el siguiente comando SQL para mover los viajes al futuro (ej: mañana), de modo que aparezcan disponibles para recibir carga.

```sql
-- Mover viajes Activos/Programados al día de mañana para pruebas
UPDATE viajes 
SET fecha_salida = DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY),
    estado = 'Programado'
WHERE estado IN ('Activo', 'Programado');
```

### Solución 2: Ajuste de Lógica (Recomendada)
Modificar el modelo `EncomiendaModel.php` para que acepte también viajes en estado 'Activo' (útil para envíos de último minuto).

**Código a modificar en `listarViajesFuturosPorRuta`:**
```php
// ANTES:
WHERE v.ruta_id = :ruta AND v.estado = 'Programado'

// DESPUÉS (Sugerido):
WHERE v.ruta_id = :ruta AND v.estado IN ('Programado', 'Activo')
```

---

## 3. Conclusión
El sistema funciona correctamente, pero **no muestra datos antiguos**. Para probar el módulo hoy, es necesario crear nuevos viajes para fechas futuras o actualizar los existentes.
