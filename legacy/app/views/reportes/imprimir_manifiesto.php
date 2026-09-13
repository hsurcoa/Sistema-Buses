<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Manifiesto de Pasajeros</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .logo {
            font-size: 20px;
            font-weight: bold;
        }

        .info-viaje {
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            padding: 10px;
        }

        .info-viaje td {
            padding: 5px;
            font-weight: bold;
        }

        table.pasajeros {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.pasajeros th,
        table.pasajeros td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        table.pasajeros th {
            background-color: #f0f0f0;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <button class="no-print" onclick="window.print()" style="padding: 10px 20px; cursor: pointer; margin-bottom: 10px; font-weight: bold; background: #007bff; color: white; border: none; border-radius: 5px;">🖨️ IMPRIMIR AHORA</button>

    <div class="header">
        <div class="logo">EMPRESA DE TRANSPORTE "TRANS BOLIVIA"</div>
        <div>MANIFIESTO DE PASAJEROS</div>
    </div>

    <!-- Info del Viaje -->
    <table class="info-viaje">
        <tr>
            <td>FECHA: <?php echo $data['viaje']->fecha_salida; ?></td>
            <td>HORA: <?php echo $data['viaje']->hora_salida; ?></td>
            <td>BUS: <?php echo $data['viaje']->placa ? $data['viaje']->placa : 'SIN ASIGNAR'; ?></td>
            <td>RUTA: <?php echo $data['viaje']->nombre_ruta; ?></td>
        </tr>
    </table>

    <table class="pasajeros">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">N°</th>
                <th>DOCUMENTO</th>
                <th>APELLIDOS Y NOMBRES</th>
                <th>DESTINO</th>
                <th>CELULAR</th>
                <th style="width: 100px;">FIRMA</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data['pasajeros'])): ?>
                <?php foreach ($data['pasajeros'] as $p): ?>
                    <?php
                    $nombres = '';
                    if (!empty($p->apellidos) && !empty($p->nombres)) {
                        $nombres = $p->apellidos . ' ' . $p->nombres;
                    } elseif (!empty($p->nombre_pasajero)) { // Fallback si viene en un solo campo
                        $nombres = $p->nombre_pasajero;
                    } else {
                        $nombres = $p->nombres;
                    }
                    ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold;"><?php echo $p->numero_asiento; ?></td>
                        <td><?php echo $p->documento; ?></td>
                        <td style="text-transform: uppercase;"><?php echo $nombres; ?></td>
                        <td><?php echo $p->destino; ?></td>
                        <td><?php echo $p->telefono; ?></td>
                        <td></td> <!-- Espacio firma -->
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 20px;">No hay pasajeros registrados en este viaje.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: center; width: 100%;">
        <div style="border-top: 1px solid #000; width: 200px; margin: 0 auto; padding-top: 5px;">
            Firma Responsable
        </div>
    </div>
</body>

</html>