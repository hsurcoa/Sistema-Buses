@extends('layouts.app')

@section('content')
<!-- Main Wrapper -->
<main class="app-main">
    <div class="app-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="fw-bold text-primary"><i class="bi bi-cash-stack me-2"></i>Control de Caja</h2>
                    <p class="text-muted">Gestión de turno actual y arqueo de dinero</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <!-- NUEVO BOTÓN -->
                    <button class="btn btn-primary btn-lg shadow-sm mb-2"
                        data-bs-toggle="modal"
                        data-bs-target="#modalReportesCierre">
                        <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
                        REPORTES DE CIERRE
                    </button>

                    <span class="badge bg-success fs-6 px-3 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-check-circle-fill me-1"></i> CAJA ABIERTA
                    </span>
                    <div class="mt-2 text-muted small">
                        Sucursal: <strong class="text-dark"><?php echo htmlspecialchars($data['caja_abierta']->sucursal_nombre ?? 'Sin sucursal'); ?></strong><br>
                        Abierta por: <strong class="text-dark"><?php echo htmlspecialchars($_SESSION['usuario'] ?? 'Usuario'); ?></strong><br>
                        Desde: <?php echo date('d/m/Y H:i', strtotime($data['caja_abierta']->fecha_apertura)); ?>
                    </div>
                </div>
            </div>

            <!-- Resumen Financiero -->
            <div class="row g-4 mb-5">
                <!-- Tarjeta Monto Inicial -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm bg-light">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small fw-bold">Fondo Inicial (Base)</h6>
                            <div class="d-flex align-items-center mt-2">
                                <div class="icon-box bg-white text-secondary rounded-circle shadow-sm me-3 p-3">
                                    <i class="bi bi-safe2 fs-4"></i>
                                </div>
                                <h3 class="fw-bold mb-0 text-secondary">Bs. <?php echo number_format($data['resumen']->monto_inicial, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Ingresos -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm bg-success text-white">
                        <div class="card-body">
                            <h6 class="text-white-50 text-uppercase small fw-bold">Total Ingresos (+Ventas)</h6>
                            <div class="d-flex align-items-center mt-2">
                                <div class="icon-box bg-white text-success rounded-circle shadow-sm me-3 p-3">
                                    <i class="bi bi-graph-up-arrow fs-4"></i>
                                </div>
                                <h3 class="fw-bold mb-0">Bs. <?php echo number_format($data['resumen']->total_ingresos, 2); ?></h3>
                            </div>
                            <div class="small text-white-50 mt-2">
                                Efectivo: Bs. <?php echo number_format((float) $data['resumen']->total_efectivo, 2); ?>
                                · QR: Bs. <?php echo number_format((float) $data['resumen']->total_qr, 2); ?>
                            </div>
                            <!-- BOTÓN REPORTE -->
                            <button class="btn btn-sm btn-light text-success fw-bold position-absolute top-0 end-0 m-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalReporteIngresos">
                                <i class="bi bi-file-earmark-bar-graph me-1"></i> REPORTES
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Egresos -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm bg-danger text-white">
                        <div class="card-body position-relative">
                            <h6 class="text-white-50 text-uppercase small fw-bold">Total Egresos (-Gastos)</h6>
                            <div class="d-flex align-items-center mt-2">
                                <div class="icon-box bg-white text-danger rounded-circle shadow-sm me-3 p-3">
                                    <i class="bi bi-graph-down-arrow fs-4"></i>
                                </div>
                                <h3 class="fw-bold mb-0">Bs. <?php echo number_format($data['resumen']->total_egresos, 2); ?></h3>
                            </div>
                            <!-- BOTÓN NUEVO GASTO -->
                            <button class="btn btn-sm btn-light text-danger fw-bold position-absolute top-0 end-0 m-3 shadow-sm" onclick="registrarGasto()">
                                <i class="bi bi-plus-circle me-1"></i> NUEVO GASTO
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Formulario de Cierre de Caja -->
                <div class="col-lg-6 mx-auto">
                    <div class="card shadow rounded-3 border-0">
                        <div class="card-header bg-dark text-white py-3">
                            <h5 class="fw-bold mb-0 text-center"><i class="bi bi-shield-lock-fill me-2"></i>Arqueo y Cierre de Caja</h5>
                        </div>
                        <div class="card-body p-4">

                            <?php
                            // Solo el efectivo debe estar en el cajon; los cobros QR van a la cuenta del dueño
                            $saldoSistema = ($data['resumen']->monto_inicial + $data['resumen']->total_efectivo) - $data['resumen']->total_egresos;
                            ?>

                            <div class="alert alert-info border-info d-flex align-items-center mb-4">
                                <i class="bi bi-info-circle-fill fs-3 me-3"></i>
                                <div>
                                    <small class="text-uppercase fw-bold">Efectivo esperado en caja</small>
                                    <h3 class="fw-bold mb-0">Bs. <?php echo number_format($saldoSistema, 2); ?></h3>
                                    <?php if ((float) $data['resumen']->total_qr > 0): ?>
                                        <small>Cobros QR (no están en el cajón, verifique en la cuenta): <strong>Bs. <?php echo number_format((float) $data['resumen']->total_qr, 2); ?></strong></small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <form action="<?php echo URLROOT; ?>/caja/cerrar" method="POST" id="formCierre">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <div class="mb-4">
                                    <label for="monto_real" class="form-label fw-bold text-muted">MONTO REAL (Conteo Físico)</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light fw-bold">Bs.</span>
                                        <input type="number"
                                            name="monto_real"
                                            id="monto_real"
                                            class="form-control fw-bold fs-2 text-center"
                                            step="0.01"
                                            required
                                            placeholder="0.00">
                                    </div>
                                    <div class="form-text text-center">Cuente el dinero físico e ingréselo aquí para calcular diferencias.</div>
                                </div>

                                <div class="d-grid">
                                    <button type="button" class="btn btn-dark btn-lg py-3 rounded-pill shadow-sm" onclick="confirmarCierre()">
                                        CERRAR CAJA Y FINALIZAR TURNO
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODAL REPORTE DE INGRESOS -->
<div class="modal fade" id="modalReporteIngresos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-bar-chart-line-fill me-2"></i>Reporte Histórico de Ingresos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <!-- Filtros -->
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row align-items-end g-2">
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold mb-1">Periodo Rápido</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-success btn-filter active" data-type="dia">Hoy</button>
                                    <button type="button" class="btn btn-outline-success btn-filter" data-type="semana">Semana</button>
                                    <button type="button" class="btn btn-outline-success btn-filter" data-type="mes">Mes</button>
                                    <button type="button" class="btn btn-outline-success btn-filter" data-type="anio">Año</button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted fw-bold mb-1">Rango Personalizado</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" id="reporte_inicio" class="form-control">
                                    <span class="input-group-text bg-white">-</span>
                                    <input type="date" id="reporte_fin" class="form-control">
                                    <button class="btn btn-secondary" onclick="cargarReporte('rango')"><i class="bi bi-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <label class="small text-muted fw-bold mb-1 d-block">Exportar</label>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-success" onclick="exportarExcel()"><i class="bi bi-file-earmark-excel"></i></button>
                                    <button class="btn btn-danger" onclick="exportarPDF()"><i class="bi bi-file-earmark-pdf"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="card border-start border-4 border-success h-100 shadow-sm">
                            <div class="card-body py-2">
                                <div class="small text-muted fw-bold text-uppercase">Total Recaudado</div>
                                <h3 class="fw-bold text-dark mb-0" id="kpi_total">Bs. 0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-start border-4 border-info h-100 shadow-sm">
                            <div class="card-body py-2">
                                <div class="small text-muted fw-bold text-uppercase">Nro. Transacciones</div>
                                <h3 class="fw-bold text-dark mb-0" id="kpi_count">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-start border-4 border-warning h-100 shadow-sm">
                            <div class="card-body py-2">
                                <div class="small text-muted fw-bold text-uppercase">Ticket Promedio</div>
                                <h3 class="fw-bold text-dark mb-0" id="kpi_promedio">Bs. 0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover mb-0" id="tablaReporte">
                            <thead class="bg-dark text-white text-uppercase small">
                                <tr>
                                    <th class="ps-3">Fecha y Hora</th>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th>Usuario</th>
                                    <th class="text-end pe-3">Monto</th>
                                </tr>
                            </thead>
                            <tbody id="tablaReporteBody">
                                <!-- JS INJECT -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL REPORTES DE CIERRE -->
<div class="modal fade" id="modalReportesCierre" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
                    Reportes de Cierre de Caja
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <!-- Filtros -->
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row align-items-end g-2">
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold mb-1">Período Rápido</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-filter-cierre active" data-type="dia">Hoy</button>
                                    <button type="button" class="btn btn-outline-primary btn-filter-cierre" data-type="semana">Semana</button>
                                    <button type="button" class="btn btn-outline-primary btn-filter-cierre" data-type="mes">Mes</button>
                                    <button type="button" class="btn btn-outline-primary btn-filter-cierre" data-type="anio">Año</button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold mb-1">Rango Personalizado</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" id="cierre_inicio" class="form-control">
                                    <span class="input-group-text bg-white">-</span>
                                    <input type="date" id="cierre_fin" class="form-control">
                                    <button class="btn btn-secondary" onclick="cargarReporteCierre('rango')">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card border-start border-4 border-primary h-100 shadow-sm">
                            <div class="card-body py-2">
                                <div class="small text-muted fw-bold text-uppercase">Total Sesiones</div>
                                <h3 class="fw-bold text-dark mb-0" id="kpi_sesiones">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-4 border-success h-100 shadow-sm">
                            <div class="card-body py-2">
                                <div class="small text-muted fw-bold text-uppercase">Total Sistema</div>
                                <h3 class="fw-bold text-dark mb-0" id="kpi_sistema">Bs. 0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-4 border-info h-100 shadow-sm">
                            <div class="card-body py-2">
                                <div class="small text-muted fw-bold text-uppercase">Total Real</div>
                                <h3 class="fw-bold text-dark mb-0" id="kpi_real">Bs. 0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-4 border-warning h-100 shadow-sm">
                            <div class="card-body py-2">
                                <div class="small text-muted fw-bold text-uppercase">Diferencia</div>
                                <h3 class="fw-bold text-dark mb-0" id="kpi_diferencia">Bs. 0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover mb-0" id="tablaCierres">
                            <thead class="bg-dark text-white text-uppercase small">
                                <tr>
                                    <th class="ps-3">Turno</th>
                                    <th>Cajero</th>
                                    <th>Apertura</th>
                                    <th>Cierre</th>
                                    <th class="text-end">Inicial</th>
                                    <th class="text-end">Ingresos</th>
                                    <th class="text-end">Egresos</th>
                                    <th class="text-end">Sistema</th>
                                    <th class="text-end">Real</th>
                                    <th class="text-end">Diferencia</th>
                                    <th class="text-center pe-3">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tablaCierresBody">
                                <!-- JS INJECT -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <div class="btn-group" role="group">
                    <button class="btn btn-success" onclick="exportarCierreExcel()">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </button>
                    <button class="btn btn-primary" onclick="exportarCierreWord()">
                        <i class="bi bi-file-earmark-word me-1"></i> Word
                    </button>
                    <button class="btn btn-danger" onclick="exportarCierrePDF('carta')">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF Carta
                    </button>
                    <button class="btn btn-warning" onclick="exportarCierrePDF('a4')">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF A4
                    </button>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- LIBRERÍAS DE EXPORTACIÓN -->
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    // Token de Seguridad Global
    const CSRF_TOKEN = '<?php echo csrf_token(); ?>';

    function confirmarCierre() {
        const montoReal = document.getElementById('monto_real').value;

        if (!montoReal) {
            Swal.fire('Atención', 'Debe ingresar el monto contado físicamente', 'warning');
            return;
        }

        Swal.fire({
            title: '¿Está seguro de cerrar caja?',
            text: "Esta acción finalizará su turno y no podrá registrar más ventas.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, Cerrar Caja',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formCierre').submit();
            }
        })
    }

    // --- NUEVA LÓGICA DE GASTOS ---
    function registrarGasto() {
        Swal.fire({
            title: 'Registrar Salida de Dinero',
            html: `
                <div class="text-start">
                    <label class="form-label small text-muted fw-bold">Monto a retirar (Bs.)</label>
                    <input type="number" id="gasto_monto" class="form-control form-control-lg mb-3" placeholder="0.00" step="0.50">
                    
                    <label class="form-label small text-muted fw-bold">Motivo / Descripción</label>
                    <input type="text" id="gasto_desc" class="form-control mb-3" placeholder="Ej: Compra de material de limpieza">
                    
                    <hr class="my-4">
                    <label class="form-label small text-danger fw-bold"><i class="bi bi-shield-lock me-1"></i> Autorización de Administrador</label>
                    <input type="password" id="admin_pass" class="form-control" placeholder="Ingrese Contraseña ADMIN">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Registrar Salida',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            focusConfirm: false,
            preConfirm: () => {
                const monto = Swal.getPopup().querySelector('#gasto_monto').value;
                const desc = Swal.getPopup().querySelector('#gasto_desc').value;
                const pass = Swal.getPopup().querySelector('#admin_pass').value;

                if (!monto || !desc || !pass) {
                    Swal.showValidationMessage('Todos los campos son obligatorios');
                }
                return {
                    monto: monto,
                    desc: desc,
                    pass: pass
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const datos = result.value;

                // Enviar AJAX
                $.ajax({
                    url: '<?php echo URLROOT; ?>/caja/registrar_gasto_ajax',
                    type: 'POST',
                    data: {
                        csrf_token: CSRF_TOKEN,
                        monto: datos.monto,
                        descripcion: datos.desc,
                        password: datos.pass
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Gasto Registrado',
                                text: 'Se ha descontado el monto de la caja.',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload(); // Recargar para actualizar tarjetas
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Fallo de conexión con el servidor', 'error');
                    }
                });
            }
        });
    }

    // --- NUEVA LÓGICA DE REPORTES ---
    $(document).ready(function() {
        // Cargar reporte del día al abrir modal
        $('#modalReporteIngresos').on('shown.bs.modal', function() {
            cargarReporte('dia');
        });

        // Botones de filtro
        $('.btn-filter').click(function() {
            $('.btn-filter').removeClass('active');
            $(this).addClass('active');
            const type = $(this).data('type');
            cargarReporte(type);
        });
    });

    function cargarReporte(tipo) {
        let inicio = $('#reporte_inicio').val();
        let fin = $('#reporte_fin').val();

        // Mostrar Loading...
        $('#tablaReporteBody').html('<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');

        $.ajax({
            url: '<?php echo URLROOT; ?>/caja/obtener_ingresos_ajax',
            type: 'POST',
            data: {
                tipo: tipo,
                fecha_inicio: inicio,
                fecha_fin: fin
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    renderizarTabla(res.data);
                    calcularKPIs(res.data);
                } else {
                    $('#tablaReporteBody').html('<tr><td colspan="5" class="text-center text-danger py-3">' + res.message + '</td></tr>');
                }
            },
            error: function() {
                $('#tablaReporteBody').html('<tr><td colspan="5" class="text-center text-danger py-3">Error de conexión</td></tr>');
            }
        });
    }

    function renderizarTabla(data) {
        let html = '';
        if (data.length === 0) {
            html = '<tr><td colspan="5" class="text-center text-muted py-4">No se encontraron movimientos en este periodo.</td></tr>';
        } else {
            data.forEach(item => {
                html += `
                    <tr>
                        <td class="ps-3 align-middle">${item.fecha_creacion}</td>
                        <td class="align-middle"><span class="badge bg-light text-dark border">${item.tipo}</span></td>
                        <td class="align-middle">${item.descripcion}</td>
                        <td class="align-middle text-muted small text-uppercase">${item.usuario}</td>
                        <td class="text-end fw-bold text-success pe-3">Bs. ${parseFloat(item.monto).toFixed(2)}</td>
                    </tr>
                `;
            });
        }
        $('#tablaReporteBody').html(html);
    }

    function calcularKPIs(data) {
        let total = 0;
        data.forEach(i => total += parseFloat(i.monto));

        let count = data.length;
        let promedio = count > 0 ? total / count : 0;

        $('#kpi_total').text('Bs. ' + total.toFixed(2));
        $('#kpi_count').text(count);
        $('#kpi_promedio').text('Bs. ' + promedio.toFixed(2));
    }

    function exportarExcel() {
        const table = document.getElementById('tablaReporte');
        const wb = XLSX.utils.table_to_book(table, {
            sheet: "Ingresos"
        });
        XLSX.writeFile(wb, 'Reporte_Ingresos_' + new Date().toISOString().slice(0, 10) + '.xlsx');
    }

    function exportarPDF() {
        const {
            jsPDF
        } = window.jspdf;
        const doc = new jsPDF();

        doc.text("Reporte de Ingresos - Caja", 14, 15);
        doc.setFontSize(10);
        doc.text("Generado: " + new Date().toLocaleString(), 14, 22);

        doc.autoTable({
            html: '#tablaReporte',
            startY: 25
        });
        doc.save('Reporte_Ingresos.pdf');
    }

    // --- NUEVO: FUNCIONALIDAD REPORTES DE CIERRE ---
    let dataCierreActual = [];

    $(document).ready(function() {
        $('#modalReportesCierre').on('shown.bs.modal', function() {
            // Cargar datos por defecto al abrir (hoy)
            cargarReporteCierre('dia');
        });

        // Manejar clics en botones de filtro rápido
        $('.btn-filter-cierre').click(function() {
            $('.btn-filter-cierre').removeClass('active');
            $(this).addClass('active');
            cargarReporteCierre($(this).data('type'));
        });
    });

    function cargarReporteCierre(tipo) {
        let inicio = $('#cierre_inicio').val();
        let fin = $('#cierre_fin').val();

        // Mostrar spinner de carga
        $('#tablaCierresBody').html('<tr><td colspan="11" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>');

        $.ajax({
            url: '<?php echo URLROOT; ?>/caja/obtener_reportes_cierre_ajax',
            type: 'POST',
            data: {
                tipo: tipo,
                fecha_inicio: inicio,
                fecha_fin: fin
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    dataCierreActual = res.data;
                    renderizarTablaCierres(res.data);
                    actualizarKPIsCierre(res.stats);

                    // Actualizar inputs de fecha si vienen del servidor para mostrar el rango real
                    if (res.periodo) {
                        $('#cierre_inicio').val(res.periodo.inicio);
                        $('#cierre_fin').val(res.periodo.fin);
                    }
                } else {
                    $('#tablaCierresBody').html('<tr><td colspan="11" class="text-center text-danger py-3">' + res.message + '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error AJAX:", error);
                $('#tablaCierresBody').html('<tr><td colspan="11" class="text-center text-danger py-3">Error de conexión al obtener reportes</td></tr>');
            }
        });
    }

    function renderizarTablaCierres(data) {
        let html = '';
        if (!data || data.length === 0) {
            html = '<tr><td colspan="11" class="text-center text-muted py-4">No hay cierres en este período</td></tr>';
        } else {
            data.forEach(item => {
                let badgeDif = parseFloat(item.diferencia) === 0 ? '<span class="badge bg-success">PERFECTO</span>' :
                    parseFloat(item.diferencia) > 0 ? '<span class="badge bg-primary">+' + item.diferencia + '</span>' :
                    '<span class="badge bg-danger">' + item.diferencia + '</span>';

                html += `<tr>
                    <td class="ps-3">#${item.id}</td>
                    <td>${item.cajero_nombre}</td>
                    <td class="small">${item.fecha_apertura}</td>
                    <td class="small">${item.fecha_cierre}</td>
                    <td class="text-end">Bs. ${parseFloat(item.monto_inicial).toFixed(2)}</td>
                    <td class="text-end text-success">Bs. ${parseFloat(item.total_ingresos || 0).toFixed(2)}</td>
                    <td class="text-end text-danger">Bs. ${parseFloat(item.total_egresos || 0).toFixed(2)}</td>
                    <td class="text-end fw-bold">Bs. ${parseFloat(item.monto_final_sistema).toFixed(2)}</td>
                    <td class="text-end fw-bold">Bs. ${parseFloat(item.monto_final_real).toFixed(2)}</td>
                    <td class="text-end">${badgeDif}</td>
                    <td class="text-center"><a href="<?php echo URLROOT; ?>/caja/reporte/${item.id}" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-eye"></i></a></td>
                </tr>`;
            });
        }
        $('#tablaCierresBody').html(html);
    }

    function actualizarKPIsCierre(stats) {
        if (!stats) return;
        $('#kpi_sesiones').text(stats.total_sesiones || 0);
        $('#kpi_sistema').text('Bs. ' + parseFloat(stats.suma_sistema || 0).toFixed(2));
        $('#kpi_real').text('Bs. ' + parseFloat(stats.suma_real || 0).toFixed(2));

        let diff = parseFloat(stats.suma_diferencias || 0);
        let colorClass = diff === 0 ? 'text-dark' : (diff > 0 ? 'text-primary' : 'text-danger');
        let signo = diff > 0 ? '+' : '';

        $('#kpi_diferencia').removeClass('text-primary text-danger text-dark').addClass(colorClass).text('Bs. ' + signo + diff.toFixed(2));
    }

    function exportarCierreExcel() {
        if (!dataCierreActual || dataCierreActual.length === 0) {
            if (typeof Swal !== 'undefined') Swal.fire('Atención', 'No hay datos para exportar', 'warning');
            else alert('No hay datos para exportar');
            return;
        }
        enviarFormExport('<?php echo URLROOT; ?>/caja/exportar_excel');
    }

    function exportarCierreWord() {
        if (!dataCierreActual || dataCierreActual.length === 0) {
            if (typeof Swal !== 'undefined') Swal.fire('Atención', 'No hay datos para exportar', 'warning');
            else alert('No hay datos para exportar');
            return;
        }
        enviarFormExport('<?php echo URLROOT; ?>/caja/exportar_word');
    }

    function exportarCierrePDF(tamano) {
        if (!dataCierreActual || dataCierreActual.length === 0) {
            if (typeof Swal !== 'undefined') Swal.fire('Atención', 'No hay datos para exportar', 'warning');
            else alert('No hay datos para exportar');
            return;
        }
        enviarFormExport('<?php echo URLROOT; ?>/caja/exportar_pdf/' + tamano);
    }

    function enviarFormExport(url) {
        // Crear formulario invisible para enviar POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.target = '_blank'; // Abrir en nueva pestaña

        const inputInicio = document.createElement('input');
        inputInicio.type = 'hidden';
        inputInicio.name = 'fecha_inicio';
        inputInicio.value = $('#cierre_inicio').val();
        form.appendChild(inputInicio);

        const inputFin = document.createElement('input');
        inputFin.type = 'hidden';
        inputFin.name = 'fecha_fin';
        inputFin.value = $('#cierre_fin').val();
        form.appendChild(inputFin);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
</script>
@endsection
