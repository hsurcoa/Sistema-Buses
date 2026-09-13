# 📊 INFORME DE ANÁLISIS PROFUNDO
## Implementación de Botón "Imprimir Ticket" Individual en Manifiesto de Pasajeros

**Fecha:** 24 de Diciembre de 2025  
**Hora:** 15:11  
**Analista:** Antigravity AI  
**Objetivo:** Agregar funcionalidad de impresión individual de tickets por pasajero en la tabla del manifiesto

---

## 🎯 1. RESUMEN EJECUTIVO

Se requiere agregar un **botón de impresión individual** para cada pasajero en la tabla del "Manifiesto de Pasajeros", permitiendo imprimir el ticket térmico de cada boleto vendido directamente desde la lista.

### **Alcance:**
- ✅ Agregar columna/botón "Imprimir Ticket" en la tabla
- ✅ Integrar con sistema de impresión existente (`impresion-ticket.js`)
- ✅ Mostrar botón solo para boletos con estado "VENDIDO"
- ✅ Mantener diseño coherente con AdminLTE

### **Beneficios:**
- 🎫 Reimpresión rápida de tickets individuales
- 📋 No necesidad de abrir modal de edición para imprimir
- ⚡ Flujo de trabajo más eficiente
- 👥 Mejor experiencia de usuario para el personal de ventas

---

## 🔍 2. ANÁLISIS DEL CÓDIGO EXISTENTE

### **2.1. Sistema de Impresión Actual**

**Archivo:** `assets/js/impresion-ticket.js`  
**Función Principal:** `imprimirTicket(datosBoleto, ventanaExistente = null)`

**Parámetros Requeridos:**
```javascript
const datosBoleto = {
    // Datos de la empresa
    empresaDireccion: 'Av. El Alto N° 777',
    empresaTelefono: '967885780',
    empresaEmail: 'atencioncliente@busdriver.com',
    empresaRuc: '20201563254',
    
    // Número de boleto
    numeroBoleto: '957 - 151405',
    
    // Datos del viaje
    fechaViaje: '2025-12-20',
    horaSalida: '05:00:00',
    placaBus: 'RG-2965',
    origen: 'Huacho',
    destino: 'Lima',
    asiento: '12',
    
    // Datos del pasajero
    nombrePasajero: 'HENRRY SURCO',
    documentoPasajero: '4961756',
    
    // Datos de venta
    fechaExpedicion: '2025-12-20',
    importe: 25.00
};
```

✅ **Estado:** Función completamente funcional y lista para usar

---

### **2.2. Estructura Actual de la Tabla**

**Archivo:** `app/views/ventas/venta_pasajes.php`  
**Líneas 790-806:**

```html
<table class="table table-hover table-striped align-middle mb-0" id="tabla_manifiesto_tab">
    <thead class="table-light">
        <tr>
            <th class="text-center">Asiento</th>
            <th>Documento</th>
            <th>Pasajero</th>
            <th>Celular</th>
            <th class="text-center">Estado</th>
            <th>Destino</th>
            <th class="text-end">Precio</th>
            <th class="text-center">Acciones</th> <!-- ❌ SOLO 2 BOTONES ACTUALES -->
        </tr>
    </thead>
    <tbody id="tbody_manifiesto_tab">
        <!-- Filas dinámicas -->
    </tbody>
</table>
```

**Botones Actuales en Columna "Acciones" (Líneas 1428-1433):**
```javascript
<td class="text-center">
    <button class="btn btn-warning btn-sm" onclick="editarBoleto(${p.id})" title="Editar">
        <i class="fas fa-edit"></i>
    </button>
    <button class="btn btn-danger btn-sm" onclick="eliminarBoleto(${p.id})" title="Eliminar">
        <i class="fas fa-trash"></i>
    </button>
</td>
```

---

### **2.3. Función de Carga de Datos**

**Archivo:** `app/views/ventas/venta_pasajes.php`  
**Líneas 1382-1446:**

```javascript
function cargarTablaPasajerosTab(viajeId) {
    $.getJSON(`${URLROOT}/ventas/listar_manifiesto/${viajeId}`, function(resp) {
        let pasajeros = resp.pasajeros || [];
        
        pasajeros.forEach(p => {
            let isVendido = (p.estado.toLowerCase() === 'vendido');
            
            // Datos disponibles en cada pasajero:
            // p.id, p.numero_asiento, p.numero_documento, 
            // p.nombres, p.apellidos, p.nombre_pasajero,
            // p.telefono, p.estado, p.destino, p.precio
            
            const tr = `...`; // Genera HTML de fila
            $tbody.append(tr);
        });
    });
}
```

**Datos Disponibles por Pasajero:**
- ✅ `p.id` - ID del boleto
- ✅ `p.numero_asiento` - Número de asiento
- ✅ `p.numero_documento` - Documento del pasajero
- ✅ `p.nombres` - Nombres del pasajero
- ✅ `p.apellidos` - Apellidos del pasajero
- ✅ `p.nombre_pasajero` - Nombre completo (alternativo)
- ✅ `p.telefono` - Celular
- ✅ `p.estado` - Estado del boleto (vendido/reservado)
- ✅ `p.destino` - Ciudad destino
- ✅ `p.precio` - Precio del boleto

**Datos FALTANTES para Impresión:**
- ❌ `fechaViaje` - Fecha del viaje
- ❌ `horaSalida` - Hora de salida
- ❌ `placaBus` - Placa del bus
- ❌ `origen` - Ciudad de origen
- ❌ `fechaExpedicion` - Fecha de venta del boleto
- ❌ `codigo_boleto` - Número de boleto formateado

---

## 🚨 3. PROBLEMA IDENTIFICADO

### **Datos Insuficientes en el Endpoint Actual**

El endpoint `/ventas/listar_manifiesto/${viajeId}` **NO devuelve todos los datos necesarios** para generar el ticket térmico.

**Comparación:**

| Campo Requerido | Disponible en Manifiesto | Fuente Alternativa |
|-----------------|--------------------------|-------------------|
| `numeroBoleto` | ❌ NO | Requiere `codigo_boleto` de BD |
| `fechaViaje` | ❌ NO | Disponible en `currentBusData` (JavaScript) |
| `horaSalida` | ❌ NO | Disponible en `currentBusData` (JavaScript) |
| `placaBus` | ❌ NO | Disponible en `currentBusData` (JavaScript) |
| `origen` | ❌ NO | Disponible en `currentBusData` (JavaScript) |
| `destino` | ✅ SÍ | `p.destino` |
| `asiento` | ✅ SÍ | `p.numero_asiento` |
| `nombrePasajero` | ✅ SÍ | `p.nombre_pasajero` |
| `documentoPasajero` | ✅ SÍ | `p.numero_documento` |
| `fechaExpedicion` | ❌ NO | Requiere `fecha_venta` de BD |
| `importe` | ✅ SÍ | `p.precio` |

---

## 💡 4. SOLUCIONES PROPUESTAS

### **SOLUCIÓN 1: Enriquecer Endpoint Backend** ⭐ RECOMENDADA

**Descripción:**  
Modificar el endpoint `/ventas/listar_manifiesto/${viajeId}` para que devuelva **todos los datos necesarios** para la impresión.

**Ventajas:**
- ✅ Datos completos y precisos desde la base de datos
- ✅ No depende de variables globales de JavaScript
- ✅ Fácil de mantener
- ✅ Escalable para futuras funcionalidades

**Desventajas:**
- ⚠️ Requiere modificar backend (PHP/SQL)
- ⚠️ Requiere modificar consulta SQL

**Complejidad:** MEDIA  
**Tiempo Estimado:** 20 minutos  
**Riesgo:** BAJO

**Cambios Requeridos:**

1. **Backend (PHP):**
   - Modificar consulta SQL en `RutaModel.php` o `VentaModel.php`
   - Agregar campos: `codigo_boleto`, `fecha_salida`, `hora_salida`, `bus_placa`, `ciudad_origen`, `fecha_venta`

2. **Frontend (JavaScript):**
   - Usar datos directamente del objeto `p` (pasajero)
   - Crear función `imprimirTicketIndividual(pasajeroData)`

---

### **SOLUCIÓN 2: Usar Variables Globales de JavaScript**

**Descripción:**  
Combinar datos del pasajero con datos del viaje almacenados en `currentBusData` (variable global).

**Ventajas:**
- ✅ No requiere modificar backend
- ✅ Implementación rápida
- ✅ Usa datos ya cargados en el frontend

**Desventajas:**
- ❌ Depende de variables globales (frágil)
- ❌ Falta `codigo_boleto` y `fecha_venta` (críticos)
- ❌ Puede fallar si `currentBusData` no está disponible

**Complejidad:** BAJA  
**Tiempo Estimado:** 10 minutos  
**Riesgo:** MEDIO-ALTO

---

### **SOLUCIÓN 3: Crear Endpoint Específico para Impresión**

**Descripción:**  
Crear un nuevo endpoint `/ventas/obtener_datos_ticket/${boletoId}` que devuelva **todos los datos** necesarios.

**Ventajas:**
- ✅ Endpoint dedicado y optimizado
- ✅ No afecta endpoint de manifiesto existente
- ✅ Datos completos y precisos

**Desventajas:**
- ⚠️ Requiere crear nuevo endpoint
- ⚠️ Llamada AJAX adicional por cada impresión

**Complejidad:** MEDIA  
**Tiempo Estimado:** 25 minutos  
**Riesgo:** BAJO

---

## 🎨 5. DISEÑO DE LA INTERFAZ

### **5.1. Propuesta de Columna de Acciones Ampliada**

**Opción A: Agregar Botón Verde "Imprimir"**

```html
<th class="text-center">Acciones</th>
```

```javascript
<td class="text-center">
    <!-- NUEVO: Botón Imprimir (solo si estado = vendido) -->
    ${isVendido ? `
        <button class="btn btn-success btn-sm me-1" 
                onclick="imprimirTicketIndividual(${p.id})" 
                title="Imprimir Ticket">
            <i class="fas fa-print"></i>
        </button>
    ` : ''}
    
    <!-- Botón Editar (existente) -->
    <button class="btn btn-warning btn-sm me-1" 
            onclick="editarBoleto(${p.id})" 
            title="Editar">
        <i class="fas fa-edit"></i>
    </button>
    
    <!-- Botón Eliminar (existente) -->
    <button class="btn btn-danger btn-sm" 
            onclick="eliminarBoleto(${p.id})" 
            title="Eliminar">
        <i class="fas fa-trash"></i>
    </button>
</td>
```

**Resultado Visual:**
- 🟢 Botón verde con icono de impresora (solo para vendidos)
- 🟡 Botón amarillo de edición
- 🔴 Botón rojo de eliminación

---

**Opción B: Botón con Texto "Imprimir Ticket"**

```javascript
<td class="text-center">
    ${isVendido ? `
        <button class="btn btn-success btn-sm me-1" 
                onclick="imprimirTicketIndividual(${p.id})">
            <i class="fas fa-print me-1"></i> Imprimir
        </button>
    ` : ''}
    <button class="btn btn-warning btn-sm me-1" onclick="editarBoleto(${p.id})">
        <i class="fas fa-edit"></i>
    </button>
    <button class="btn btn-danger btn-sm" onclick="eliminarBoleto(${p.id})">
        <i class="fas fa-trash"></i>
    </button>
</td>
```

---

### **5.2. Lógica Condicional**

**Regla de Negocio:**
- ✅ Mostrar botón "Imprimir" **SOLO** si `estado === 'vendido'`
- ❌ NO mostrar para reservas (no tiene sentido imprimir un ticket no pagado)

**Código:**
```javascript
let isVendido = (p.estado.toLowerCase() === 'vendido');

const btnImprimir = isVendido ? `
    <button class="btn btn-success btn-sm me-1" 
            onclick="imprimirTicketIndividual(${p.id})" 
            title="Imprimir Ticket">
        <i class="fas fa-print"></i>
    </button>
` : '';
```

---

## 🔧 6. IMPLEMENTACIÓN TÉCNICA

### **6.1. Algoritmo Completo (Solución 1 - Recomendada)**

```
INICIO

PASO 1: MODIFICAR BACKEND
│
├─ Archivo: app/models/RutaModel.php (o VentaModel.php)
├─ Función: listar_manifiesto($viaje_id)
├─ Acción: Modificar consulta SQL
│
├─ SQL ACTUAL:
│   SELECT 
│       b.id, b.numero_asiento, b.numero_documento,
│       c.nombres, c.apellidos, c.telefono,
│       b.estado, r.ciudad_destino AS destino, b.precio_final AS precio
│   FROM boletos b
│   LEFT JOIN clientes c ON b.cliente_id = c.id
│   LEFT JOIN viajes v ON b.viaje_id = v.id
│   LEFT JOIN rutas r ON v.ruta_id = r.id
│   WHERE b.viaje_id = ?
│
├─ SQL NUEVO (AGREGAR CAMPOS):
│   SELECT 
│       b.id, b.numero_asiento, b.numero_documento, b.codigo_boleto,
│       b.fecha_venta, b.precio_final AS precio,
│       c.nombres, c.apellidos, c.telefono,
│       b.estado,
│       r.ciudad_origen AS origen, r.ciudad_destino AS destino,
│       v.fecha_salida, v.hora_salida,
│       bu.placa AS bus_placa
│   FROM boletos b
│   LEFT JOIN clientes c ON b.cliente_id = c.id
│   LEFT JOIN viajes v ON b.viaje_id = v.id
│   LEFT JOIN rutas r ON v.ruta_id = r.id
│   LEFT JOIN buses bu ON v.bus_id = bu.id
│   WHERE b.viaje_id = ?
│
└─ Resultado: Endpoint devuelve datos completos

PASO 2: MODIFICAR FRONTEND (HTML)
│
├─ Archivo: app/views/ventas/venta_pasajes.php
├─ Línea: ~1427 (dentro de cargarTablaPasajerosTab)
├─ Acción: Agregar botón de impresión
│
├─ CÓDIGO NUEVO:
│   const btnImprimir = isVendido ? `
│       <button class="btn btn-success btn-sm me-1" 
│               onclick="imprimirTicketIndividual(${p.id}, '${JSON.stringify(p).replace(/'/g, "\\'")})" 
│               title="Imprimir Ticket">
│           <i class="fas fa-print"></i>
│       </button>
│   ` : '';
│
│   const tr = `
│       <tr class="${isVendido ? 'fila-manifiesto' : 'fila-reserva'}">
│           ...
│           <td class="text-center">
│               ${btnImprimir}
│               <button class="btn btn-warning btn-sm me-1" ...>...</button>
│               <button class="btn btn-danger btn-sm" ...>...</button>
│           </td>
│       </tr>
│   `;
│
└─ Resultado: Botón visible en tabla

PASO 3: CREAR FUNCIÓN JAVASCRIPT
│
├─ Archivo: app/views/ventas/venta_pasajes.php
├─ Ubicación: Después de cargarTablaPasajerosTab() (~línea 1447)
├─ Acción: Crear función de impresión
│
├─ CÓDIGO NUEVO:
│   function imprimirTicketIndividual(boletoId, pasajeroData) {
│       // Parsear datos del pasajero
│       const p = JSON.parse(pasajeroData);
│       
│       // Construir objeto para impresión
│       const datosBoleto = {
│           // Datos de empresa (hardcoded o desde config)
│           empresaDireccion: 'Terminal BusDriver',
│           empresaTelefono: '967885780',
│           empresaEmail: 'atencioncliente@busdriver.com',
│           empresaRuc: '20201563254',
│           
│           // Datos del boleto
│           numeroBoleto: p.codigo_boleto || 'SIN CÓDIGO',
│           
│           // Datos del viaje
│           fechaViaje: p.fecha_salida,
│           horaSalida: p.hora_salida,
│           placaBus: p.bus_placa || 'SIN ASIGNAR',
│           origen: p.origen,
│           destino: p.destino,
│           asiento: p.numero_asiento,
│           
│           // Datos del pasajero
│           nombrePasajero: p.nombre_pasajero || (p.nombres + ' ' + p.apellidos),
│           documentoPasajero: p.numero_documento,
│           
│           // Datos de venta
│           fechaExpedicion: p.fecha_venta,
│           importe: parseFloat(p.precio)
│       };
│       
│       // Llamar función de impresión (ya existe en impresion-ticket.js)
│       imprimirTicket(datosBoleto);
│   }
│
└─ Resultado: Función lista para usar

PASO 4: PRUEBAS
│
├─ Cargar página de venta de pasajes
├─ Seleccionar un viaje
├─ Ir al tab "Manifiesto de Pasajeros"
├─ Verificar que aparezca botón verde de impresión en boletos vendidos
├─ Hacer clic en botón de impresión
├─ Verificar que se abra ventana de impresión con ticket correcto
│
└─ Resultado: Funcionalidad operativa

FIN
```

---

## 📊 7. COMPARACIÓN DE SOLUCIONES

| Criterio | Solución 1: Backend | Solución 2: Variables JS | Solución 3: Endpoint Nuevo |
|----------|---------------------|--------------------------|---------------------------|
| **Datos Completos** | ✅ SÍ | ❌ NO (faltan 2 campos) | ✅ SÍ |
| **Precisión** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Complejidad** | ⭐⭐⭐ | ⭐ | ⭐⭐⭐ |
| **Tiempo** | 20 min | 10 min | 25 min |
| **Riesgo** | ⭐ | ⭐⭐⭐⭐ | ⭐ |
| **Mantenibilidad** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Escalabilidad** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Llamadas AJAX** | 0 adicionales | 0 adicionales | 1 por impresión |

**Leyenda:** ⭐ = Muy Malo | ⭐⭐⭐ = Aceptable | ⭐⭐⭐⭐⭐ = Excelente

---

## 🎨 8. MOCKUP VISUAL

### **Estado Actual vs. Propuesto**

**ANTES (Columna Acciones):**
```
| Acciones          |
|-------------------|
| [✏️] [🗑️]        |
```

**DESPUÉS (Columna Acciones):**
```
| Acciones                |
|-------------------------|
| [🖨️] [✏️] [🗑️]  (Vendido)  |
| [✏️] [🗑️]        (Reserva)   |
```

**Colores:**
- 🟢 Verde: Imprimir (solo vendidos)
- 🟡 Amarillo: Editar
- 🔴 Rojo: Eliminar

---

## 📋 9. CHECKLIST DE IMPLEMENTACIÓN

### **Backend (PHP/SQL):**
- [ ] Modificar consulta SQL en `listar_manifiesto()`
- [ ] Agregar campos: `codigo_boleto`, `fecha_venta`, `fecha_salida`, `hora_salida`, `bus_placa`, `ciudad_origen`
- [ ] Probar endpoint con Postman o navegador
- [ ] Verificar que JSON devuelva todos los campos

### **Frontend (JavaScript):**
- [ ] Modificar función `cargarTablaPasajerosTab()`
- [ ] Agregar lógica condicional para botón de impresión
- [ ] Crear función `imprimirTicketIndividual(boletoId, pasajeroData)`
- [ ] Mapear datos del pasajero a objeto `datosBoleto`
- [ ] Llamar a `imprimirTicket(datosBoleto)`

### **Pruebas:**
- [ ] Verificar que botón aparezca solo en boletos vendidos
- [ ] Verificar que NO aparezca en reservas
- [ ] Probar impresión de ticket
- [ ] Verificar que todos los datos sean correctos
- [ ] Probar en diferentes navegadores (Chrome, Firefox, Edge)
- [ ] Verificar que no afecte funcionalidad de editar/eliminar

---

## 🚨 10. RIESGOS Y MITIGACIONES

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Datos faltantes en endpoint | Media | Alto | Usar Solución 1 (modificar backend) |
| Botón aparece en reservas | Baja | Bajo | Validación condicional `isVendido` |
| Ticket imprime datos incorrectos | Media | Alto | Mapeo cuidadoso de campos |
| Ventana emergente bloqueada | Media | Medio | Instrucciones al usuario |
| Conflicto con botones existentes | Baja | Bajo | Usar clases `me-1` para espaciado |

---

## 🎓 11. CONCLUSIÓN Y RECOMENDACIÓN

### **Análisis Final:**

La implementación del botón "Imprimir Ticket" individual es:

✅ **VIABLE** - Sistema de impresión ya existe y funciona  
✅ **RECOMENDABLE** - Mejora significativa en UX  
✅ **BAJO RIESGO** - Cambios aislados y controlados  
✅ **RÁPIDO** - Implementación en ~30 minutos (con backend)  

### **Solución Recomendada:**

**⭐ SOLUCIÓN 1: Enriquecer Endpoint Backend**

**Razones:**
1. Datos completos y precisos desde la base de datos
2. No depende de variables globales frágiles
3. Fácil de mantener y escalar
4. Reutilizable para futuras funcionalidades
5. Evita llamadas AJAX adicionales

### **Próximos Pasos:**

1. ✅ **Aprobar** este informe y solución propuesta
2. ✅ **Modificar** consulta SQL en backend
3. ✅ **Implementar** botón en frontend
4. ✅ **Crear** función JavaScript de impresión
5. ✅ **Probar** con datos reales

---

## 📚 12. REFERENCIAS

- **Sistema de Impresión:** `assets/js/impresion-ticket.js`
- **Función de Carga:** `cargarTablaPasajerosTab()` (línea 1382)
- **Endpoint Backend:** `/ventas/listar_manifiesto/${viajeId}`
- **Documentación AdminLTE:** https://adminlte.io/themes/v3/

---

**Fin del Informe de Análisis**

**Estado:** ✅ LISTO PARA IMPLEMENTACIÓN  
**Aprobación Requerida:** SÍ  
**Siguiente Acción:** Esperar confirmación del usuario para proceder
