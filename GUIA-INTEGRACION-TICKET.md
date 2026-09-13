# 🎫 Guía de Integración - Sistema de Impresión de Tickets

## ✅ Archivos Creados

1. **`assets/js/impresion-ticket.js`** - Sistema completo de impresión de tickets térmicos
2. **`demo-ticket.html`** - Página de demostración interactiva
3. **`DOCUMENTACION-TICKET.md`** - Documentación completa del sistema

## ✅ Modificaciones Realizadas

### 1. Header (`app/views/layouts/header.php`)
- ✅ Agregada librería QRCode.js

### 2. Footer (`app/views/layouts/footer.php`)
- ✅ Incluido script `impresion-ticket.js`

### 3. Crear Ruta (`app/views/ventas/crear_ruta.php`)
- ✅ Modificada función `procesarVenta()` para integrar impresión
- ✅ Agregada función `imprimirTicketVenta()`
- ✅ Variable global `ultimoBoletoEmitido` para almacenar datos

## 📋 Cómo Funciona

### Flujo de Venta e Impresión

```
1. Usuario completa venta → procesarVenta()
2. Se generan datos del boleto → ultimoBoletoEmitido
3. SweetAlert muestra confirmación con botón "Imprimir Ticket"
4. Usuario hace clic → imprimirTicketVenta()
5. Se abre ventana emergente con ticket térmico
6. Se genera código QR automáticamente
7. Se lanza diálogo de impresión del navegador
```

## 🔧 Próximos Pasos de Integración

### 1. Obtener Datos Reales de la Ruta

Actualmente, la función `procesarVenta()` usa datos de ejemplo. Debes modificarla para obtener los datos reales de la ruta seleccionada:

```javascript
function procesarVenta() {
    // ... validaciones ...
    
    // MODIFICAR ESTO: Obtener datos reales del contexto
    const datosRuta = obtenerDatosRutaActual(); // Tu función
    
    ultimoBoletoEmitido = {
        // Datos de la empresa (pueden venir de configuración)
        empresaDireccion: datosRuta.empresaDireccion || 'Av. El Alto N° 777',
        empresaTelefono: datosRuta.empresaTelefono || '967885780',
        empresaEmail: datosRuta.empresaEmail || 'atencioncliente@busdriver.com',
        empresaRuc: datosRuta.empresaRuc || '20201563254',
        
        // Número de boleto (debe venir del backend)
        numeroBoleto: datosRuta.numeroBoleto,
        
        // Datos del viaje (de la ruta seleccionada)
        fechaViaje: datosRuta.fechaViaje,
        horaSalida: datosRuta.horaSalida,
        placaBus: datosRuta.placaBus,
        origen: datosRuta.origen,
        destino: datosRuta.destino,
        asiento: asientoSeleccionado,
        
        // Datos del pasajero (del formulario)
        nombrePasajero: `${nombres} ${apellidos}`.toUpperCase(),
        documentoPasajero: documento,
        
        // Datos de venta
        fechaExpedicion: new Date().toISOString().split('T')[0],
        importe: datosRuta.precioBase || 25.00
    };
    
    // ... resto del código ...
}
```

### 2. Guardar Venta en Base de Datos

Antes de mostrar el SweetAlert, debes guardar la venta en la base de datos:

```javascript
function procesarVenta() {
    // ... validaciones ...
    
    // Preparar datos para enviar al backend
    const datosVenta = {
        ruta_id: rutaActualId,
        asiento: asientoSeleccionado,
        nombre_pasajero: nombres,
        apellido_pasajero: apellidos,
        documento_pasajero: documento,
        importe: precioBase
    };
    
    // Enviar al backend
    $.ajax({
        url: `${URLROOT}/ventas/guardar_venta`,
        type: 'POST',
        data: datosVenta,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Preparar datos del boleto con información del backend
                ultimoBoletoEmitido = {
                    empresaDireccion: response.empresaDireccion,
                    empresaTelefono: response.empresaTelefono,
                    empresaEmail: response.empresaEmail,
                    empresaRuc: response.empresaRuc,
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
                
                // Mostrar SweetAlert con opción de imprimir
                Swal.fire({
                    icon: 'success',
                    title: '¡Boleto Emitido!',
                    html: `
                        <p class="mb-3">Venta realizada exitosamente.</p>
                        <div class="alert alert-info">
                            <strong>Boleto:</strong> ${response.numeroBoleto}<br>
                            <strong>Pasajero:</strong> ${response.nombrePasajero}<br>
                            <strong>Asiento:</strong> #${response.asiento}
                        </div>
                    `,
                    confirmButtonText: '🖨️ Imprimir Ticket',
                    showCancelButton: true,
                    cancelButtonText: 'Cerrar',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#94a3b8'
                }).then((result) => {
                    if (result.isConfirmed) {
                        imprimirTicketVenta();
                    }
                    cerrarModalVenta();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'No se pudo completar la venta'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al guardar venta:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: 'No se pudo conectar con el servidor'
            });
        }
    });
}
```

### 3. Crear el Controlador Backend

Crea el método `guardar_venta` en tu controlador de Ventas:

```php
// app/controllers/Ventas.php

public function guardar_venta() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Sanitizar datos
        $datos = [
            'ruta_id' => filter_input(INPUT_POST, 'ruta_id', FILTER_SANITIZE_NUMBER_INT),
            'asiento' => filter_input(INPUT_POST, 'asiento', FILTER_SANITIZE_NUMBER_INT),
            'nombre_pasajero' => filter_input(INPUT_POST, 'nombre_pasajero', FILTER_SANITIZE_STRING),
            'apellido_pasajero' => filter_input(INPUT_POST, 'apellido_pasajero', FILTER_SANITIZE_STRING),
            'documento_pasajero' => filter_input(INPUT_POST, 'documento_pasajero', FILTER_SANITIZE_STRING),
            'importe' => filter_input(INPUT_POST, 'importe', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
        ];
        
        // Generar número de boleto
        $numeroBoleto = $this->generarNumeroBoleto();
        
        // Guardar en base de datos
        $ventaId = $this->ventaModel->guardarVenta($datos, $numeroBoleto);
        
        if ($ventaId) {
            // Obtener datos completos de la venta para el ticket
            $datosCompletos = $this->ventaModel->obtenerDatosVenta($ventaId);
            
            // Retornar datos para el ticket
            echo json_encode([
                'success' => true,
                'numeroBoleto' => $numeroBoleto,
                'empresaDireccion' => 'Av. El Alto N° 777',
                'empresaTelefono' => '967885780',
                'empresaEmail' => 'atencioncliente@busdriver.com',
                'empresaRuc' => '20201563254',
                'fechaViaje' => $datosCompletos->fecha_viaje,
                'horaSalida' => $datosCompletos->hora_salida,
                'placaBus' => $datosCompletos->placa_bus,
                'origen' => $datosCompletos->origen,
                'destino' => $datosCompletos->destino,
                'asiento' => $datos['asiento'],
                'nombrePasajero' => strtoupper($datos['nombre_pasajero'] . ' ' . $datos['apellido_pasajero']),
                'documentoPasajero' => $datos['documento_pasajero'],
                'fechaExpedicion' => date('Y-m-d'),
                'importe' => $datos['importe']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar la venta'
            ]);
        }
    }
}

private function generarNumeroBoleto() {
    // Obtener el último número de boleto de la base de datos
    $ultimoNumero = $this->ventaModel->obtenerUltimoNumeroBoleto();
    
    // Incrementar
    $nuevoNumero = $ultimoNumero + 1;
    
    // Formatear: "100 - 000016"
    $serie = '100';
    $correlativo = str_pad($nuevoNumero, 6, '0', STR_PAD_LEFT);
    
    return "$serie - $correlativo";
}
```

## 🧪 Probar el Sistema

### Opción 1: Demo Independiente
1. Abre en tu navegador: `http://localhost/venta-pasajes/demo-ticket.html`
2. Haz clic en "Imprimir Ticket de Prueba"
3. Verifica que se abre la ventana emergente con el ticket
4. Verifica que el código QR se genera correctamente

### Opción 2: Integración Real
1. Ve a la página de ventas: `http://localhost/venta-pasajes/ventas/crear_ruta`
2. Selecciona una ruta y haz clic en el ícono de venta
3. Selecciona un asiento
4. Completa los datos del pasajero
5. Haz clic en "Emitir Boleto"
6. En el SweetAlert, haz clic en "🖨️ Imprimir Ticket"
7. Verifica que se abre la ventana con el ticket térmico

## 📝 Personalización

### Cambiar Datos de la Empresa

Edita `assets/js/impresion-ticket.js` o pásalos dinámicamente desde el backend:

```javascript
ultimoBoletoEmitido = {
    empresaDireccion: 'Tu dirección',
    empresaTelefono: 'Tu teléfono',
    empresaEmail: 'tu@email.com',
    empresaRuc: 'Tu RUC',
    // ... resto de datos
};
```

### Cambiar Logo

Edita la función `generarHTMLTicket()` en `impresion-ticket.js`:

```javascript
<div class="logo-container">
    <img src="<?php echo URLROOT; ?>/assets/img/logo.png" class="logo" alt="Logo">
</div>
```

### Ajustar Ancho del Ticket

Si el ticket no cabe en tu impresora térmica, ajusta el ancho en los estilos:

```css
body {
    width: 280px; /* Reducir de 300px a 280px */
}
```

## 🐛 Solución de Problemas

### El QR no se genera
- Verifica que QRCode.js esté cargado en el header
- Aumenta el timeout antes de imprimir (línea 500ms → 1000ms)

### La ventana emergente no se abre
- Permite ventanas emergentes en tu navegador
- Verifica la consola del navegador para errores

### Los estilos no se aplican
- Verifica que los estilos estén dentro del `<style>` en el HTML generado
- No uses archivos CSS externos para el ticket

## 📞 Soporte

Para más información, consulta:
- `DOCUMENTACION-TICKET.md` - Documentación completa
- `demo-ticket.html` - Ejemplo funcional
- `assets/js/impresion-ticket.js` - Código fuente comentado

---

**¡Sistema de Impresión de Tickets Térmicos Instalado Exitosamente!** 🎉
