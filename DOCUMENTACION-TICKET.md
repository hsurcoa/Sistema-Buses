# 🎫 Sistema de Impresión de Tickets Térmicos - BusDriver Transporte

## 📋 Descripción General

Sistema completo de impresión de tickets térmicos optimizado para papel de 80mm, diseñado específicamente para sistemas POS (Punto de Venta) de empresas de transporte terrestre.

## ✨ Características Principales

- ✅ **Optimizado para papel térmico de 80mm** (300-320px de ancho)
- ✅ **Generación automática de código QR** para validación
- ✅ **Conversión de números a letras** (formato peruano)
- ✅ **Estilos @media print** optimizados para impresoras térmicas
- ✅ **JavaScript puro** - Sin dependencias adicionales (excepto QRCode.js)
- ✅ **Diseño profesional** idéntico a tickets reales
- ✅ **Fácil integración** con cualquier sistema web

## 📦 Archivos del Sistema

```
venta-pasajes/
├── assets/
│   └── js/
│       └── impresion-ticket.js    # Función principal de impresión
├── demo-ticket.html               # Página de demostración
└── DOCUMENTACION-TICKET.md        # Este archivo
```

## 🚀 Instalación Rápida

### 1. Incluir la librería QRCode.js

Agrega esta línea en el `<head>` de tu página:

```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
```

### 2. Incluir el archivo de impresión

```html
<script src="assets/js/impresion-ticket.js"></script>
```

### 3. ¡Listo para usar!

```javascript
imprimirTicket(datosBoleto);
```

## 📖 Uso Básico

### Estructura del Objeto de Datos

```javascript
const datosBoleto = {
    // Datos de la empresa
    empresaDireccion: 'Av. El Alto N° 777',
    empresaTelefono: '967885780',
    empresaEmail: 'atencioncliente@busdriver.com',
    empresaRuc: '20201563254',
    
    // Número de boleto
    numeroBoleto: '100 - 000016',
    
    // Datos del viaje
    fechaViaje: '2021-04-30',        // Formato: YYYY-MM-DD
    horaSalida: '05:00:00',          // Formato: HH:MM:SS
    placaBus: 'RG-2965',
    origen: 'Huacho',
    destino: 'Lima',
    asiento: '45',
    
    // Datos del pasajero
    nombrePasajero: 'DANIEL PACHAS MAGALLANES',
    documentoPasajero: '45484645',
    
    // Datos de venta
    fechaExpedicion: '2021-04-29',   // Formato: YYYY-MM-DD
    importe: 25.00                   // Número decimal
};
```

### Llamar la Función

```javascript
// Forma básica
imprimirTicket(datosBoleto);

// Desde un botón
document.getElementById('btnImprimir').addEventListener('click', function() {
    imprimirTicket(datosBoleto);
});

// Desde el modal de "¡Boleto Emitido!"
function imprimirDesdeModal() {
    const datos = obtenerDatosVenta(); // Tu función que obtiene los datos
    imprimirTicket(datos);
}
```

## 🔧 Integración con tu Sistema

### Ejemplo: Modal de Venta Completada

```html
<!-- Modal de Boleto Emitido -->
<div class="modal" id="modalBoletoEmitido">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>¡Boleto Emitido!</h5>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle text-success" style="font-size: 64px;"></i>
                <p>Venta realizada exitosamente.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="imprimirTicketVenta()">
                    🖨️ Imprimir Ticket
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Incluir QRCode.js
const qrScript = document.createElement('script');
qrScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
document.head.appendChild(qrScript);

// Incluir el sistema de impresión
const printScript = document.createElement('script');
printScript.src = 'assets/js/impresion-ticket.js';
document.head.appendChild(printScript);

// Función para imprimir desde el modal
function imprimirTicketVenta() {
    // Obtener datos de la venta actual (ejemplo)
    const datosVenta = {
        empresaDireccion: 'Av. El Alto N° 777',
        empresaTelefono: '967885780',
        empresaEmail: 'atencioncliente@busdriver.com',
        empresaRuc: '20201563254',
        numeroBoleto: document.getElementById('numeroBoleto').value,
        fechaViaje: document.getElementById('fechaViaje').value,
        horaSalida: document.getElementById('horaSalida').value,
        placaBus: document.getElementById('placaBus').value,
        origen: document.getElementById('origen').value,
        destino: document.getElementById('destino').value,
        asiento: document.getElementById('asiento').value,
        nombrePasajero: document.getElementById('nombrePasajero').value,
        documentoPasajero: document.getElementById('documentoPasajero').value,
        fechaExpedicion: new Date().toISOString().split('T')[0],
        importe: parseFloat(document.getElementById('importe').value)
    };
    
    imprimirTicket(datosVenta);
}
</script>
```

### Ejemplo: Integración con PHP/AJAX

```javascript
// Después de completar una venta exitosamente
$.ajax({
    url: 'ventas/guardar_venta',
    method: 'POST',
    data: formData,
    success: function(response) {
        if (response.success) {
            // Mostrar modal de éxito
            $('#modalBoletoEmitido').modal('show');
            
            // Preparar datos para impresión
            const datosTicket = {
                empresaDireccion: 'Av. El Alto N° 777',
                empresaTelefono: '967885780',
                empresaEmail: 'atencioncliente@busdriver.com',
                empresaRuc: '20201563254',
                numeroBoleto: response.numeroBoleto,
                fechaViaje: response.fechaViaje,
                horaSalida: response.horaSalida,
                placaBus: response.placaBus,
                origen: response.origen,
                destino: response.destino,
                asiento: response.asiento,
                nombrePasajero: response.nombrePasajero,
                documentoPasajero: response.documentoPasajero,
                fechaExpedicion: response.fechaExpedicion,
                importe: response.importe
            };
            
            // Guardar datos para impresión posterior
            window.datosTicketActual = datosTicket;
            
            // Opcional: Imprimir automáticamente
            // imprimirTicket(datosTicket);
        }
    }
});

// Función del botón "Imprimir Ticket"
function imprimirTicketActual() {
    if (window.datosTicketActual) {
        imprimirTicket(window.datosTicketActual);
    } else {
        alert('No hay datos de ticket disponibles');
    }
}
```

## 🎨 Personalización

### Modificar Colores

Edita el archivo `impresion-ticket.js`, busca la sección de estilos:

```css
.empresa-nombre {
    color: #1e88e5;  /* Cambia este color */
}

.campo-destacado .campo-valor {
    color: #1e88e5;  /* Color del número de asiento */
}
```

### Modificar Logo

Busca la sección del logo en el HTML generado:

```javascript
<svg class="logo" viewBox="0 0 200 80" xmlns="http://www.w3.org/2000/svg">
    <!-- Reemplaza este SVG con tu logo -->
</svg>
```

O usa una imagen:

```javascript
<img src="ruta/a/tu/logo.png" class="logo" alt="Logo">
```

### Modificar Tamaño de Fuente

```css
body {
    font-size: 12px;  /* Tamaño base */
}

.empresa-nombre {
    font-size: 16px;  /* Nombre de empresa */
}

.numero-boleto-grande {
    font-size: 18px;  /* Número de boleto */
}
```

## 📱 Funciones Auxiliares

### numeroALetras(numero)

Convierte un número decimal a su representación en letras (formato peruano).

```javascript
numeroALetras(25.00);
// Retorna: "VEINTICINCO CON 00/100 SOLES"

numeroALetras(150.50);
// Retorna: "CIENTO CINCUENTA CON 50/100 SOLES"
```

### formatearFecha(fecha)

Convierte una fecha a formato DD/MM/YYYY.

```javascript
formatearFecha('2021-04-30');
// Retorna: "30/04/2021"
```

### formatearHora(hora)

Formatea una hora a HH:MM.

```javascript
formatearHora('05:00:00');
// Retorna: "05:00"
```

## 🖨️ Configuración de Impresora

### Configuración Recomendada

1. **Tipo de papel**: Térmico 80mm
2. **Ancho de impresión**: 80mm (aprox. 300-320px)
3. **Márgenes**: 0mm (configurado en @media print)
4. **Orientación**: Vertical (Portrait)
5. **Escala**: 100%

### Configurar en el Navegador

**Chrome/Edge:**
1. Ctrl + P (Abrir diálogo de impresión)
2. Destino: Seleccionar impresora térmica
3. Márgenes: Ninguno
4. Escala: 100%
5. Opciones avanzadas: Desactivar encabezados y pies de página

**Firefox:**
1. Ctrl + P
2. Configuración de página → Márgenes: 0
3. Desactivar "Imprimir encabezados y pies de página"

## 🔍 Código QR

El código QR se genera automáticamente con la siguiente información:

```
BOLETO:[numeroBoleto]|PASAJERO:[nombrePasajero]|ASIENTO:[asiento]|FECHA:[fechaViaje]
```

Ejemplo:
```
BOLETO:100-000016|PASAJERO:DANIEL PACHAS MAGALLANES|ASIENTO:45|FECHA:2021-04-30
```

### Personalizar Datos del QR

Edita la función `imprimirTicket()`:

```javascript
const datosQR = `BOLETO:${datosBoleto.numeroBoleto}|PASAJERO:${datosBoleto.nombrePasajero}|ASIENTO:${datosBoleto.asiento}|FECHA:${datosBoleto.fechaViaje}`;
```

O usa una URL de validación:

```javascript
const datosQR = `https://tusistema.com/validar/${datosBoleto.numeroBoleto}`;
```

## 🐛 Solución de Problemas

### El QR no se genera

**Problema**: El código QR no aparece en el ticket.

**Solución**:
1. Verifica que QRCode.js esté cargado:
```javascript
if (typeof QRCode === 'undefined') {
    console.error('QRCode.js no está cargado');
}
```

2. Aumenta el tiempo de espera antes de imprimir:
```javascript
setTimeout(function() {
    ventanaImpresion.print();
}, 1000); // Aumentar de 500ms a 1000ms
```

### La ventana de impresión no se abre

**Problema**: El navegador bloquea ventanas emergentes.

**Solución**:
1. Permitir ventanas emergentes para tu dominio
2. Mostrar mensaje al usuario:
```javascript
if (!ventanaImpresion) {
    alert('Por favor, permite las ventanas emergentes para imprimir el ticket');
    return;
}
```

### El ticket se corta en la impresión

**Problema**: El contenido se corta o no cabe en el papel.

**Solución**:
1. Ajustar el ancho en los estilos:
```css
body {
    width: 280px; /* Reducir si es necesario */
}
```

2. Reducir tamaños de fuente:
```css
body {
    font-size: 11px; /* En lugar de 12px */
}
```

### Los estilos no se aplican

**Problema**: El ticket se imprime sin estilos.

**Solución**:
Asegúrate de que los estilos estén dentro de `<style>` en el HTML generado, no en un archivo CSS externo.

## 📊 Estructura del Ticket

```
┌─────────────────────────────┐
│         LOGO EMPRESA        │
│    BUSDRIVER TRANSPORTE     │
│      Dirección, Tel, RUC    │
│      BOLETO DE VIAJE        │
│        100 - 000016         │
├─────────────────────────────┤
│ Fecha  │ Hora   │ Placa     │
│ Origen │ Destino│ Asiento   │
├─────────────────────────────┤
│ Nombre Pasajero             │
│ Documento Cliente           │
├─────────────────────────────┤
│ Fecha Expedición            │
│ Importe: S/ 25.00           │
│ SON: VEINTICINCO...         │
├─────────────────────────────┤
│       [CÓDIGO QR]           │
│                             │
│  Gracias por su preferencia │
└─────────────────────────────┘
```

## 🧪 Pruebas

### Probar con Datos de Ejemplo

```javascript
// Usar el objeto de ejemplo incluido
imprimirTicket(datosBoletoEjemplo);
```

### Probar con Datos Personalizados

```javascript
const miPrueba = {
    empresaDireccion: 'Mi Dirección',
    empresaTelefono: '999888777',
    empresaEmail: 'test@test.com',
    empresaRuc: '20123456789',
    numeroBoleto: 'TEST-001',
    fechaViaje: '2024-12-25',
    horaSalida: '10:00:00',
    placaBus: 'TEST-123',
    origen: 'Ciudad A',
    destino: 'Ciudad B',
    asiento: '1',
    nombrePasajero: 'PRUEBA TEST',
    documentoPasajero: '00000000',
    fechaExpedicion: '2024-12-20',
    importe: 100.00
};

imprimirTicket(miPrueba);
```

## 📄 Licencia

Este código es de uso libre para el proyecto BusDriver Transporte.

## 👨‍💻 Soporte

Para soporte técnico o consultas:
- Email: soporte@busdriver.com
- Documentación: Este archivo

## 🔄 Historial de Versiones

### v1.0.0 (2024-12-20)
- ✅ Versión inicial
- ✅ Generación de tickets térmicos 80mm
- ✅ Código QR automático
- ✅ Conversión de números a letras
- ✅ Estilos optimizados para impresión
- ✅ Página de demostración incluida

---

**¡Listo para usar!** 🎉

Para comenzar, abre `demo-ticket.html` en tu navegador y prueba la funcionalidad.
