<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Z - Cierre de Caja #<?php echo $data['sesion']->id; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            padding: 20px;
            max-width: 80mm;
            margin: 0 auto;
        }

        .ticket {
            border: 2px dashed #000;
            padding: 15px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
            margin: 2px 0;
        }

        .section {
            margin: 15px 0;
            padding: 10px 0;
            border-bottom: 1px dashed #666;
        }

        .section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }

        .row.total {
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 2px solid #000;
        }

        .label {
            flex: 1;
        }

        .value {
            text-align: right;
            font-weight: bold;
        }

        .movimientos {
            font-size: 10px;
            max-height: 200px;
            overflow-y: auto;
        }

        .movimiento {
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }

        .movimiento:last-child {
            border-bottom: none;
        }

        .ingreso {
            color: #006400;
        }

        .egreso {
            color: #8B0000;
        }

        .resultado {
            text-align: center;
            padding: 15px;
            margin: 15px 0;
            border: 3px double #000;
            background: #f0f0f0;
        }

        .resultado.perfecto {
            background: #d4edda;
            border-color: #28a745;
        }

        .resultado.diferencia {
            background: #f8d7da;
            border-color: #dc3545;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #000;
            font-size: 10px;
        }

        @media print {
            body {
                padding: 0;
            }

            .ticket {
                border: none;
            }

            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="ticket">
        <!-- HEADER -->
        <div class="header">
            <h1>═══ REPORTE Z ═══</h1>
            <p>CIERRE DE CAJA</p>
            <p>Sistema de Venta de Pasajes</p>
            <p>────────────────────</p>
            <p><strong>Turno #<?php echo str_pad($data['sesion']->id, 6, '0', STR_PAD_LEFT); ?></strong></p>
        </div>

        <!-- INFORMACIÓN DEL CAJERO -->
        <div class="section">
            <div class="section-title">📋 Información del Turno</div>
            <div class="row">
                <span class="label">Cajero:</span>
                <span class="value"><?php echo $data['sesion']->cajero_nombre; ?></span>
            </div>
            <div class="row">
                <span class="label">ID Usuario:</span>
                <span class="value">#<?php echo $data['sesion']->usuario_id; ?></span>
            </div>
            <div class="row">
                <span class="label">Apertura:</span>
                <span class="value"><?php echo date('d/m/Y H:i', strtotime($data['sesion']->fecha_apertura)); ?></span>
            </div>
            <div class="row">
                <span class="label">Cierre:</span>
                <span class="value"><?php echo date('d/m/Y H:i', strtotime($data['sesion']->fecha_cierre)); ?></span>
            </div>
        </div>

        <!-- RESUMEN FINANCIERO -->
        <div class="section">
            <div class="section-title">💰 Resumen Financiero</div>
            <div class="row">
                <span class="label">Fondo Inicial:</span>
                <span class="value">Bs. <?php echo number_format($data['sesion']->monto_inicial, 2); ?></span>
            </div>
            <div class="row ingreso">
                <span class="label">+ Ingresos:</span>
                <span class="value">Bs. <?php echo number_format($data['sesion']->total_ingresos, 2); ?></span>
            </div>
            <div class="row egreso">
                <span class="label">- Egresos:</span>
                <span class="value">Bs. <?php echo number_format($data['sesion']->total_egresos, 2); ?></span>
            </div>
            <div class="row total">
                <span class="label">TOTAL SISTEMA:</span>
                <span class="value">Bs. <?php echo number_format($data['sesion']->monto_final_sistema, 2); ?></span>
            </div>
        </div>

        <!-- DETALLE DE MOVIMIENTOS -->
        <?php if (!empty($data['movimientos'])): ?>
            <div class="section">
                <div class="section-title">📊 Detalle de Movimientos</div>
                <div class="movimientos">
                    <?php foreach ($data['movimientos'] as $mov): ?>
                        <div class="movimiento <?php echo strtolower($mov->tipo_movimiento); ?>">
                            <div class="row">
                                <span><?php echo $mov->descripcion; ?></span>
                                <span><?php echo ($mov->tipo_movimiento == 'INGRESO' ? '+' : '-'); ?> Bs. <?php echo number_format($mov->monto, 2); ?></span>
                            </div>
                            <div style="font-size: 9px; color: #666;">
                                <?php echo date('H:i', strtotime($mov->fecha_creacion)); ?> | <?php echo $mov->origen_modulo; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- RESULTADO DEL ARQUEO -->
        <div class="resultado <?php echo ($data['sesion']->diferencia == 0) ? 'perfecto' : 'diferencia'; ?>">
            <div class="section-title">🎯 Resultado de Arqueo</div>
            <div class="row">
                <span class="label">Efectivo Real:</span>
                <span class="value">Bs. <?php echo number_format($data['sesion']->monto_final_real, 2); ?></span>
            </div>
            <div class="row">
                <span class="label">Diferencia:</span>
                <span class="value">
                    <?php if ($data['sesion']->diferencia == 0): ?>
                        ✓ PERFECTO
                    <?php elseif ($data['sesion']->diferencia > 0): ?>
                        +Bs. <?php echo number_format($data['sesion']->diferencia, 2); ?> (SOBRA)
                    <?php else: ?>
                        Bs. <?php echo number_format($data['sesion']->diferencia, 2); ?> (FALTA)
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <p>────────────────────</p>
            <p>Este documento es un comprobante oficial</p>
            <p>de cierre de caja registrado en el sistema</p>
            <p>Fecha de impresión: <?php echo date('d/m/Y H:i:s'); ?></p>
            <p>────────────────────</p>
            <p><strong>Gracias por su trabajo</strong></p>
        </div>
    </div>
</body>

</html>