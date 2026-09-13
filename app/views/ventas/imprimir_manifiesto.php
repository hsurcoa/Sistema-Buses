<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manifiesto de Pasajeros - HTML Print</title>

    <!-- Estilos Optimizados para Impresión HORIZONTAL (Landscape) -->
    <style>
        /* Tipografía General */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            /* Aumentamos un poco la fuente ya que tenemos más espacio */
            color: #000;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Configuración de Página: HORIZONTAL (LANDSCAPE) */
        @media print {
            @page {
                size: letter landscape;
                /* Hoja Carta Horizontal */
                margin: 10mm;
                /* Margen seguro */
            }

            body {
                width: 100%;
            }

            .no-print {
                display: none;
            }

            /* Evitar cortes feos en tablas */
            tr {
                page-break-inside: avoid;
            }
        }

        /* Contenedor Principal */
        .container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        /* Encabezado */
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }

        .header h2 {
            margin: 0;
            text-transform: uppercase;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .header .sub-title {
            font-size: 14px;
            margin-top: 2px;
            color: #444;
        }

        /* Datos del Viaje */
        .trip-info-container {
            display: flex;
            justify-content: space-between;
            border: 1px solid #000;
            margin-bottom: 15px;
            padding: 8px 15px;
            gap: 20px;
            background-color: #f8f9fa;
        }

        .info-block {
            flex: 1;
            min-width: 0;
        }

        .info-item {
            margin-bottom: 5px;
            display: flex;
            align-items: baseline;
        }

        .label {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            color: #555;
            min-width: 80px;
            /* Alineación visual */
            display: inline-block;
        }

        .value {
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Tabla de Pasajeros */
        .table-manifest {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .table-manifest th,
        .table-manifest td {
            border: 1px solid #333;
            padding: 6px 8px;
        }

        .table-manifest th {
            background-color: #e0e0e0 !important;
            color: #000;
            text-transform: uppercase;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        /* Columnas ajustadas para Landscape */
        .col-n {
            width: 3%;
        }

        .col-asiento {
            width: 5%;
        }

        .col-estado {
            width: 8%;
        }

        .col-doc {
            width: 10%;
        }

        .col-nombre {
            width: 40%;
            /* Más espacio para nombres largos */
        }

        .col-destino {
            width: 20%;
        }

        .col-importe {
            width: 14%;
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

        /* Estados */
        .status-reservado {
            color: #555;
            font-style: italic;
        }

        .status-vendido {
            color: #000;
        }

        /* Totales */
        .total-row td {
            background-color: #f0f0f0;
            border-top: 2px solid #000;
            padding: 10px;
            font-size: 12px;
        }

        /* Firmas */
        .signatures {
            margin-top: 60px;
            width: 100%;
            border-collapse: separate;
            border-spacing: 40px 0;
        }

        .signatures td {
            text-align: center;
            vertical-align: top;
            width: 33%;
            padding: 0;
        }

        .sign-line {
            border-top: 1px solid #000;
            margin: 0 auto;
            padding-top: 8px;
            font-weight: bold;
            font-size: 11px;
        }

        /* Footer fecha */
        .footer-date {
            text-align: right;
            margin-top: 20px;
            font-size: 10px;
            color: #666;
            font-style: italic;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <h2>Manifiesto de Pasajeros</h2>
            <div class="sub-title">Sistema de Transporte Interprovincial - Reporte de Salida</div>
        </div>

        <!-- Datos del Viaje -->
        <div class="trip-info-container">
            <!-- Columna 1 -->
            <div class="info-block">
                <div class="info-item">
                    <span class="label">Ruta:</span>
                    <span class="value"><?php echo $data['viaje']->origen . ' - ' . $data['viaje']->destino; ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Conductor:</span>
                    <span class="value"><?php echo $data['viaje']->chofer_nombre ?? '---'; ?></span>
                </div>
            </div>

            <!-- Columna 2 -->
            <div class="info-block">
                <div class="info-item">
                    <span class="label">Fecha Salida:</span>
                    <span class="value"><?php echo date('d/m/Y', strtotime($data['viaje']->fecha_salida)); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Hora:</span>
                    <span class="value"><?php echo substr($data['viaje']->hora_salida, 0, 5); ?> hrs</span>
                </div>
            </div>

            <!-- Columna 3 -->
            <div class="info-block text-right">
                <div class="info-item" style="justify-content: flex-end;">
                    <span class="label">Bus / Placa:</span>
                    <span class="value"><?php echo $data['viaje']->bus_numero ?? 'S/N'; ?> / <?php echo $data['viaje']->bus_placa ?? '---'; ?></span>
                </div>
                <div class="info-item" style="justify-content: flex-end;">
                    <span class="label">Capacidad:</span>
                    <span class="value"><?php echo $data['viaje']->capacidad; ?> Asientos</span>
                </div>
            </div>
        </div>

        <!-- Tabla de Pasajeros -->
        <table class="table-manifest">
            <thead>
                <tr>
                    <th class="col-n">N°</th>
                    <th class="col-asiento">Asiento</th>
                    <th class="col-estado">Estado</th>
                    <th class="col-doc">CI / Documento</th>
                    <th class="col-nombre">Apellidos y Nombres</th>
                    <th class="col-destino">Destino</th>
                    <th class="col-importe">Importe</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalRecaudado = 0;
                $totalPasajeros = 0;
                $count = 1;

                if (empty($data['pasajeros'])) {
                    echo '<tr><td colspan="7" class="text-center" style="padding: 20px;">No hay pasajeros registrados</td></tr>';
                } else {
                    foreach ($data['pasajeros'] as $p):
                        $isVendido = (strtolower($p->estado) === 'vendido');
                        if ($isVendido) {
                            $totalRecaudado += floatval($p->precio);
                        }
                        $totalPasajeros++;
                        $nombreCompleto = $p->nombre_pasajero ? $p->nombre_pasajero : ($p->nombres . ' ' . $p->apellidos);

                        // Recortar estado si es muy largo visualmente
                        $estadoDisplay = strtoupper($p->estado);
                        if ($estadoDisplay == 'RESERVADO') $estadoDisplay = 'RESERVA';
                ?>
                        <tr class="<?php echo $isVendido ? 'status-vendido' : 'status-reservado'; ?>">
                            <td class="text-center"><?php echo $count++; ?></td>
                            <td class="text-center bold" style="font-size: 13px;"><?php echo $p->numero_asiento; ?></td>
                            <td class="text-center" style="font-size: 10px;"><?php echo $estadoDisplay; ?></td>
                            <td class="text-center"><?php echo $p->numero_documento; ?></td>
                            <td class="text-left bold"><?php echo strtoupper($nombreCompleto); ?></td>
                            <td class="text-center"><?php echo substr($p->destino, 0, 20); ?></td>
                            <td class="text-right bold"><?php echo number_format($p->precio, 2); ?></td>
                        </tr>
                <?php endforeach;
                } ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6" class="text-right bold" style="padding-right: 20px;">TOTAL RECAUDADO (Solo Vendidos):</td>
                    <td class="text-right bold" style="font-size: 14px;">Bs. <?php echo number_format($totalRecaudado, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Firmas -->
        <table class="signatures">
            <tr>
                <td>
                    <div class="sign-line">Firma Conductor</div>
                </td>
                <td>
                    <div class="sign-line">Firma Control/Despacho</div>
                </td>
                <td>
                    <div class="sign-line">Firma Administración</div>
                </td>
            </tr>
        </table>

        <div class="footer-date">
            Generado por Sistema de Ventas | Impreso el: <?php echo date('d/m/Y H:i:s'); ?> | Usuario: <?php echo $_SESSION['user_name'] ?? 'Sistema'; ?>
        </div>

    </div>

    <!-- Script de Auto-impresión -->
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500); // Pequeño delay para asegurar carga de estilos
        };
    </script>
</body>

</html>