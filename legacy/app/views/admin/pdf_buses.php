<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Flota</title>
    <style>
        /** Margenes y Configuración de Página **/
        @page {
            margin: 1cm 1cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            margin-top: 2.5cm;
            /* Espacio para el header fijo */
            margin-bottom: 1cm;
            /* Espacio para el footer fijo */
        }

        /** Header Fijo **/
        header {
            position: fixed;
            top: -2.0cm;
            /* Subir al margen superior */
            left: 0;
            right: 0;
            height: 2cm;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
        }

        .header-content {
            width: 100%;
        }

        .logo-section {
            width: 30%;
            float: left;
        }

        .company-section {
            width: 70%;
            float: right;
            text-align: right;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            margin: 0;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
            color: #444;
            text-transform: uppercase;
        }

        .report-meta {
            font-size: 10px;
            color: #666;
        }

        /** Footer Fijo **/
        footer {
            position: fixed;
            bottom: -0.5cm;
            left: 0px;
            right: 0px;
            height: 30px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            font-size: 9px;
            color: #777;
        }

        .page-number:before {
            content: "Página " counter(page);
        }

        /** Tabla de Datos **/
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #f4f6f9;
            color: #495057;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            padding: 8px;
            border-bottom: 2px solid #ddd;
            text-align: left;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        /* Zebra Striping */
        tr:nth-child(even) {
            background-color: #ffffff;
        }

        tr:nth-child(odd) {
            background-color: #f9fbfd;
            /* Azul muy muy pálido */
        }

        /** Estados y Estilos Especiales **/
        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }

        .status-active {
            color: #0f5132;
            /* Verde oscuro */
            background-color: #d1e7dd;
            border: 1px solid #badbcc;
        }

        .status-inactive {
            color: #842029;
            /* Rojo oscuro */
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
        }

        .plate {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 1px;
            background: #eee;
            padding: 2px 4px;
            border-radius: 3px;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body>
    <!-- Encabezado -->
    <header>
        <div class="header-content">
            <div class="logo-section">
                <!-- Ajustar ruta según tu entorno. DomPDF prefiere rutas absolutas del sistema de archivos -->
                <!-- Placeholder si no hay logo -->
                <?php $logoPath = 'C:/xampp/htdocs/venta-pasajes/public/assets/img/AdminLTELogo.png'; ?>
                <?php if (file_exists($logoPath)): ?>
                    <img src="file://<?php echo $logoPath; ?>" style="height: 50px; opacity: 0.8;">
                <?php else: ?>
                    <h2 style="color:#aaa;">[LOGO]</h2>
                <?php endif; ?>
            </div>
            <div class="company-section">
                <h1 class="company-name">SISTEMA DE TRANSPORTE</h1>
                <div class="report-title">Reporte General de Unidades</div>
                <div class="report-meta">Generado el: <?php echo $data['fecha_generacion']; ?></div>
            </div>
        </div>
    </header>

    <!-- Pie de Página -->
    <footer>
        <table style="width: 100%; border: none; margin: 0;">
            <tr>
                <td style="text-align: left; border: none; width: 33%;">
                    Confidencial - Uso Interno
                </td>
                <td style="text-align: center; border: none; width: 33%;">
                    <span class="page-number"></span>
                </td>
                <td style="text-align: right; border: none; width: 33%;">
                    Generado por Sistema de Transporte v1.0
                </td>
            </tr>
        </table>
    </footer>

    <!-- Contenido Principal -->
    <main>
        <h3>Listado de Buses Registrados</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 15%;">Placa</th>
                    <th style="width: 15%;">Marca / Modelo</th>
                    <th style="width: 10%;">Año</th>
                    <th style="width: 25%;">Propietario</th>
                    <th style="width: 15%;">Capacidad</th>
                    <th style="width: 15%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data['vehiculos'])): ?>
                    <?php foreach ($data['vehiculos'] as $bus): ?>
                        <tr>
                            <td style="color: #666;">#<?php echo $bus->id; ?></td>
                            <td><span class="plate"><?php echo strtoupper($bus->placa); ?></span></td>
                            <td>
                                <div><strong><?php echo $bus->marca; ?></strong></div>
                                <div style="font-size: 10px; color: #666;"><?php echo $bus->modelo; ?></div>
                            </td>
                            <td><?php echo $bus->anio; ?></td>
                            <td>
                                <?php echo $bus->propietario_nombres . ' ' . $bus->propietario_apellidos; ?>
                            </td>
                            <td>
                                <?php echo $bus->asientos ?? 'N/A'; ?> Asientos
                                <div style="font-size: 9px; color: #666;"><?php echo $bus->tipo_servicio ?? 'Normal'; ?></div>
                            </td>
                            <td>
                                <?php if ($bus->estado == 1): ?>
                                    <span class="badge status-active">Activo</span>
                                <?php else: ?>
                                    <span class="badge status-inactive">Inactivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px; color: #777;">
                            No hay unidades registradas en el sistema.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>

</html>