<?php
/**
 * Puerto de `legacy/public/print_ticket.php` (Fase de eliminacion de
 * SessionManager/scripts sueltos). La consulta y el formateo de variables
 * ahora viven en `BoletosController::printPublico()`; esta vista solo
 * recibe `$data` ya armado, mismo patron que el resto de vistas portadas.
 */
$ticket = $data['ticket'];
$empresaTicket = $data['empresa'];
$nombre_completo = $data['nombre_completo'];
$origen = $data['origen'];
$destino = $data['destino'];
$fecha = $data['fecha'];
$hora = $data['hora'];
$precio = $data['precio'];
$nombre_chofer = $data['nombre_chofer'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $ticket->codigo_boleto; ?></title>
    <style>
        /* THERMAL PRINT CSS */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 5px;
            }

            .no-print {
                display: none;
            }
        }

        body {
            font-family: 'Courier New', 'Consolas', monospace;
            width: 78mm;
            /* Slight buffer */
            margin: 0 auto;
            background: #fff;
            color: #000;
            font-size: 12px;
            line-height: 1.2;
        }

        .container {
            padding: 5px 2px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .header {
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }

        .logo-placeholder {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .big-seat {
            font-size: 32px;
            font-weight: 800;
            border: 2px solid #000;
            display: inline-block;
            padding: 5px 15px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 10px;
        }

        .total-box {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            margin-top: 5px;
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- HEADER -->
        <div class="header text-center">
            <div class="logo-placeholder"><?php echo htmlspecialchars(strtoupper($empresaTicket['nombre'])); ?></div>
            <?php if ($empresaTicket['nit']): ?><div>NIT: <?php echo htmlspecialchars($empresaTicket['nit']); ?></div><?php endif; ?>
            <?php if (!empty($ticket->sucursal_nombre)): ?>
                <div>SUCURSAL <?php echo htmlspecialchars(strtoupper($ticket->sucursal_nombre)); ?></div>
                <div><?php echo htmlspecialchars($ticket->sucursal_direccion); ?></div>
                <?php if (!empty($ticket->sucursal_telefono)): ?><div>Tel: <?php echo htmlspecialchars($ticket->sucursal_telefono); ?></div><?php endif; ?>
            <?php endif; ?>
            <div>BOLETO <?php echo htmlspecialchars($ticket->codigo_boleto); ?></div>
        </div>

        <!-- INFO VIAJE -->
        <div class="text-center">
            <div class="bold uppercase" style="font-size: 14px;">
                <?php echo $origen; ?> - <?php echo $destino; ?>
            </div>

            <div class="info-row" style="margin-top: 5px;">
                <span>FECHA: <?php echo $fecha; ?></span>
                <span>HORA: <?php echo $hora; ?></span>
            </div>
            <div>BUS: <?php echo $ticket->bus_numero ?? '---'; ?> (<?php echo $ticket->bus_placa ?? '---'; ?>)</div>
            <div style="margin-top: 2px;">CHOFER: <?php echo $nombre_chofer; ?></div>
        </div>

        <div class="divider"></div>

        <!-- ASIENTO GRANDE -->
        <div class="text-center">
            <div style="font-size: 10px;">ASIENTO N°</div>
            <div class="big-seat"><?php echo $ticket->numero_asiento; ?></div>
        </div>

        <div class="divider"></div>

        <!-- PASAJERO -->
        <div class="uppercase">
            <div class="bold">PASAJERO:</div>
            <div><?php echo $nombre_completo; ?></div>
            <div class="info-row">
                <span>DOC: <?php echo $ticket->numero_documento; ?></span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- PRECIO -->
        <div class="info-row" style="align-items: center;">
            <span class="bold">TOTAL PAGADO:</span>
            <span class="total-box">Bs. <?php echo $precio; ?></span>
        </div>
        <div class="info-row" style="font-size: 10px;">
            <span>PAGO: <?php echo ($ticket->metodo_pago ?? 'EFECTIVO') === 'QR' ? 'QR' : 'EFECTIVO'; ?></span>
            <?php if (!empty($ticket->referencia_pago)): ?><span>OP: <?php echo htmlspecialchars($ticket->referencia_pago); ?></span><?php endif; ?>
        </div>
        <div class="uppercase text-center" style="font-size: 9px; margin-top: 2px;">
            (Son: <?php echo $precio; ?> Bolivianos)
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <p>GRACIAS POR SU PREFERENCIA</p>
            <p>Conserve este boleto para cualquier reclamo.</p>

            <!-- QR PlaceHolder -->
            <div style="margin-top: 10px;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=BOLETO-<?php echo $ticket->codigo_boleto; ?>" alt="QR" width="80">
            </div>
            <div style="font-size: 9px; margin-top: 5px;">
                COD: <?php echo $ticket->codigo_boleto; ?>
            </div>
        </div>

    </div>

    <!-- Auto Print Script -->
    <script>
        window.onload = function() {
            window.print();
            // Optional: Close after print
            // window.onafterprint = function() { window.close(); }
        }
    </script>
</body>

</html>