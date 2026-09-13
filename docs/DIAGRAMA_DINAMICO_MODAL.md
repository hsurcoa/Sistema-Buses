# ✅ Diagrama Dinámico Implementado en Modal de Tipos de Buses

## 🎯 Cambios Realizados

He eliminado las **imágenes estáticas** (`piso01.jpg`, `piso02.jpg`) y ahora el diagrama se **dibuja dinámicamente en tiempo real** basándose en los datos que ingresas en el formulario.

---

## 🚀 Cómo Funciona Ahora

### Antes (❌ Imágenes Estáticas)
```html
<!-- Mostraba imágenes fijas -->
<img src="/images/bus-diagrams/piso01.jpg">
<img src="/images/bus-diagrams/piso02.jpg">
```

### Ahora (✅ Diagrama Dinámico)
```javascript
// Se genera automáticamente según los datos
Capacidad: 43 asientos
Pisos: 1
→ Genera: 2 premium + 41 normales en 11 filas
```

---

## 📋 Funcionamiento Paso a Paso

### 1. **Ingresas los Datos**
```
Nombre: Bus Imperial
Capacidad Total: 43
Número de Pisos: 1 Piso
```

### 2. **El Sistema Calcula Automáticamente**
```javascript
// Cálculo para 1 piso:
Premium: 2 asientos (cabina)
Normales: 43 - 2 = 41 asientos
Filas: ceil(41 / 4) = 11 filas
Layout: 2-2 (2 asientos - pasillo - 2 asientos)
```

### 3. **Se Dibuja el Diagrama**
- ✅ Cabina con volante y 2 asientos premium (naranja)
- ✅ 11 filas de 4 asientos cada una (azul)
- ✅ Pasillo central entre asientos
- ✅ Sección trasera con BAÑO, ESCALERA, DORMITORIO

---

## 🎨 Características del Diagrama Dinámico

### Visual
- **Cabina del conductor** con volante estilizado
- **Asientos premium** en color naranja (asientos 1 y 2)
- **Asientos normales** en color azul
- **Pasillo central** con líneas punteadas
- **Servicios traseros** (BAÑO, ESCALERA, DORMITORIO)

### Interactivo
- Se actualiza **automáticamente** al cambiar la capacidad
- Se actualiza **automáticamente** al cambiar el número de pisos
- Muestra **tabs** para cambiar entre pisos (si hay 2 pisos)
- **Numeración secuencial** de asientos

---

## 📊 Ejemplos de Cálculo

### Ejemplo 1: Bus de 43 asientos (1 piso)
```
Entrada:
- Capacidad: 43
- Pisos: 1

Cálculo:
- Premium: 2
- Normales: 41
- Filas: 11

Resultado:
- Asientos 1-2: Premium (cabina)
- Asientos 3-43: Normales (11 filas × 4 asientos)
```

### Ejemplo 2: Bus de 49 asientos (2 pisos)
```
Entrada:
- Capacidad: 49
- Pisos: 2

Cálculo:
Piso 1 (60%):
- Total: 30 asientos
- Premium: 2
- Normales: 28
- Filas: 7

Piso 2 (40%):
- Total: 19 asientos
- Normales: 19
- Filas: 5

Resultado:
- Piso 1: Asientos 1-30 (2 premium + 28 normales)
- Piso 2: Asientos 31-49 (19 normales)
```

### Ejemplo 3: Bus de 60 asientos (2 pisos)
```
Entrada:
- Capacidad: 60
- Pisos: 2

Cálculo:
Piso 1 (60%):
- Total: 36 asientos
- Premium: 2
- Normales: 34
- Filas: 9

Piso 2 (40%):
- Total: 24 asientos
- Normales: 24
- Filas: 6

Resultado:
- Piso 1: Asientos 1-36
- Piso 2: Asientos 37-60
```

---

## 🔧 Código Implementado

### Función Principal
```javascript
function generarDiagramaDinamico() {
    const capacidad = parseInt(document.getElementById('capacidadTipoBus').value);
    const pisos = parseInt(document.getElementById('pisosTipoBus').value);
    
    // Calcular distribución
    const config = calcularDistribucion(capacidad, pisos);
    
    // Generar HTML
    const html = generarHTMLPiso(config, piso, capacidad);
    
    // Renderizar
    document.getElementById('vistaPreviaBus').innerHTML = html;
}
```

### Cálculo de Distribución
```javascript
function calcularDistribucion(capacidad, pisos) {
    if (pisos === 1) {
        return {
            piso1: {
                premium: 2,
                normales: capacidad - 2,
                filas: Math.ceil((capacidad - 2) / 4)
            }
        };
    } else {
        const asientosPiso1 = Math.ceil(capacidad * 0.6);
        const asientosPiso2 = capacidad - asientosPiso1;
        
        return {
            piso1: {
                premium: 2,
                normales: asientosPiso1 - 2,
                filas: Math.ceil((asientosPiso1 - 2) / 4)
            },
            piso2: {
                normales: asientosPiso2,
                filas: Math.ceil(asientosPiso2 / 4)
            }
        };
    }
}
```

---

## 🎯 Ventajas de la Implementación

| Característica | Antes | Ahora |
|----------------|-------|-------|
| **Flexibilidad** | Solo 2 imágenes fijas | Cualquier capacidad |
| **Precisión** | Imagen genérica | Cálculo exacto |
| **Actualización** | Manual (editar imagen) | Automática |
| **Mantenimiento** | Difícil | Fácil |
| **Escalabilidad** | Limitada | Ilimitada |

---

## 📝 Cómo Probar

1. **Abrir el modal**:
   - Ir a "Tipos de Buses"
   - Click en "Agregar Nuevo Tipo"

2. **Ingresar datos**:
   ```
   Nombre: Bus Imperial
   Capacidad: 43
   Pisos: 1 Piso
   ```

3. **Ver el resultado**:
   - El diagrama se dibuja automáticamente
   - Muestra 2 asientos premium + 41 normales
   - 11 filas de 4 asientos cada una

4. **Cambiar capacidad**:
   ```
   Capacidad: 60
   Pisos: 2 Pisos
   ```
   - El diagrama se actualiza instantáneamente
   - Muestra piso 1 (36 asientos) y piso 2 (24 asientos)
   - Tabs para cambiar entre pisos

---

## 🎨 Estilos CSS Implementados

- `.bus-preview-dinamico` - Contenedor del bus
- `.preview-cabin` - Cabina del conductor
- `.preview-steering` - Volante
- `.preview-seat` - Asientos individuales
- `.preview-aisle` - Pasillo central
- `.preview-services` - Servicios traseros
- `.preview-floor-badge` - Indicador de piso

---

## ✅ Resumen

**Antes:**
- Imágenes estáticas de piso01.jpg y piso02.jpg
- No reflejaban la capacidad real ingresada
- Difícil de mantener

**Ahora:**
- ✅ Diagrama generado dinámicamente
- ✅ Se actualiza en tiempo real
- ✅ Refleja exactamente la capacidad ingresada
- ✅ Cálculo automático de filas y distribución
- ✅ Soporte para 1 o 2 pisos
- ✅ Numeración secuencial de asientos
- ✅ Visual profesional y preciso

---

**Última actualización**: 2025-12-20  
**Versión**: 3.0.0 (Diagrama Dinámico en Modal)  
**Archivo modificado**: `app/views/admin/tipos_buses.php`
