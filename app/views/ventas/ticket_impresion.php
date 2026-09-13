<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $data['ticket']->codigo_boleto ?? '---'; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            /* Fuente Monospace para ticket */
            font-size: 12px;
            margin: 0;
            padding: 5px;
            color: #000;
            background: #fff;
            width: 80mm;
            /* Ancho estándar 80mm */
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 10px;
        }

        .title {
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .label {
            font-weight: bold;
        }

        .big-seat {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            border: 2px solid #000;
            padding: 5px;
        }

        .total {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
        }

        @media print {
            @page {
                margin: 0;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <?php $t = $data['ticket']; ?>

    <div class="header">
        <div class="title">TRANSPORTES FLOTA</div>
        <div class="title">"INTERPROVINCIAL"</div>
        <div>NIT: 123456789</div>
        <div>--------------------------------</div>
        <div>BOLETO DE VIAJE</div>
        <div><?php echo $t->codigo_boleto; ?></div>
    </div>

    <div class="info-row">
        <span class="label">FECHA EMISIÓN:</span>
        <span><?php echo date('d/m/Y H:i'); ?></span>
    </div>

    <div class="separator"></div>

    <div class="info-row">
        <span class="label">ORIGEN:</span>
        <span><?php echo $t->ciudad_origen; ?></span>
    </div>
    <div class="info-row">
        <span class="label">DESTINO:</span>
        <span><?php echo $t->ciudad_destino; ?></span>
    </div>
    <div class="info-row">
        <span class="label">FECHA VIAJE:</span>
        <span><?php echo date('d/m/Y', strtotime($t->fecha_salida)); ?></span>
    </div>
    <div class="info-row">
        <span class="label">HORA:</span>
        <span><?php echo substr($t->hora_salida, 0, 5); ?></span>
    </div>
    <div class="info-row">
        <span class="label">BUS / PLACA:</span>
        <span><?php echo $t->bus_numero . ' / ' . $t->bus_placa; ?></span>
    </div>

    <div class="separator"></div>

    <div class="info-row">
        <span class="label">PASAJERO:</span>
    </div>
    <div><?php echo $t->nombre_pasajero; ?></div>
    <div class="info-row">
        <span class="label">CI/DNI:</span>
        <span><?php echo $t->numero_documento; ?></span>
    </div>

    <div class="big-seat">
        ASIENTO: <?php echo $t->numero_asiento; ?>
    </div>

    <div class="separator"></div>

    <div class="total">
        TOTAL: Bs. <?php echo number_format($t->precio, 2); ?>
    </div>

    <div class="footer">
        <div class="separator"></div>
        <div>¡Gracias por su preferencia!</div>
        <div>Debe presentarse 30 min antes.</div>
    </div>

    <script>
        // Auto-impresión y cierre
        window.onload = function() {
            window.print();
            window.onafterprint = function() {
                window.close();
            };
        };
    </script>
</body>

</html>