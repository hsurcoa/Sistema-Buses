@extends('layouts.app')

@section('content')
<!-- Main Wrapper -->
<main class="app-main">
    <div class="app-content">
        <div class="container-fluid">
            <div class="row justify-content-center mt-5 mb-5">
                <div class="col-lg-5 col-md-7">

                    <div class="card shadow-lg border-0" id="ticket-cierre">
                        <div class="card-header bg-dark text-white text-center py-4">
                            <i class="bi bi-receipt-cutoff display-1 text-white-50 mb-3"></i>
                            <h4 class="fw-bold mb-0">COMPROBANTE DE CIERRE</h4>
                            <small class="opacity-75 text-uppercase">Arqueo de Caja - Control Diario</small>
                        </div>

                        <div class="card-body p-5">

                            <!-- Info Turno -->
                            <div class="text-center mb-4 border-bottom pb-4">
                                <h5 class="fw-bold mb-1"><?php echo $data['sesion']->cajero_nombre; ?></h5>
                                <p class="text-muted mb-0 small">ID Cajero: <?php echo $data['sesion']->usuario_id; ?></p>
                                <div class="mt-2 badge bg-light text-dark border">
                                    Turno #<?php echo $data['sesion']->id; ?>
                                </div>
                            </div>

                            <!-- Detalles Fechas -->
                            <div class="row small text-muted mb-4">
                                <div class="col-6">
                                    <strong>Apertura:</strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($data['sesion']->fecha_apertura)); ?>
                                </div>
                                <div class="col-6 text-end">
                                    <strong>Cierre:</strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($data['sesion']->fecha_cierre)); ?>
                                </div>
                            </div>

                            <!-- Tabla de Totales -->
                            <div class="table-responsive bg-light rounded-3 p-3 mb-4">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <td class="text-muted">Fondo Inicial:</td>
                                        <td class="text-end fw-bold">Bs. <?php echo number_format($data['sesion']->monto_inicial, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-success"><i class="bi bi-plus me-1"></i> Ventas/Ingresos:</td>
                                        <td class="text-end fw-bold text-success">Bs. <?php echo number_format($data['sesion']->total_ingresos, 2); ?>
                                            <?php if ((float) ($data['sesion']->total_qr ?? 0) > 0): ?>
                                                <div class="small fw-normal text-muted">incluye QR Bs. <?php echo number_format((float) $data['sesion']->total_qr, 2); ?> (no está en efectivo)</div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-danger"><i class="bi bi-dash me-1"></i> Gastos/Egresos:</td>
                                        <td class="text-end fw-bold text-danger">Bs. <?php echo number_format($data['sesion']->total_egresos, 2); ?></td>
                                    </tr>
                                    <tr class="border-top border-dark">
                                        <td class="pt-2 fw-bold text-dark">Total Sistema:</td>
                                        <td class="pt-2 text-end fw-bold text-dark fs-5">Bs. <?php echo number_format($data['sesion']->monto_final_sistema, 2); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Arqueo Final -->
                            <div class="p-3 border rounded-3 text-center mb-3 <?php echo ($data['sesion']->diferencia == 0) ? 'bg-success-subtle border-success' : 'bg-danger-subtle border-danger'; ?>">
                                <small class="text-uppercase fw-bold d-block mb-1">Resultado de Arqueo</small>
                                <div class="d-flex justify-content-between align-items-end px-3">
                                    <div class="text-start">
                                        <small class="text-muted">Efectivo Real:</small><br>
                                        <span class="fw-bold fs-5">Bs. <?php echo number_format($data['sesion']->monto_final_real, 2); ?></span>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">Diferencia:</small><br>
                                        <?php if ($data['sesion']->diferencia == 0): ?>
                                            <span class="badge bg-success">PERFECTO</span>
                                        <?php elseif ($data['sesion']->diferencia > 0): ?>
                                            <span class="badge bg-primary">+<?php echo $data['sesion']->diferencia; ?> (Sobra)</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?php echo $data['sesion']->diferencia; ?> (Falta)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning text-center small mb-0">
                                <i class="bi bi-lock-fill me-1"></i> Esta caja está cerrada y no admite cambios.
                            </div>

                        </div>
                    </div>

                    <div class="mt-4 text-center no-print d-grid gap-2">
                        <button onclick="window.open('<?php echo URLROOT; ?>/caja/reporte_z/<?php echo $data['sesion']->id; ?>', '_blank')" class="btn btn-dark btn-lg shadow-sm">
                            <i class="bi bi-printer-fill me-2"></i> Imprimir Reporte Z
                        </button>
                        <a href="<?php echo URLROOT; ?>/caja" class="btn btn-outline-secondary">
                            <i class="bi bi-house-door-fill me-2"></i> Ir al Panel Principal
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

<style>
    @media print {
        body * {
            visibility: hidden;
        }

        #ticket-cierre,
        #ticket-cierre * {
            visibility: visible;
        }

        #ticket-cierre {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none !important;
            margin: 0;
        }

        .no-print {
            display: none !important;
        }
    }
</style>
@endsection
