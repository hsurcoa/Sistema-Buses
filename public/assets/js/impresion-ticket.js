/**
 * SISTEMA DE IMPRESIÓN DE TICKETS TÉRMICOS 80MM
 * Ticket termico de boleto de viaje
 * 
 * Función principal para generar e imprimir tickets térmicos
 * optimizados para papel de 80mm (aprox. 300-320px de ancho)
 */

/**
 * Convierte un número a su representación en letras (Español - Bolivia)
 * @param {number} numero - El número a convertir
 * @returns {string} - Número en letras
 */
function numeroALetras(numero) {
    const unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    const decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    const especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    const centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    if (numero === 0) return 'CERO';
    if (numero === 100) return 'CIEN';

    let resultado = '';

    // Parte entera
    const parteEntera = Math.floor(numero);
    const parteDecimal = Math.round((numero - parteEntera) * 100);

    // Centenas
    if (parteEntera >= 100) {
        resultado += centenas[Math.floor(parteEntera / 100)] + ' ';
        numero = parteEntera % 100;
    } else {
        numero = parteEntera;
    }

    // Decenas y unidades
    if (numero >= 10 && numero < 20) {
        resultado += especiales[numero - 10];
    } else {
        if (numero >= 20) {
            resultado += decenas[Math.floor(numero / 10)];
            if (numero % 10 > 0) {
                resultado += ' Y ' + unidades[numero % 10];
            }
        } else {
            resultado += unidades[numero];
        }
    }

    // Agregar decimales
    const decimalStr = parteDecimal.toString().padStart(2, '0');
    return resultado.trim() + ' CON ' + decimalStr + '/100 BOLIVIANOS';
}

/**
 * Formatea una fecha en formato DD/MM/YYYY
 * @param {string|Date} fecha - Fecha a formatear
 * @returns {string} - Fecha formateada
 */
function formatearFecha(fecha) {
    const date = new Date(fecha);
    const dia = String(date.getDate()).padStart(2, '0');
    const mes = String(date.getMonth() + 1).padStart(2, '0');
    const anio = date.getFullYear();
    return `${dia}/${mes}/${anio}`;
}

/**
 * Formatea una hora en formato HH:MM
 * @param {string} hora - Hora a formatear
 * @returns {string} - Hora formateada
 */
function formatearHora(hora) {
    if (!hora) return '00:00';
    return hora.substring(0, 5);
}

/**
 * Función principal de impresión de tickets térmicos
 * @param {Object} datosBoleto - Objeto con la información del boleto
 * @param {Window} [ventanaExistente=null] - (Opcional) Referencia a una ventana ya abierta para evitar bloqueos
 */
// Escapa texto para insertarlo en el HTML del ticket
function escaparTicket(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function imprimirTicket(datosBoleto, ventanaExistente = null) {
    // Validar que existan los datos
    if (!datosBoleto) {
        console.error('No se proporcionaron datos del boleto');
        if (ventanaExistente) ventanaExistente.close();
        return;
    }

    // Usar ventana existente o abrir nueva
    const ventanaImpresion = ventanaExistente || window.open('', '_blank', 'width=340,height=600');

    if (!ventanaImpresion) {
        alert('Por favor, permite las ventanas emergentes para imprimir el ticket');
        return;
    }

    // Generar el HTML del ticket
    const htmlTicket = generarHTMLTicket(datosBoleto);

    // Escribir el contenido en la ventana (LIMPIANDO PREVIAMENTE)
    ventanaImpresion.document.open();
    ventanaImpresion.document.write(htmlTicket);
    ventanaImpresion.document.close();

    // Esperar un momento para asegurar carga (aunque onload script en html se encarga)
    // No necesitamos hacer nada mas desde aqui ya que el script inyectado hace el trabajo.
    console.log("Ticket generado en ventana emergente.");
}

/**
 * Genera el HTML completo del ticket térmico
 * @param {Object} datos - Datos del boleto
 * @returns {string} - HTML del ticket
 */
function generarHTMLTicket(datos) {
    const empresaTicket = {
        nombre: datos.empresaNombre || (window.EMPRESA_TICKET && window.EMPRESA_TICKET.nombre) || 'Boleto de viaje',
        nit: datos.empresaNit || (window.EMPRESA_TICKET && window.EMPRESA_TICKET.nit) || ''
    };

    return `
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - ${datos.numeroBoleto}</title>
    
    <!-- QRCode.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <style>
        /* ===== ESTILOS GENERALES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', 'Consolas', monospace;
            font-size: 11px;
            color: #000;
            background: #fff;
            width: 78mm;
            margin: 0 auto;
            padding: 3mm;
        }

        /* ===== ESTILOS DE IMPRESIÓN ===== */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            body {
                width: 80mm;
                padding: 3mm;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }
        }

        /* ===== CABECERA ===== */
        .ticket-header {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo-container {
            margin-bottom: 8px;
        }

        .logo {
            max-width: 100px;
            height: auto;
        }

        .empresa-nombre {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            margin: 3px 0;
            text-transform: uppercase;
        }

        .empresa-info {
            font-size: 9px;
            line-height: 1.3;
            color: #000;
        }

        .numero-boleto-header {
            font-size: 10px;
            margin-top: 6px;
            color: #000;
            font-weight: bold;
        }

        .numero-boleto-grande {
            font-size: 16px;
            font-weight: bold;
            color: #000;
            margin: 4px 0;
            letter-spacing: 1px;
        }

        /* ===== SEPARADORES ===== */
        .separador {
            border-bottom: 1px dashed #000;
            margin: 8px 0;
        }

        .separador-solido {
            border-bottom: 1px solid #000;
            margin: 8px 0;
        }

        /* ===== GRILLA DE VIAJE ===== */
        .grilla-viaje {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin: 8px 0;
            font-size: 10px;
        }

        .campo-viaje {
            text-align: center;
        }

        .campo-label {
            font-weight: bold;
            font-size: 9px;
            color: #000;
            margin-bottom: 2px;
        }

        .campo-valor {
            font-size: 10px;
            color: #000;
            font-weight: 600;
        }

        .campo-destacado {
            background: #f0f0f0;
            padding: 6px;
            border: 1px solid #000;
            margin: 8px 0;
        }

        .campo-destacado .campo-valor {
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        /* ===== SECCIÓN ORIGEN/DESTINO ===== */
        .origen-destino {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin: 8px 0;
        }

        .origen-destino .campo-viaje {
            text-align: left;
        }

        /* ===== DATOS PASAJERO ===== */
        .seccion-datos {
            margin: 8px 0;
        }

        .seccion-titulo {
            font-weight: bold;
            font-size: 10px;
            color: #000;
            margin-bottom: 4px;
        }

        .dato-fila {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 10px;
        }

        .dato-label {
            font-weight: bold;
            color: #000;
        }

        .dato-valor {
            color: #000;
            text-align: right;
            flex: 1;
            margin-left: 8px;
        }

        /* ===== DATOS VENTA ===== */
        .importe-total {
            font-size: 12px;
            font-weight: bold;
            margin: 6px 0;
        }

        .importe-letras {
            font-size: 8px;
            color: #000;
            margin: 4px 0;
            text-align: center;
        }

        /* ===== CÓDIGO QR ===== */
        .qr-container {
            text-align: center;
            margin: 10px 0;
        }

        #qrcode {
            display: inline-block;
            margin: 8px auto;
        }

        #qrcode img {
            margin: 0 auto;
            display: block;
        }

        /* ===== FOOTER ===== */
        .ticket-footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
            color: #000;
        }

        .mensaje-gracias {
            font-weight: bold;
            margin-top: 8px;
            color: #000;
        }

        /* ===== UTILIDADES ===== */
        .text-center {
            text-align: center;
        }

        .text-bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- CABECERA -->
    <div class="ticket-header">
        <div class="logo-container">
            <svg class="logo" viewBox="0 0 200 80" xmlns="http://www.w3.org/2000/svg">
                <!-- Bus Icon -->
                <rect x="40" y="20" width="120" height="40" rx="8" fill="#000"/>
                <rect x="50" y="28" width="20" height="15" fill="#ffffff"/>
                <rect x="75" y="28" width="20" height="15" fill="#ffffff"/>
                <rect x="100" y="28" width="20" height="15" fill="#ffffff"/>
                <rect x="125" y="28" width="20" height="15" fill="#ffffff"/>
                <circle cx="65" cy="65" r="6" fill="#000"/>
                <circle cx="135" cy="65" r="6" fill="#000"/>
                <circle cx="65" cy="65" r="3" fill="#fff"/>
                <circle cx="135" cy="65" r="3" fill="#fff"/>
            </svg>
        </div>
        <div class="empresa-nombre">${escaparTicket(empresaTicket.nombre)}</div>
        <div class="empresa-info">
            ${[
                empresaTicket.nit ? 'NIT: ' + escaparTicket(empresaTicket.nit) : '',
                datos.empresaDireccion ? escaparTicket(datos.empresaDireccion) : '',
                datos.empresaTelefono ? 'Tel: ' + escaparTicket(datos.empresaTelefono) : '',
            ].filter(Boolean).join('<br>')}
        </div>
        <div class="numero-boleto-header">BOLETO DE VIAJE</div>
        <div class="numero-boleto-grande">${escaparTicket(datos.numeroBoleto || '')}</div>
    </div>

    <div class="separador-solido"></div>

    <!-- GRILLA DE VIAJE -->
    <div class="grilla-viaje">
        <div class="campo-viaje">
            <div class="campo-label">Fecha Viaje</div>
            <div class="campo-valor">${formatearFecha(datos.fechaViaje)}</div>
        </div>
        <div class="campo-viaje">
            <div class="campo-label">Hora Salida</div>
            <div class="campo-valor">${formatearHora(datos.horaSalida)}</div>
        </div>
        <div class="campo-viaje">
            <div class="campo-label">Placa Bus</div>
            <div class="campo-valor">${escaparTicket(datos.placaBus || '—')}</div>
        </div>
    </div>

    <!-- ORIGEN Y DESTINO -->
    <div class="origen-destino">
        <div class="campo-viaje">
            <div class="campo-label">Origen</div>
            <div class="campo-valor">${datos.origen}</div>
        </div>
        <div class="campo-viaje">
            <div class="campo-label">Destino</div>
            <div class="campo-valor">${datos.destino}</div>
        </div>
    </div>

    <!-- ASIENTO DESTACADO -->
    <div class="campo-viaje campo-destacado">
        <div class="campo-label">Asiento</div>
        <div class="campo-valor">${datos.asiento}</div>
    </div>

    <div class="separador"></div>

    <!-- DATOS DEL PASAJERO -->
    <div class="seccion-datos">
        <div class="seccion-titulo">Nombre pasajero</div>
        <div class="dato-valor text-bold">${datos.nombrePasajero}</div>
    </div>

    <div class="seccion-datos">
        <div class="seccion-titulo">Documento Cliente</div>
        <div class="dato-valor">${datos.documentoPasajero}</div>
    </div>

    <div class="separador"></div>

    <!-- DATOS DE VENTA -->
    <div class="seccion-datos">
        <div class="dato-fila">
            <span class="dato-label">Fecha Expedición</span>
            <span class="dato-valor">${formatearFecha(datos.fechaExpedicion || new Date())}</span>
        </div>
        <div class="dato-fila importe-total">
            <span class="dato-label">Importe pagado</span>
            <span class="dato-valor">Bs. ${parseFloat(datos.importe).toFixed(2)}</span>
        </div>
        <div class="importe-letras">
            SON: ${numeroALetras(parseFloat(datos.importe))}
        </div>
    </div>

    <div class="separador"></div>

    <!-- CÓDIGO QR -->
    <div class="qr-container">
        <div id="qrcode"></div>
    </div>

    <!-- FOOTER -->
    <div class="ticket-footer">
        <div class="mensaje-gracias">Gracias por su preferencia</div>
    </div>

    <!-- AUTO-EXECUTE SCRIPT -->
    <script>
        window.onload = function() {
            try {
                // Generar QR
                var qrContainer = document.getElementById('qrcode');
                if (qrContainer && typeof QRCode !== 'undefined') {
                    var datosQR = "BOLETO:${datos.numeroBoleto}|PASAJERO:${datos.nombrePasajero}|ASIENTO:${datos.asiento}|FECHA:${datos.fechaViaje}";
                    new QRCode(qrContainer, {
                        text: datosQR,
                        width: 120,
                        height: 120,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            } catch(e) { console.error("Error QR", e); }

            // Auto-Imprimir
            setTimeout(function() {
                window.focus();
                window.print();
            }, 800);
        };
    </script>
</body>
</html>
    `;
}

/**
 * OBJETO DE EJEMPLO PARA PRUEBAS
 * Puedes usar este objeto para probar la función inmediatamente
 */
const datosBoletoEjemplo = {
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

/**
 * EJEMPLO DE USO:
 * 
 * // Llamar la función con datos de ejemplo
 * imprimirTicket(datosBoletoEjemplo);
 * 
 * // O con datos reales desde tu sistema
 * const datosVentaReal = {
 *     empresaDireccion: 'Tu dirección',
 *     empresaTelefono: 'Tu teléfono',
 *     // ... resto de datos
 * };
 * imprimirTicket(datosVentaReal);
 */
