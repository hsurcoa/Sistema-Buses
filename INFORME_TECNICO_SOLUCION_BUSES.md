# Informe Técnico: Solución al Error "No hay buses disponibles"

## Diagnóstico del Problema

Tras realizar un análisis profundo de la base de datos y el código fuente (`RutaModel.php`), se han identificado dos causas raíz que impiden que el sistema muestre los buses disponibles y, por ende, que no se carguen los datos de placa y conductor.

### 1. Inconsistencia de Datos (Error Crítico)
El sistema está buscando buses activos utilizando el valor numérico `1`, pero su base de datos almacena el estado como la palabra `'activo'`. Esta discrepancia hace que la consulta SQL falle silenciosamente, devolviendo 0 resultados incluso si existen buses.

*   **Código (RutaModel.php)**: Busca `estado = 1`
*   **Base de Datos (Tabla 'buses')**: Contiene `estado = 'activo'`

### 2. Falta de Datos Específicos
Para el tipo de bus seleccionado en la imagen (**TURISMO 01**, ID: 10), **no existen buses registrados** en la tabla `buses`.
Aunque se solucione el error de código mencionado arriba, el sistema seguirá mostrando "No hay buses disponibles" para este tipo en particular hasta que se registre un bus asociado a él.

---

## Solución Definitiva (Paso a Paso)

Para solucionar el problema sin alterar la estructura de la base de datos, debe realizar una pequeña modificación en el archivo `app/models/RutaModel.php`.

### Paso 1: Modificar el Código Fuente

Abra el archivo `app/models/RutaModel.php` y localice la función `obtenerBusesPorTipo` (aproximadamente en la línea 374).

**Código Actual (Incorrecto):**
```php
public function obtenerBusesPorTipo($tipoBusId)
{
    // ...
    $this->db->query("SELECT id, placa, marca, numero_interno FROM buses WHERE tipo_bus_id = :tipo_id AND estado = 1");
    // ...
}
```

**Código Corregido:**
Cambie `estado = 1` por `estado = 'activo'`.

```php
public function obtenerBusesPorTipo($tipoBusId)
{
    /* 
       Nota: Idealmente aquí se filtraría también por disponibilidad de horario,
       pero para esta fase inicial filtraremos solo por activo y tipo.
    */
    // CORRECCIÓN: La base de datos usa 'activo' (texto) en lugar de 1 (número)
    $this->db->query("SELECT id, placa, marca, numero_interno FROM buses WHERE tipo_bus_id = :tipo_id AND estado = 'activo'");
    $this->db->bind(':tipo_id', $tipoBusId);
    return $this->db->resultSet();
}
```

### Paso 2: Registrar un Bus para "TURISMO 01"

Como se detectó que no hay buses para el tipo "TURISMO 01", debe registrar uno nuevo o editar uno existente para asignarle este tipo.

1.  Vaya al módulo de **Gestión de Buses**.
2.  Cree un nuevo bus o edite uno existente.
3.  Asegúrese de seleccionar **"TURISMO 01"** en el campo "Tipo de Bus".
4.  Guarde los cambios.

### Verificación

Una vez aplicados estos dos pasos:
1.  El mensaje de error desaparecerá.
2.  Podrá seleccionar el bus en la lista desplegable.
3.  Al seleccionar el bus, **automáticamente aparecerán la placa y el nombre del conductor**, resolviendo su problema original.
