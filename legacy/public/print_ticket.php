<?php
// print_ticket.php - Standalone Ticket Printer
// Allows printing without Controller restrictions
// Place this in /public/ folder

// 1. Load Bootstrap (Access to DB and Config)
if (file_exists('../app/bootstrap.php')) {
    require_once '../app/bootstrap.php';
} else {
    die("Error: System core not found.");
}

// 2. Initial Checks
// El ticket tiene nombre y documento del pasajero: solo para usuarios con sesion
// (antes cualquiera podia recorrer ids y ver los datos).
if (!$sessionManager->isAuthenticated()) {
    http_response_code(401);
    die("Sesión expirada. Inicie sesión nuevamente.");
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Ticket ID Required.");
}

$id = $_GET['id'];

// 3. Database Connection & Query
$db = new Database();

// Query optimized for Ticket Details
$sql = "SELECT 
            b.id as ticket_id,
            b.codigo_boleto,
            b.numero_asiento,
            b.precio_final,
            b.fecha_reserva as fecha_emision,
            b.metodo_pago,
            b.referencia_pago,

            -- Sucursal que vendio
            suc.nombre_sede as sucursal_nombre,
            suc.direccion as sucursal_direccion,
            suc.telefono as sucursal_telefono,
            
            -- Cliente
            c.nombres,
            c.apellidos,
            c.numero_documento,
            
            -- Viaje
            v.fecha_salida,
            v.hora_salida,
            
            -- Ruta
            r.origen,
            COALESCE(rp.nombre_parada, r.destino) AS destino, -- destino real del pasajero (parada intermedia)
            
            -- Bus (Corregido: v.bus_id apunta a vehiculos, no buses)
            veh.placa as bus_placa,
            bs.numero_interno as bus_numero,
            
            -- Chofer (Nuevo)
            u.nombres as chofer_nombres,
            u.apellidos as chofer_apellidos
            
        FROM boletos b
        INNER JOIN clientes c ON b.cliente_id = c.id
        INNER JOIN viajes v ON b.viaje_id = v.id
        INNER JOIN rutas r ON v.ruta_id = r.id
        LEFT JOIN rutas_paradas rp ON rp.id = b.parada_id
        LEFT JOIN terminales suc ON suc.id = b.sucursal_id
        
        -- Fix: Unir con vehiculos usando el bus_id del viaje
        LEFT JOIN vehiculos veh ON v.bus_id = veh.id
        -- Fix: Intentar obtener el numero interno de la tabla buses usando la placa (con correccion de collation)
        LEFT JOIN buses bs ON veh.placa COLLATE utf8mb4_unicode_ci = bs.placa
        
        -- Chofer del viaje (viajes.chofer_id -> personal)
        LEFT JOIN personal u ON v.chofer_id = u.id
        
        WHERE b.id = :id OR b.codigo_boleto = :codigo
        LIMIT 1";

$db->query($sql);
$db->bind(':id', $id);
$db->bind(':codigo', $id); // Allow searching by code too
$ticket = $db->single();

if (!$ticket) {
    die("Error: Ticket not found or invalid.");
}

// 4. Data Formatting
// Empresa desde Configuracion (antes el ticket imprimia datos de ejemplo: BUSDRIVER, NIT 1234567890)
$db->query("SELECT clave, valor FROM configuracion_sistema WHERE clave IN ('empresa_nombre', 'empresa_nit')");
$empresaCfg = [];
foreach ($db->resultSet() as $fila) {
    $empresaCfg[$fila->clave] = $fila->valor;
}
$empresaTicket = ['nombre' => $empresaCfg['empresa_nombre'] ?? SITENAME, 'nit' => $empresaCfg['empresa_nit'] ?? ''];

$nombre_completo = strtoupper($ticket->nombres . ' ' . $ticket->apellidos);
$origen = strtoupper($ticket->origen);
$destino = strtoupper($ticket->destino);
$fecha = date('d/m/Y', strtotime($ticket->fecha_salida));
$hora = substr($ticket->hora_salida, 0, 5); // HH:MM
$precio = number_format($ticket->precio_final, 2);

// Formatear chofer
$nombre_chofer = '---';
if (!empty($ticket->chofer_nombres)) {
    $nombre_chofer = strtoupper($ticket->chofer_nombres . ' ' . $ticket->chofer_apellidos);
}

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