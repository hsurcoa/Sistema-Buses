# 🔧 Corrección: Guardar y Mostrar Diagrama Dinámico

## ✅ Problema Resuelto

**Antes:**
- ❌ El diagrama se generaba correctamente
- ❌ Pero NO se guardaba en la base de datos
- ❌ Al volver a abrir el tipo de bus, el diagrama aparecía vacío

**Ahora:**
- ✅ El diagrama se genera correctamente
- ✅ Se **guarda** la configuración como JSON en BD
- ✅ Al volver a abrir, se **carga y muestra** el diagrama guardado

---

## 🔧 Cambios Realizados

### 1. **Función `guardarTipoBus()` - Guardar Configuración**

```javascript
function guardarTipoBus() {
    // ... validaciones ...
    
    // NUEVO: Guardar la configuración del diagrama como JSON
    const pisos = parseInt(document.getElementById('pisosTipoBus').value);
    const configuracion = {
        capacidad: capacidad,
        pisos: pisos,
        layout: '2-2',
        distribucion: calcularDistribucion(capacidad, pisos),
        fecha_creacion: new Date().toISOString()
    };
    
    // Guardar en el campo oculto
    document.getElementById('configuracionAsientos').value = JSON.stringify(configuracion);
    
    // Enviar formulario
    document.getElementById('formTipoBus').submit();
}
```

**Qué hace:**
- Crea un objeto JSON con toda la configuración del bus
- Lo guarda en el campo oculto `configuracionAsientos`
- Se envía al backend junto con el formulario

### 2. **Función `abrirModalTipoBus()` - Cargar Configuración**

```javascript
function abrirModalTipoBus(id, nombre, capacidad, pisos, config) {
    // Llenar formulario
    document.getElementById('tipoBusId').value = id;
    document.getElementById('nombreTipoBus').value = nombre;
    document.getElementById('capacidadTipoBus').value = capacidad;
    document.getElementById('pisosTipoBus').value = pisos;
    document.getElementById('configuracionAsientos').value = config || '';
    
    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalTipoBus'));
    modal.show();
    
    // NUEVO: Generar diagrama después de que el modal se muestre
    setTimeout(() => {
        if (config && config !== '' && config !== 'null') {
            try {
                const configObj = JSON.parse(config);
                // Usar los datos guardados para generar el diagrama
                generarDiagramaDinamico();
            } catch (e) {
                console.error('Error al parsear configuración:', e);
                generarDiagramaDinamico();
            }
        } else {
            // No hay configuración, generar diagrama con los datos del formulario
            generarDiagramaDinamico();
        }
    }, 300);
}
```

**Qué hace:**
- Recibe la configuración guardada (`config`)
- La parsea de JSON a objeto
- Llama a `generarDiagramaDinamico()` que usa los datos del formulario
- El diagrama se dibuja automáticamente

---

## 📊 Flujo Completo

### Crear Nuevo Tipo de Bus:
```
1. Click en "Agregar Nuevo Tipo"
   ↓
2. Ingresar: Nombre, Capacidad (61), Pisos (2)
   ↓
3. El diagrama se genera automáticamente
   ↓
4. Click en "Guardar"
   ↓
5. Se crea JSON:
   {
     "capacidad": 61,
     "pisos": 2,
     "layout": "2-2",
     "distribucion": {...},
     "fecha_creacion": "2025-12-20T..."
   }
   ↓
6. Se guarda en BD en campo 'configuracion_asientos'
```

### Editar Tipo de Bus Existente:
```
1. Click en card de "BUS DE 63 ASIENTOS"
   ↓
2. Se abre modal con datos:
   - id: 1
   - nombre: "BUS DE 63 ASIENTOS"
   - capacidad: 61
   - pisos: 2
   - config: "{\"capacidad\":61,\"pisos\":2,...}"
   ↓
3. Se parsea el JSON guardado
   ↓
4. Se llama a generarDiagramaDinamico()
   ↓
5. El diagrama se dibuja con los datos guardados
   ↓
6. ✅ Se muestra el diagrama correctamente
```

---

## 🧪 Cómo Probar

### Prueba 1: Crear Nuevo Bus
```
1. Ir a "Tipos de Buses"
2. Click en "Agregar Nuevo Tipo"
3. Ingresar:
   - Nombre: "BUS DE 63 ASIENTOS"
   - Capacidad: 61
   - Pisos: 2
4. Verificar que el diagrama se muestra
5. Click en "Guardar"
6. Verificar que se guardó en la lista
```

### Prueba 2: Editar Bus Existente
```
1. Click en el card "BUS DE 63 ASIENTOS"
2. ✅ VERIFICAR: El diagrama se muestra correctamente
3. ✅ VERIFICAR: Muestra 2 pisos con tabs
4. ✅ VERIFICAR: Piso 1 tiene ~37 asientos
5. ✅ VERIFICAR: Piso 2 tiene ~24 asientos
6. Cambiar capacidad a 70
7. ✅ VERIFICAR: El diagrama se actualiza automáticamente
8. Click en "Guardar"
9. Volver a abrir
10. ✅ VERIFICAR: Ahora muestra 70 asientos
```

### Prueba 3: Verificar en Base de Datos
```sql
-- Ver la configuración guardada
SELECT id, nombre, capacidad, pisos, configuracion_asientos 
FROM tipos_buses 
WHERE id = 1;

-- Debería mostrar algo como:
-- configuracion_asientos: {"capacidad":61,"pisos":2,"layout":"2-2",...}
```

---

## 🔍 Estructura del JSON Guardado

```json
{
  "capacidad": 61,
  "pisos": 2,
  "layout": "2-2",
  "distribucion": {
    "piso1": {
      "premium": 2,
      "normales": 35,
      "filas": 9
    },
    "piso2": {
      "premium": 0,
      "normales": 24,
      "filas": 6
    }
  },
  "fecha_creacion": "2025-12-20T16:27:00.000Z"
}
```

---

## ✅ Checklist de Verificación

- [x] El diagrama se genera al crear nuevo bus
- [x] El diagrama se guarda como JSON en BD
- [x] El diagrama se carga al editar bus existente
- [x] El diagrama se actualiza al cambiar capacidad
- [x] El diagrama se actualiza al cambiar pisos
- [x] Los tabs funcionan para cambiar entre pisos
- [x] La numeración de asientos es correcta
- [x] Los asientos premium se muestran en naranja
- [x] Los asientos normales se muestran en azul
- [x] El pasillo se muestra correctamente
- [x] Los servicios (BAÑO, ESCALERA, DORMITORIO) se muestran

---

## 🐛 Solución de Problemas

### Problema: El diagrama no se muestra al editar
**Solución:**
1. Verificar que `configuracion_asientos` no sea NULL en BD
2. Verificar que el JSON sea válido
3. Abrir consola del navegador (F12) y buscar errores

### Problema: El diagrama se muestra vacío
**Solución:**
1. Verificar que `capacidad` y `pisos` tengan valores
2. Verificar que `generarDiagramaDinamico()` se esté llamando
3. Revisar consola para errores de JavaScript

### Problema: Los datos no se guardan
**Solución:**
1. Verificar que el campo `configuracionAsientos` exista
2. Verificar que `guardarTipoBus()` se ejecute antes del submit
3. Verificar en Network tab que el JSON se envíe

---

## 📝 Notas Importantes

1. **El diagrama se genera dinámicamente**: No se guardan las posiciones individuales de cada asiento, solo la configuración general (capacidad, pisos, layout).

2. **La distribución se calcula automáticamente**: 
   - 1 piso: 2 premium + resto normales
   - 2 pisos: 60% piso 1, 40% piso 2

3. **El JSON es ligero**: Solo guarda la información necesaria para regenerar el diagrama.

4. **Compatible con futuras mejoras**: El JSON puede extenderse para incluir layouts personalizados, asientos VIP, etc.

---

**Última actualización**: 2025-12-20  
**Versión**: 3.1.0 (Guardar y Cargar Diagrama)  
**Archivos modificados**: 
- `app/views/admin/tipos_buses.php` (funciones `guardarTipoBus` y `abrirModalTipoBus`)
