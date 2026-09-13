<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TICKET</title>
    <style>
        @media print {
            @page {
                margin: 0;
            }

            body {
                margin: 0;
                padding: 5px;
            }
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            color: #000;
            background: #fff;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .seat-box {
            font-size: 32px;
            border: 2px solid #000;
            text-align: center;
            margin: 10px 0;
            padding: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php $t = $data['ticket']; ?>

    <div class="text-center fw-bold uppercase">
        TRANSPORTES FLOTA
        <br>"INTERPROVINCIAL"
    </div>
    <div class="text-center">NIT: 123456789</div>
    <div class="line"></div>

    <div class="row">
        <span>ORIGEN:</span>
        <span class="fw-bold"><?php echo substr($t->ciudad_origen, 0, 15); ?></span>
    </div>
    <div class="row">
        <span>DESTINO:</span>
        <span class="fw-bold"><?php echo substr($t->ciudad_destino, 0, 15); ?></span>
    </div>
    <div class="row">
        <span>FECHA:</span>
        <span><?php echo $t->fecha_salida; ?></span>
    </div>
    <div class="row">
        <span>HORA:</span>
        <span><?php echo substr($t->hora_salida, 0, 5); ?></span>
    </div>
    <div class="row">
        <span>BUS:</span>
        <span><?php echo $t->bus_numero; ?></span>
    </div>

    <div class="seat-box">
        ASIENTO: <?php echo $t->numero_asiento; ?>
    </div>

    <div class="line"></div>

    <div class="fw-bold">PASAJERO:</div>
    <div class="uppercase"><?php echo $t->nombre_pasajero; ?></div>
    <div class="row">
        <span>CI/NIT:</span>
        <span><?php echo $t->numero_documento; ?></span>
    </div>

    <div class="line"></div>

    <div class="row fw-bold" style="font-size: 14px;">
        <span>TOTAL:</span>
        <span>Bs. <?php echo number_format($t->precio, 2); ?></span>
    </div>

    <div class="line"></div>
    <div class="text-center" style="font-size:10px;">
        Gracias por su preferencia.<br>
        Conserve este boleto.
    </div>

    <script>
        window.onload = function() {
            window.print();
            window.onafterprint = function() {
                window.close();
            };
        };
    </script>
</body>

</html>