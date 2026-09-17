@extends('layouts.app')

@section('content')

<!-- ESTILOS PERSONALIZADOS PREMIUM -->
<style>
    .card-premium-tabs {
        border: none;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        border-radius: 12px;
        overflow: hidden;
    }

    .card-premium-tabs .card-header {
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 0;
    }

    .card-premium-tabs .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 600;
        padding: 1.2rem 2rem;
        transition: all 0.3s;
        border-bottom: 3px solid transparent;
    }

    .card-premium-tabs .nav-tabs .nav-link:hover {
        color: #007bff;
        background: #f8f9fa;
    }

    .card-premium-tabs .nav-tabs .nav-link.active {
        color: #007bff;
        border-bottom: 3px solid #007bff;
        background: transparent;
    }

    /* MODAL MODERNO */
    .modal-modern .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .modal-modern .modal-header {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 1.5rem 2rem;
        border: none;
    }

    .modal-modern .modal-header .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
        padding: 0;
        margin: 0;
    }

    .modal-modern .info-viaje {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e9ecef;
    }

    .modal-modern .info-label {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
        color: #888;
        font-weight: 700;
        margin-bottom: 0.2rem;
    }

    .modal-modern .info-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }

    .btn-export-custom {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 600;
        box-shadow: 0 4px 6px rgba(50, 50, 93, .11), 0 1px 3px rgba(0, 0, 0, .08);
        transition: all 0.15s ease;
        border: none;
    }

    .btn-export-custom:hover {
        transform: translateY(-1px);
        box-shadow: 0 7px 14px rgba(50, 50, 93, .1), 0 3px 6px rgba(0, 0, 0, .08);
    }

    .table-modern thead th {
        background-color: #fff;
        color: #495057;
        font-weight: 700;
        border-bottom: 2px solid #e9ecef;
        text-transform: uppercase;
        font-size: 0.85rem;
    }

    .table-modern tbody td {
        vertical-align: middle;
        padding: 1rem 0.75rem;
        border-bottom: 1px solid #f0f0f0;
    }
</style>

<!-- Content Wrapper -->
<div class="content-wrapper" style="background: #f4f6f9;">
    <!-- Header -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-3 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark font-weight-bold" style="font-family: 'Segoe UI', sans-serif;">
                        <i class="fas fa-users-cog text-primary me-2"></i> Gestión de Pasajeros
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end bg-transparent p-0">
                        <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                        <li class="breadcrumb-item active">Pasajeros</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- MAIN CARD TABS -->
            <!-- MAIN CARD TABS -->
            <div class="card card-premium-tabs mb-4">
                <div class="card-header">
                    <ul class="nav nav-tabs justify-content-start" id="premium-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-manifiesto" data-bs-toggle="tab" href="#content-manifiesto" role="tab" aria-controls="content-manifiesto" aria-selected="true">
                                <i class="fas fa-list-alt me-2"></i> Manifiesto de Viaje
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-historico" data-bs-toggle="tab" href="#content-historico" role="tab" aria-controls="content-historico" aria-selected="false">
                                <i class="fas fa-history me-2"></i> Histórico por Fechas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-persona" data-bs-toggle="tab" href="#content-persona" role="tab" aria-controls="content-persona" aria-selected="false">
                                <i class="fas fa-user-check me-2"></i> Búsqueda Individual
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="premium-tabs-content">

                        <!-- TAB 1: MANIFIESTO -->
                        <div class="tab-pane fade show active" id="content-manifiesto" role="tabpanel">
                            <form id="formFiltros">
                                <div class="row align-items-end p-3 bg-light rounded mb-4 border">
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label class="text-muted font-weight-bold small text-uppercase">Fecha de Viaje</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0"><i class="far fa-calendar-alt text-primary"></i></span>
                                                <input type="date" class="form-control border-start-0" name="fecha" id="filtroFecha" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label class="text-muted font-weight-bold small text-uppercase">Ruta</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-map-marked-alt text-danger"></i></span>
                                                <select class="form-control border-start-0" name="ruta" id="filtroRuta">
                                                    <option value="">Todas las rutas</option>
                                                    <?php if (isset($data['rutas'])): ?><?php foreach ($data['rutas'] as $ruta) : ?><option value="<?php echo $ruta->id; ?>"><?php echo $ruta->origen . ' - ' . $ruta->destino; ?></option><?php endforeach; ?><?php endif; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary btn-block shadow-sm font-weight-bold">
                                            <i class="fas fa-search me-2"></i> BUSCAR SALIDAS
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <!-- RESULTADOS TABLA (AJAX) -->
                            <div id="contenedor-resultados" class="mt-4">
                                <div class="text-center py-5 text-muted">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7887/7887104.png" width="100" class="mb-3 opacity-50" style="filter: grayscale(100%);">
                                    <h5 class="font-weight-normal">Inicia una búsqueda para ver los viajes programados</h5>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: HISTÓRICO -->
                        <div class="tab-pane fade" id="content-historico" role="tabpanel">

                            <!-- Botones de Filtro Rápido -->
                            <div class="mb-3 d-flex justify-content-center">
                                <div class="btn-group shadow-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" onclick="setFecha('hoy')">Hoy</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="setFecha('ayer')">Ayer</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="setFecha('semana')">Esta Semana</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="setFecha('mes')">Este Mes</button>
                                </div>
                            </div>

                            <form id="formHistorico">
                                <div class="row align-items-end p-3 bg-light rounded mb-4 border">
                                    <div class="col-md-3">
                                        <label class="text-muted font-weight-bold small text-uppercase">Desde</label>
                                        <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted font-weight-bold small text-uppercase">Hasta</label>
                                        <input type="date" class="form-control" name="fecha_fin" id="fecha_fin" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted font-weight-bold small text-uppercase">Ruta</label>
                                        <select class="form-control" name="ruta_hist">
                                            <option value="">Todas</option>
                                            <?php if (isset($data['rutas'])): ?><?php foreach ($data['rutas'] as $ruta) : ?><option value="<?php echo $ruta->id; ?>"><?php echo $ruta->origen . ' - ' . $ruta->destino; ?></option><?php endforeach; ?><?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-info btn-block shadow-sm font-weight-bold text-white">
                                            <i class="fas fa-search me-1"></i> BUSCAR REGISTROS
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Botones de Exportación (Ocultos inicialmente) -->
                            <div id="export-buttons-container" class="d-none justify-content-end mb-3 animation-fade-in">
                                <span class="align-self-center me-2 text-muted small font-weight-bold text-uppercase">Exportar Reporte:</span>
                                <div class="btn-group shadow-sm">
                                    <button class="btn btn-success btn-sm btn-export-custom text-white" onclick="exportHistorico('excel')">
                                        <i class="fas fa-file-excel me-1"></i> Excel
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-export-custom text-white" onclick="exportHistorico('pdf', 'a4')">
                                        <i class="fas fa-file-pdf me-1"></i> PDF A4
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-export-custom text-white" onclick="exportHistorico('pdf', 'letter')">
                                        <i class="fas fa-file-pdf me-1"></i> PDF Carta
                                    </button>
                                    <button class="btn btn-primary btn-sm btn-export-custom text-white" onclick="exportHistorico('word')">
                                        <i class="fas fa-file-word me-1"></i> Word
                                    </button>
                                </div>
                            </div>

                            <div id="contenedor-historico" class="mt-4 text-center text-muted">Use los filtros para generar un reporte histórico.</div>
                        </div>

                        <!-- TAB 3: PERSONA -->
                        <div class="tab-pane fade" id="content-persona" role="tabpanel">
                            <form id="formPersona">
                                <div class="input-group input-group-lg shadow-sm rounded overflow-hidden">
                                    <span class="input-group-text bg-white border-0 ps-4"><i class="fas fa-search text-muted"></i></span>
                                    <input type="search" class="form-control border-0 ps-2" placeholder="Ingrese CI o Apellidos del pasajero" name="criterio" required>
                                    <button type="submit" class="btn btn-primary px-5 font-weight-bold">BUSCAR</button>
                                </div>
                            </form>
                            <div id="contenedor-persona" class="mt-5 text-center">
                                <i class="fas fa-user-tag fa-4x text-light mb-3"></i>
                                <p class="text-muted">Rastrea el historial completo de un pasajero.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- MODAL MANIFIESTO MODERNO -->
<div class="modal fade modal-modern" id="modalManifiesto" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h4 class="modal-title font-weight-bold mb-0"><i class="fas fa-clipboard-list me-2"></i> Manifiesto de Pasajeros</h4>
                    <p class="mb-0 text-white-50 small">Lista oficial y control de abordaje</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white">

                <!-- HEADER DEL VIAJE -->
                <div class="info-viaje shadow-sm">
                    <div class="row text-center" id="detalleViajeHeader">
                        <!-- JS INJECT -->
                    </div>
                </div>

                <!-- BOTONES EXPORTACIÓN -->
                <div class="d-flex justify-content-end mb-3">
                    <div class="btn-group shadow-sm">
                        <button class="btn btn-light border" disabled><i class="fas fa-download me-1 text-muted"></i> Exportar:</button>
                        <button type="button" class="btn btn-white btn-export-custom text-success border" onclick="exportManifiesto('excel')">
                            <i class="fas fa-file-excel fa-lg"></i>
                        </button>
                        <button type="button" class="btn btn-white btn-export-custom text-danger border" onclick="exportManifiesto('pdf')">
                            <i class="fas fa-file-pdf fa-lg"></i>
                        </button>
                        <button type="button" class="btn btn-white btn-export-custom text-primary border" onclick="exportManifiesto('word')">
                            <i class="fas fa-file-word fa-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- TABLA PASAJEROS -->
                <div class="table-responsive">
                    <table class="table table-modern table-hover" id="tablaPasajeros">
                        <thead>
                            <tr>
                                <th class="text-center" width="50">#</th>
                                <th>N° Documento</th>
                                <th>Nombre Completo</th>
                                <th>Nacionalidad</th>
                                <th>Destino</th>
                                <th class="text-end">Boleto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- JS INJECT -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary font-weight-bold px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts Libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    $(document).ready(function() {

        // --- 0. FIX TABS (Explicit JS) ---
        $('#premium-tabs a').on('click', function(e) {
            e.preventDefault();
            $(this).tab('show');
        });

        // --- 1. LÓGICA DE BÚSQUEDA: MANIFIESTO (TAB 1) ---
        $('#formFiltros').on('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Buscando Manifiestos...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            let formData = new FormData(this);
            $.ajax({
                url: '<?php echo URLROOT; ?>/reportes/buscar_viajes_ajax',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.status === 'success' && response.data.length > 0) {
                        renderizarResultados(response.data, '#contenedor-resultados');
                    } else {
                        $('#contenedor-resultados').html(`
                            <div class="alert alert-light text-center border mt-4 p-4 shadow-sm">
                                <i class="fas fa-search-minus fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No se encontraron viajes para esta fecha.</p>
                            </div>
                        `);
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo contactar al servidor'
                    });
                }
            });
        });

        // --- 2. LÓGICA DE BÚSQUEDA: HISTÓRICO (TAB 2) ---
        $('#formHistorico').on('submit', function(e) {
            e.preventDefault();

            let formData = new FormData(this);

            Swal.fire({
                title: 'Generando Reporte...',
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            $.ajax({
                url: '<?php echo URLROOT; ?>/reportes/buscar_historico_ajax',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.status === 'success' && response.data.length > 0) {
                        renderizarResultados(response.data, '#contenedor-historico');
                    } else {
                        $('#contenedor-historico').html(`
                            <div class="alert alert-info text-center border mt-4 shadow-sm">
                                <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                <h5>Sin Resultados</h5>
                                <p class="mb-0">No se encontraron viajes en el rango de fechas seleccionado.</p>
                            </div>
                        `);
                    }
                },
                error: function() {
                    Swal.fire('Error', 'No se pudo generar el reporte', 'error');
                }
            });
        });

        // --- 3. LÓGICA DE BÚSQUEDA: PERSONA (TAB 3) ---
        $('#formPersona').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);

            Swal.fire({
                title: 'Buscando Pasajero...',
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            $.ajax({
                url: '<?php echo URLROOT; ?>/reportes/buscar_persona_ajax',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.status === 'success' && response.data.length > 0) {
                        renderizarResultadosPersona(response.data);
                    } else {
                        $('#contenedor-persona').html(`
                            <div class="alert alert-warning text-center border mt-4 shadow-sm">
                                <i class="fas fa-user-slash fa-2x mb-2"></i>
                                <h5>Pasajero no encontrado</h5>
                                <p class="mb-0">No hay registros de viajes para el criterio ingresado.</p>
                            </div>
                        `);
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error al buscar pasajero', 'error');
                }
            });
        });


        // --- RENDERIZADO DE RESULTADOS (MANIFIESTO / HISTORICO) ---
        function renderizarResultados(viajes, containerId = '#contenedor-resultados') {

            // Mostrar botones de exportación si estamos en la pestaña Histórico
            if (containerId === '#contenedor-historico') {
                $('#export-buttons-container').removeClass('d-none').addClass('d-flex');
            }

            let html = `
            <div class="card shadow border-0 mt-3 animation-fade-in">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0" id="tablaResultadosHistorico">
                        <thead class="bg-light">
                            <tr class="text-muted text-uppercase small">
                                <th class="ps-4">Fecha/Hora Salida</th>
                                <th>Ruta</th>
                                <th>Bus / Placa</th>
                                <th>Chofer Asignado</th>
                                <th>Ventas Realizadas</th>
                                <th>Ocupación</th>
                                <th class="text-center pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>`;

            viajes.forEach(v => {
                let ocupados = parseInt(v.total_pasajeros);
                let capacidad = parseInt(v.capacidad);
                let porcentaje = capacidad > 0 ? Math.round((ocupados / capacidad) * 100) : 0;

                // Color Logic
                let color = 'bg-success';
                let textClass = 'text-success';

                if (porcentaje > 50) {
                    color = 'bg-warning';
                    textClass = 'text-warning';
                }
                if (porcentaje > 90) {
                    color = 'bg-danger';
                    textClass = 'text-danger';
                }

                let placa = v.placa && v.placa !== 'Sin Asignar' ?
                    `<span class="badge bg-light text-dark border font-weight-bold px-2 py-1"><i class="fas fa-bus me-1 text-muted"></i>${v.placa}</span>` :
                    '<span class="badge bg-danger px-2">Sin Bus</span>';

                // Nueva lógica para mostrar fechas de venta
                let ventasInfo = '';
                if (ocupados > 0 && v.primera_venta) {
                    if (v.primera_venta === v.ultima_venta) {
                        // Todas las ventas en un solo día
                        ventasInfo = `
                            <div class="text-center">
                                <span class="badge bg-info text-white px-3 py-2">
                                    <i class="fas fa-calendar-check me-1"></i>
                                    ${v.primera_venta}
                                </span>
                                <div class="small text-muted mt-1">${ocupados} pasaje${ocupados > 1 ? 's' : ''}</div>
                            </div>`;
                    } else {
                        // Ventas en rango de fechas
                        ventasInfo = `
                            <div class="text-center">
                                <div class="small text-muted mb-1">Desde</div>
                                <span class="badge bg-primary text-white px-2 py-1 d-block mb-1">
                                    <i class="fas fa-calendar-alt me-1"></i>${v.primera_venta}
                                </span>
                                <div class="small text-muted mb-1">Hasta</div>
                                <span class="badge bg-primary text-white px-2 py-1 d-block">
                                    <i class="fas fa-calendar-alt me-1"></i>${v.ultima_venta}
                                </span>
                                <div class="small text-muted mt-1">${ocupados} pasaje${ocupados > 1 ? 's' : ''}</div>
                            </div>`;
                    }
                } else {
                    ventasInfo = '<div class="text-center text-muted small"><i class="fas fa-minus-circle"></i> Sin ventas</div>';
                }

                html += `
                <tr>
                    <td class="ps-4 align-middle">
                        <h5 class="mb-0 font-weight-bold text-dark">${v.hora_salida}</h5>
                        <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> ${v.fecha_salida}</small>
                    </td>
                    <td class="align-middle">
                        <div class="font-weight-bold text-primary mb-1">${v.nombre_ruta}</div>
                    </td>
                    <td class="align-middle">${placa}</td>
                    <td class="align-middle">
                        <div class="text-sm font-weight-bold text-uppercase text-dark">${v.chofer || '<span class="text-muted font-weight-normal font-italic">No Asignado</span>'}</div>
                    </td>
                    <td class="align-middle">${ventasInfo}</td>
                    <td class="align-middle" style="min-width: 140px;">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="font-weight-bold text-dark">${ocupados}/${capacidad}</span>
                            <span class="${textClass} font-weight-bold">${porcentaje}%</span>
                        </div>
                        <div class="progress rounded-pill" style="height: 8px; background-color: #e9ecef;">
                            <div class="progress-bar ${color}" role="progressbar" style="width: ${porcentaje}%"></div>
                        </div>
                    </td>
                    <td class="align-middle text-center pe-4">
                        <button class="btn btn-primary shadow-sm rounded-pill px-4 btn-ver-manifiesto transition-hover" data-id="${v.id}">
                            <i class="fas fa-list-alt me-2"></i> Ver Lista
                        </button>
                    </td>
                </tr>`;
            });
            html += `</tbody></table></div></div>`;
            $(containerId).html(html).hide().fadeIn(300);
        }

        // --- FUNCIÓN GLOBAL: SET FECHA RÁPIDA ---
        window.setFecha = function(tipo) {
            const hoy = new Date();
            let inicio = new Date();
            let fin = new Date();

            switch (tipo) {
                case 'hoy':
                    // Inicio y Fin = Hoy
                    break;
                case 'ayer':
                    inicio.setDate(hoy.getDate() - 1);
                    fin.setDate(hoy.getDate() - 1);
                    break;
                case 'semana':
                    // Primer día de la semana (Lunes)
                    const diaSemana = hoy.getDay() || 7; // Convertir Domingo (0) a 7
                    inicio.setDate(hoy.getDate() - diaSemana + 1);
                    // Fin = Hoy (o Domingo si prefieres semana completa)
                    break;
                case 'mes':
                    inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
                    fin = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0); // Último día del mes
                    break;
            }

            // Formatear a YYYY-MM-DD
            const formato = (d) => d.toISOString().split('T')[0];
            document.getElementById('fecha_inicio').value = formato(inicio);
            document.getElementById('fecha_fin').value = formato(fin);

            // Disparar submit automáticamente para UX fluida
            $("#formHistorico").submit();
        };

        // --- FUNCIÓN GLOBAL: EXPORTAR HISTÓRICO ---
        window.exportHistorico = function(type, format = 'a4') {
            const table = document.getElementById('tablaResultadosHistorico');
            if (!table) {
                Swal.fire('Atención', 'No hay datos para exportar.', 'warning');
                return;
            }

            const date = new Date().toISOString().slice(0, 10);

            // Clonar tabla para eliminar columna de acciones antes de exportar
            const tableClone = table.cloneNode(true);
            // Eliminar última columna (Acciones) de cada fila
            tableClone.querySelectorAll('tr').forEach(tr => {
                if (tr.cells.length > 0) tr.deleteCell(-1);
            });

            if (type === 'excel') {
                const wb = XLSX.utils.table_to_book(tableClone, {
                    sheet: "Reporte Historico"
                });
                XLSX.writeFile(wb, `Reporte_Pasajeros_${date}.xlsx`);
            } else if (type === 'pdf') {
                const {
                    jsPDF
                } = window.jspdf;
                // Configuración A4 vs Carta
                const orientation = 'p'; // portrait
                const unit = 'mm';
                const size = (format === 'letter') ? 'letter' : 'a4';

                const doc = new jsPDF(orientation, unit, size);

                doc.text("REPORTE HISTÓRICO DE PASAJEROS", 14, 15);
                doc.setFontSize(10);
                doc.text("Generado el: " + new Date().toLocaleString(), 14, 22);

                doc.autoTable({
                    html: tableClone,
                    startY: 25,
                    theme: 'grid',
                    styles: {
                        fontSize: 8
                    }
                });

                doc.save(`Reporte_Historico_${format}_${date}.pdf`);
            } else if (type === 'word') {
                const html = `
            <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
            <head><meta charset='utf-8'><title>Reporte Historico</title>
            <style>
                body{font-family: Arial, sans-serif;}
                table{border-collapse:collapse;width:100%;}
                th{background-color: #eee; color: #333; padding: 5px; border: 1px solid #999;}
                td{border: 1px solid #999; padding: 5px;}
            </style>
            </head><body>
            <h3>REPORTE HISTÓRICO DE PASAJEROS</h3>
            ${tableClone.outerHTML}
            </body></html>`;

                const blob = new Blob(['\ufeff', html], {
                    type: 'application/msword'
                });
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = `Reporte_Historico_${date}.doc`;
                link.click();
            }
        };

        // --- RENDERIZADO DE RESULTADOS (PERSONA) ---
        function renderizarResultadosPersona(viajes) {
            let p = viajes[0]; // Datos personales del primero
            let nombreCompleto = `${p.nombres} ${p.apellidos}`;

            let htmlHeader = `
                <div class="d-flex align-items-center mb-4 bg-white p-3 rounded shadow-sm border">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                        <i class="fas fa-user fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-0 text-dark">${nombreCompleto}</h5>
                        <div class="text-muted small"><i class="fas fa-id-card me-1"></i> CI: ${p.numero_documento}</div>
                    </div>
                    <div class="ml-auto text-end">
                        <div class="font-weight-bold h4 mb-0 text-primary">${viajes.length}</div>
                        <div class="text-muted small text-uppercase font-weight-bold">Viajes Encontrados</div>
                    </div>
                </div>
            `;

            let htmlTable = `
            <div class="card shadow border-0">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="ps-4">Fecha Viaje</th>
                                <th>Ruta</th>
                                <th>Asiento</th>
                                <th>Estado</th>
                                <th>Costo</th>
                                <th class="text-end pe-4">Detalles</th>
                            </tr>
                        </thead>
                        <tbody>`;

            viajes.forEach(v => {
                // Lógica de color forzado para Estado
                let estadoStyle = '';
                let icon = '';

                if (v.estado === 'vendido') {
                    estadoStyle = 'background-color: #28a745 !important; color: #fff !important;';
                    icon = '<i class="fas fa-check-circle me-1"></i>';
                } else {
                    estadoStyle = 'background-color: #ffc107 !important; color: #212529 !important;';
                    icon = '<i class="fas fa-clock me-1"></i>';
                }

                htmlTable += `
                <tr>
                    <td class="ps-4 align-middle">
                        <div class="font-weight-bold text-dark">${v.fecha_salida}</div>
                        <small class="text-muted font-weight-bold"><i class="far fa-clock text-primary me-1"></i>${v.hora_salida}</small>
                    </td>
                    <td class="align-middle font-weight-bold text-dark">${v.ruta}</td>
                    <td class="align-middle text-center">
                        <div class="rounded-circle bg-white border border-dark mx-auto shadow-sm font-weight-bold text-dark d-flex align-items-center justify-content-center" 
                             style="width: 35px; height: 35px; font-size: 1rem;">
                            ${v.numero_asiento}
                        </div>
                    </td>
                    <td class="align-middle">
                        <span class="badge shadow-sm text-uppercase px-3 py-2" style="${estadoStyle} font-size: 0.75rem; letter-spacing: 0.5px;">
                            ${icon} ${v.estado}
                        </span>
                    </td>
                    <td class="align-middle font-weight-bold text-primary">Bs. ${v.precio_final}</td>
                    <td class="align-middle text-end pe-4">
                        <button class="btn btn-sm btn-outline-primary font-weight-bold shadow-sm btn-ver-manifiesto" data-id="${v.viaje_id}">
                            <i class="fas fa-eye me-1"></i> Ver Viaje
                        </button>
                    </td>
                </tr>`;
            });

            htmlTable += `</tbody></table></div></div>`;

            $('#contenedor-persona').html(htmlHeader + htmlTable).hide().fadeIn(300);
        }

        // --- 4. ABRIR MODAL MANIFIESTO (MEJORADO) ---
        $(document).on('click', '.btn-ver-manifiesto', function() {
            let viajeId = $(this).data('id');
            $('#modalManifiesto').data('viaje-id', viajeId);

            Swal.fire({
                title: 'Cargando Manifiesto...',
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            $.ajax({
                url: '<?php echo URLROOT; ?>/reportes/obtener_manifiesto_ajax/' + viajeId,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        let v = res.viaje;

                        // Header Mejorado
                        $('#detalleViajeHeader').html(`
                            <div class="col-md-3 mb-3 mb-md-0 position-relative">
                                <label class="small text-muted font-weight-bold text-uppercase mb-1">Ruta</label>
                                <div class="h5 font-weight-bold text-primary mb-0">${v.nombre_ruta}</div>
                                <div class="vr position-absolute d-none d-md-block" style="right:0; top:10%; height:80%; border-right:1px solid #ddd;"></div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0 position-relative">
                                <label class="small text-muted font-weight-bold text-uppercase mb-1">Unidad / Bus</label>
                                <div class="h5 font-weight-bold text-dark mb-0"><i class="fas fa-bus text-muted me-2"></i>${v.placa || 'N/A'}</div>
                                <div class="vr position-absolute d-none d-md-block" style="right:0; top:10%; height:80%; border-right:1px solid #ddd;"></div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0 position-relative">
                                <label class="small text-muted font-weight-bold text-uppercase mb-1">Fecha Salida</label>
                                <div class="h5 font-weight-bold text-dark mb-0">${v.fecha_salida}</div>
                                <div class="vr position-absolute d-none d-md-block" style="right:0; top:10%; height:80%; border-right:1px solid #ddd;"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted font-weight-bold text-uppercase mb-1">Hora Salida</label>
                                <div class="h5 font-weight-bold text-dark mb-0">${v.hora_salida}</div>
                            </div>
                        `);

                        // Tabla Mejorada
                        let html = '';
                        if (res.pasajeros.length > 0) {
                            res.pasajeros.forEach((p, idx) => {
                                let nombre = (p.apellidos && p.nombres) ? `${p.apellidos} ${p.nombres}` : (p.nombre_pasajero || 'ANÓNIMO');
                                let doc = p.documento || '<span class="text-muted font-italic">Sin Doc</span>';

                                html += `<tr>
                                    <td class="text-center align-middle">
                                        <div class="bg-light rounded-circle font-weight-bold mx-auto text-secondary shadow-sm" style="width:35px; height:35px; line-height:35px; border: 1px solid #dee2e6;">${p.numero_asiento}</div>
                                    </td>
                                    <td class="align-middle font-weight-bold text-dark">${doc}</td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold text-uppercase text-dark" style="font-size: 1rem;">${nombre}</div>
                                        <small class="text-muted font-weight-bold"><i class="fas fa-flag me-1 text-info"></i> Nacionalidad: Boliviana</small>
                                    </td>
                                    <td class="align-middle text-center">
                                        <div class="d-inline-block px-3 py-1 rounded-pill bg-white border shadow-sm">
                                            <i class="fas fa-map-marker-alt text-danger me-2"></i> 
                                            <span class="font-weight-bold text-dark text-uppercase">${p.destino || 'Destino Final'}</span>
                                        </div>
                                    </td>
                                    <td class="align-middle text-end">
                                        <span class="badge bg-success px-3 py-2 shadow" style="font-weight: 700; font-size: 0.85rem; background-color: #28a745 !important; color: #fff !important; opacity: 1 !important;">
                                            <i class="fas fa-check me-1"></i> ABORDÓ
                                        </span>
                                    </td>
                                </tr>`;
                            });
                        } else {
                            html = `<tr><td colspan="6" class="text-center py-5">
                                <div class="text-muted opacity-50">
                                    <i class="fas fa-chair fa-3x mb-3"></i>
                                    <h5>El bus está vacío</h5>
                                    <p>No se han registrado pasajeros todavía.</p>
                                </div>
                            </td></tr>`;
                        }
                        $('#tablaPasajeros tbody').html(html);

                        Swal.close();
                        $('#modalManifiesto').modal('show');
                    } else {
                        Swal.fire('Error', 'No se pudo cargar la información del viaje.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error crítico de servidor.', 'error');
                }
            });
        });
    });

    // --- FUNCIONES DE EXPORTACIÓN ROBUSTAS ---
    window.exportManifiesto = function(type) {
        let viajeId = $('#modalManifiesto').data('viaje-id');
        if (!viajeId) {
            Swal.fire('Atención', 'No hay datos cargados para exportar.', 'warning');
            return;
        }

        const table = document.getElementById('tablaPasajeros');
        const date = new Date().toISOString().slice(0, 10);

        try {
            Swal.fire({
                title: 'Generando archivo...',
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            setTimeout(() => {
                if (type === 'excel') {
                    const wb = XLSX.utils.table_to_book(table, {
                        sheet: "Manifiesto"
                    });
                    XLSX.writeFile(wb, `Manifiesto_Viaje_${viajeId}_${date}.xlsx`);
                } else if (type === 'pdf') {
                    if (typeof window.jspdf === 'undefined') {
                        throw new Error("Librería PDF no cargada");
                    }
                    const {
                        jsPDF
                    } = window.jspdf;
                    const doc = new jsPDF();

                    // Header PDF
                    doc.setFillColor(41, 128, 185);
                    doc.rect(0, 0, 210, 20, 'F');
                    doc.setTextColor(255, 255, 255);
                    doc.setFontSize(16);
                    doc.text("MANIFIESTO DE PASAJEROS", 105, 13, {
                        align: 'center'
                    });

                    doc.setTextColor(0, 0, 0);
                    doc.setFontSize(10);
                    doc.text("Fecha Impresión: " + new Date().toLocaleString(), 14, 30);

                    doc.autoTable({
                        html: '#tablaPasajeros',
                        startY: 35,
                        theme: 'grid',
                        headStyles: {
                            fillColor: [41, 128, 185],
                            textColor: 255,
                            fontStyle: 'bold'
                        },
                        styles: {
                            fontSize: 9,
                            cellPadding: 3
                        },
                        showHead: 'everyPage'
                    });
                    doc.save(`Manifiesto_${viajeId}.pdf`);
                } else if (type === 'word') {
                    const html = `
                    <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                    <head><meta charset='utf-8'><title>Manifiesto</title>
                    <style>
                        body{font-family: Arial, sans-serif;}
                        table{border-collapse:collapse;width:100%; margin-top: 20px;}
                        th{background-color: #2980b9; color: white; padding: 10px; border: 1px solid #000;}
                        td{border: 1px solid #ddd; padding: 8px;}
                        h2{color: #2c3e50; text-align: center;}
                    </style>
                    </head><body>
                    <h2>MANIFIESTO DE PASAJEROS - SISTEMA DE TRANSPORTE</h2>
                    ${table.outerHTML}
                    <br><br>
                    <p style="text-align:center; color:#777;">Documento generado automáticamente.</p>
                    </body></html>`;

                    const blob = new Blob(['\ufeff', html], {
                        type: 'application/msword'
                    });
                    const link = document.createElement("a");
                    link.href = URL.createObjectURL(blob);
                    link.download = `Manifiesto_Oficial.doc`;
                    link.click();
                }
                Swal.close();
            }, 500); // Pequeño delay para UX

        } catch (e) {
            Swal.fire('Error', 'Fallo al exportar: ' + e.message, 'error');
        }
    }
</script>

@endsection
