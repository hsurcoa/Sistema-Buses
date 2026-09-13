<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row mb-3 no-print">
    <div class="col-12 mt-3 text-center">
        <!-- Espacio visual superior -->
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <!-- Columna Central: Hacemos el recibo más ancho para mejor visibilidad -->
        <div class="col-lg-8 col-md-10"> <!-- Aumentado de col-md-7 a col-lg-8 -->

            <div class="text-center mb-4 no-print">
                <h2 class="fw-bold text-dark mb-1">Comprobante Generado</h2>
                <p class="text-muted">Revise los detalles antes de imprimir</p>
            </div>

            <!-- TARJETA DE RECIBO (Diseño Amplio y Legible) -->
            <div class="card shadow-lg rounded-3 border-0 overflow-hidden" id="receipt-card">

                <!-- Encabezado Llamativo -->
                <div class="card-header bg-primary text-white p-5 position-relative">
                    <div class="d-flex justify-content-between align-items-center position-relative z-1">
                        <div>
                            <h2 class="fw-bold mb-1"><i class="bi bi-box-seam me-3"></i>TRANSPORTE PANDO</h2>
                            <p class="mb-0 fs-5 opacity-75">Servicio de Encomiendas y Logística</p>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-1 opacity-75 text-uppercase ls-1">Guía de Remisión</h5>
                            <h1 class="fw-bold mb-0 display-6"><?php echo $data['encomienda']->codigo_guia; ?></h1>
                        </div>
                    </div>
                    <!-- Icono Decorativo Fondo -->
                    <i class="bi bi-truck position-absolute text-white opacity-10" style="font-size: 10rem; right: -20px; bottom: -30px;"></i>
                </div>

                <div class="card-body p-0">

                    <!-- Sección Ruta (Destacada) -->
                    <div class="bg-light p-4 border-bottom">
                        <div class="row align-items-center text-center py-2">
                            <div class="col-md-5">
                                <h6 class="text-uppercase text-muted fw-bold mb-2">De: Origen</h6>
                                <h2 class="text-primary fw-bold mb-0"><?php echo strtoupper($data['encomienda']->origen); ?></h2>
                                <p class="text-muted mt-1 mb-0"><i class="bi bi-calendar-event me-1"></i> <?php echo date('d/m/Y', strtotime($data['encomienda']->fecha_registro)); ?></p>
                            </div>
                            <div class="col-md-2 my-3 my-md-0">
                                <div class="bg-white rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="bi bi-arrow-right text-primary fs-3"></i>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h6 class="text-uppercase text-muted fw-bold mb-2">A: Destino</h6>
                                <h2 class="text-primary fw-bold mb-0"><?php echo strtoupper($data['encomienda']->destino); ?></h2>
                                <p class="text-muted mt-1 mb-0">Llegada Estimada</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-5"> <!-- Padding interno amplio -->

                        <!-- Datos Remitente / Destinatario -->
                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <div class="p-4 border rounded-3 bg-white h-100 shadow-sm card-hover">
                                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                        <div class="icon-box bg-primary-soft text-primary me-3">
                                            <i class="bi bi-person-fill fs-4"></i>
                                        </div>
                                        <h5 class="fw-bold mb-0 text-dark">REMITENTE</h5>
                                    </div>
                                    <h4 class="mb-2 text-dark"><?php echo ucwords(strtolower($data['encomienda']->remitente_nombre)); ?></h4>
                                    <p class="text-secondary mb-0 fs-5">
                                        <span class="fw-bold">DNI:</span> <?php echo $data['encomienda']->remitente_dni; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-4 border rounded-3 bg-white h-100 shadow-sm card-hover">
                                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                        <div class="icon-box bg-success-soft text-success me-3">
                                            <i class="bi bi-geo-alt-fill fs-4"></i>
                                        </div>
                                        <h5 class="fw-bold mb-0 text-dark">DESTINATARIO</h5>
                                    </div>
                                    <h4 class="mb-2 text-dark"><?php echo ucwords(strtolower($data['encomienda']->destinatario_nombre)); ?></h4>
                                    <p class="text-secondary mb-0 fs-5">
                                        <span class="fw-bold">Tel:</span> <?php echo $data['encomienda']->destinatario_telefono; ?><br>
                                        <span class="fw-bold">DNI:</span> <?php echo $data['encomienda']->destinatario_dni; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles Carga -->
                        <h5 class="text-uppercase text-muted fw-bold mb-3 ls-1">Detalle del Envío</h5>
                        <div class="table-responsive mb-5">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="py-3 text-center" style="font-size: 1.1rem;">Cant.</th>
                                        <th class="py-3" style="font-size: 1.1rem;">Contenido</th>
                                        <th class="py-3 text-center" style="font-size: 1.1rem;">Tipo</th>
                                        <th class="py-3 text-end" style="font-size: 1.1rem;">Peso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center py-3 fs-5 fw-bold">1</td>
                                        <td class="py-3 fs-5"><?php echo ucfirst($data['encomienda']->descripcion); ?></td>
                                        <td class="text-center py-3">
                                            <span class="badge bg-secondary fs-6 rounded-pill px-3 py-2 fw-normal">
                                                <?php echo $data['encomienda']->tipo_carga ?? 'Estándar'; ?>
                                            </span>
                                        </td>
                                        <td class="text-end py-3 fs-5 fw-bold px-4"><?php echo $data['encomienda']->peso_kg; ?> kg</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Totales y Código -->
                        <div class="row align-items-center mt-5 pt-3 border-top">
                            <div class="col-md-7 text-center text-md-start mb-4 mb-md-0">
                                <div class="barcode mb-2 ms-md-0 mx-auto">
                                    ||| || ||| || |||
                                </div>
                                <small class="text-muted d-block ms-1"><?php echo $data['encomienda']->codigo_guia; ?></small>
                            </div>
                            <div class="col-md-5">
                                <div class="bg-primary text-white p-4 rounded-3 text-center shadow">
                                    <small class="text-uppercase opacity-75 d-block mb-1">Importe Total Pagado</small>
                                    <h1 class="mb-0 fw-bold">Bs. <?php echo number_format($data['encomienda']->total_pagar, 2); ?></h1>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Botones de Acción (Grandes y Claros) -->
            <div class="d-flex gap-3 mt-5 justify-content-center no-print pb-5">
                <a href="<?php echo URLROOT; ?>/encomiendas" class="btn btn-outline-secondary btn-lg px-5 py-3 rounded-pill fw-bold border-2">
                    <i class="bi bi-arrow-left me-2"></i> Volver
                </a>
                <button onclick="window.print()" class="btn btn-primary btn-lg px-5 py-3 rounded-pill fw-bold shadow hover-scale">
                    <i class="bi bi-printer-fill me-2"></i> IMPRIMIR TICKET
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    /* Estilos Generales Pantalla */
    body {
        background-color: #f0f2f5;
    }

    .ls-1 {
        letter-spacing: 1px;
    }

    .bg-main {
        background-color: #0d6efd;
    }

    .bg-primary-soft {
        background-color: rgba(13, 110, 253, 0.1);
    }

    .bg-success-soft {
        background-color: rgba(25, 135, 84, 0.1);
    }

    .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .card-hover {
        transition: transform 0.2s;
    }

    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .1) !important;
    }

    .hover-scale {
        transition: transform 0.2s;
    }

    .hover-scale:hover {
        transform: scale(1.05);
    }

    .barcode {
        font-family: 'Courier New', Courier, monospace;
        font-weight: 900;
        font-size: 3rem;
        height: 40px;
        overflow: hidden;
        color: #333;
        white-space: nowrap;
        width: 100%;
        max-width: 250px;
    }

    /* Ocultar sidebar para dar foco total */
    .app-sidebar,
    .app-header,
    .app-footer,
    .page-heading {
        display: none !important;
    }

    .main-content {
        margin: 0 !important;
        padding-top: 20px !important;
    }

    /* --- ESTILOS IMPRESIÓN (TICKET 80mm INVISIBLE PERO FUNCIONAL) --- */
    @media print {
        @page {
            margin: 0;
            size: auto;
        }

        body {
            background-color: #fff;
            font-family: 'Courier New', Courier, monospace;
            color: #000;
            display: block;
        }

        body * {
            visibility: hidden;
            height: 0;
        }

        #receipt-card,
        #receipt-card * {
            visibility: visible;
            height: auto;
        }

        #receipt-card {
            position: absolute;
            left: 0;
            top: 0;
            width: 80mm;
            max-width: 80mm;
            padding: 5px;
            margin: 0;
            box-shadow: none !important;
            border: none !important;
            background: none !important;
            border-radius: 0;
        }

        .row,
        .d-flex {
            display: block !important;
            margin: 0 !important;
        }

        .col-lg-8,
        .col-md-10,
        .col-md-5,
        .col-md-2,
        .col-md-6,
        .col-md-7 {
            width: 100% !important;
            display: block !important;
            text-align: center !important;
            padding: 0 !important;
            margin: 5px 0 5px 0 !important;
        }

        /* Limpieza Visual Ticket */
        .no-print,
        .icon-box,
        .bi,
        .btn,
        .card-hover {
            display: none !important;
        }

        /* Ajustes Tipografía Ticket */
        h1 {
            font-size: 14pt !important;
            margin: 5px 0 !important;
        }

        h2 {
            font-size: 12pt !important;
            margin: 2px 0 !important;
            color: #000 !important;
        }

        h4,
        h5,
        h6 {
            font-size: 10pt !important;
            margin: 1px 0 !important;
            font-weight: bold;
            color: #000 !important;
        }

        p,
        small,
        span,
        td,
        th {
            font-size: 8pt !important;
            color: #000 !important;
            font-weight: normal;
        }

        /* Estructura Ticket */
        .card-header {
            background: none !important;
            border-bottom: 2px dashed #000 !important;
            padding: 5px 0 !important;
            text-align: center;
        }

        .card-body {
            padding: 0 !important;
        }

        .bg-light,
        .bg-white,
        .border {
            background: none !important;
            border: none !important;
        }

        .border-bottom {
            border-bottom: 1px dashed #000 !important;
        }

        /* Cajas */
        .rounded-3,
        .rounded-circle {
            border-radius: 0 !important;
        }

        .p-4,
        .p-5 {
            padding: 2px !important;
        }

        .card-hover.border {
            border: 1px dashed #000 !important;
            padding: 5px !important;
            margin: 5px 0 !important;
            text-align: left !important;
        }

        /* Tabla y Totales */
        .table th {
            border-bottom: 1px dashed #000 !important;
        }

        .table td {
            border-bottom: none !important;
        }

        .bg-primary {
            border: 2px solid #000 !important;
            background: none !important;
            color: #000 !important;
            margin-top: 10px;
        }

        .barcode {
            font-size: 20pt;
            width: 100%;
            text-align: center;
            margin-top: 10px;
        }
    }
</style>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>