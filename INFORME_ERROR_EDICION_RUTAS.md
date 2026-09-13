# INFORME DIAGNÓSTICO: Error al Guardar Edición de Ruta

**Fecha:** 30 de Diciembre de 2025
**Módulo:** Gestión de Rutas (Edición de Viajes)
**Severidad:** Media (Impide modificar horarios)

## 1. Descripción del Problema
El usuario reporta que al intentar editar la fecha o la hora de una ruta programada, los cambios no se guardan en el sistema. Aunque la operación parece completarse, los datos persisten con sus valores originales.

## 2. Análisis del Código (`RutaModel.php`)
Se ha revisado el método `guardarRutaViaje` encargado de procesar tanto la creación como la actualización de viajes. Se identificó una vulnerabilidad crítica en el manejo de formatos de fecha antes de enviar los datos a la base de datos.

### Segmento de Código Actual:
```php
$fechaHoraSalida = $data['fecha_salida'];
if (!empty($data['hora_salida'])) {
    $fechaHoraSalida .= ' ' . $data['hora_salida'];
}
```

### El Fallo Técnico:
El sistema toma el valor de fecha directamente del formulario HTML (`$data['fecha_salida']`) y lo concatena con la hora.
-   **Expectativa MySQL:** Formato estricto `AAAA-MM-DD HH:MM:SS` (Ej: `2025-12-31 08:00:00`).
-   **Escenario de Error:** Si el navegador o el selector de fecha envía el formato local (ej: `31/12/2025`), la cadena resultante es `31/12/2025 08:00`.
-   **Consecuencia:** MySQL rechaza este valor por ser un formato datetime inválido y aborta la actualización o guarda una fecha errónea (`0000-00-00`), provocando que los cambios no surtan efecto.

## 3. Solución Propuesta
Se debe implementar una **normalización forzosa de la fecha** en el servidor antes de concatenarla. Esto garantiza que, independientemente de cómo envíe la fecha el navegador (ISO o local), siempre se convierta al estándar MySQL (`Y-m-d`) antes de guardar.

### Corrección (Pseudocódigo):
```php
// Convertir cualquier input a Y-m-d seguro
$fechaSegura = date('Y-m-d', strtotime(str_replace('/', '-', $data['fecha_salida'])));
$fechaHoraSalida = $fechaSegura . ' ' . $data['hora_salida'];
```

Esta corrección hará el sistema robusto frente a diferencias regionales de formato y asegurará que las ediciones se guarden correctamente.
