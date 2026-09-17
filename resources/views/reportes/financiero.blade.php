@extends('layouts.app')

@section('content')

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Header -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-chart-line mr-2"></i> Reporte Financiero</h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid">

            <!-- 1. TARJETAS KPI -->
            <div class="row">
                <!-- Ventas Hoy -->
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-white border shadow-sm p-3">
                        <div class="inner">
                            <h3 class="text-primary font-weight-bold">Bs. <?php echo number_format($data['kpi_hoy']->total ?? 0, 2); ?></h3>
                            <p class="text-muted mb-0">Ventas Hoy</p>
                            <span class="text-xs text-success font-weight-bold">
                                <i class="fas fa-ticket-alt mr-1"></i> <?php echo $data['kpi_hoy']->cantidad ?? 0; ?> Pasajes
                            </span>
                        </div>
                        <div class="icon">
                            <i class="fas fa-cash-register text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>

                <!-- Ventas Mensuales -->
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-white border shadow-sm p-3">
                        <div class="inner">
                            <h3 class="text-success font-weight-bold">Bs. <?php echo number_format($data['kpi_mes']['actual'] ?? 0, 2); ?></h3>
                            <p class="text-muted mb-0">Ventas Este Mes</p>
                            <?php
                            $actual = $data['kpi_mes']['actual'];
                            $anterior = $data['kpi_mes']['anterior'];
                            $crecimiento = $anterior > 0 ? (($actual - $anterior) / $anterior) * 100 : 100;
                            $color = $crecimiento >= 0 ? 'text-success' : 'text-danger';
                            $icon = $crecimiento >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                            ?>
                            <span class="text-xs <?php echo $color; ?> font-weight-bold">
                                <i class="fas <?php echo $icon; ?> mr-1"></i> <?php echo number_format($crecimiento, 1); ?>% vs mes anterior
                            </span>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-bar text-success opacity-25"></i>
                        </div>
                    </div>
                </div>

                <!-- Mejor Ruta -->
                <div class="col-lg-4 col-12">
                    <div class="small-box bg-white border shadow-sm p-3">
                        <div class="inner">
                            <?php if ($data['mejor_ruta']): ?>
                                <h4 class="font-weight-bold text-dark mb-0 text-truncate" style="font-size: 1.4rem;">
                                    <?php echo $data['mejor_ruta']->origen . ' - ' . $data['mejor_ruta']->destino; ?>
                                </h4>
                                <p class="text-muted mb-0">Ruta Estrella ⭐</p>
                                <span class="text-xs text-info font-weight-bold">
                                    Bs. <?php echo number_format($data['mejor_ruta']->total_ventas, 2); ?>
                                </span>
                            <?php else: ?>
                                <h4 class="font-weight-bold text-muted">Sin datos</h4>
                                <p>No hay ventas este mes</p>
                            <?php endif; ?>
                        </div>
                        <div class="icon">
                            <i class="fas fa-trophy text-warning opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. GRÁFICO TENDENCIA -->
            <div class="card shadow-sm mb-4">
                <div class="card-header border-0 bg-white">
                    <h3 class="card-title text-secondary">
                        <i class="fas fa-chart-area mr-1"></i> Tendencia de Ventas (Últimos 7 días)
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>

            <!-- 3. TABLA DE DETALLE GLOBAL -->
            <div class="card shadow-sm">
                <div class="card-header border-0 bg-white d-flex align-items-center justify-content-between">
                    <h3 class="card-title text-secondary">
                        <i class="fas fa-list mr-1"></i> Detalle de Ingresos por Viaje
                    </h3>

                    <!-- Filtros -->
                    <div class="card-tools d-flex">
                        <input type="date" id="filtroInicio" class="form-control form-control-sm mr-2" placeholder="Desde">
                        <input type="date" id="filtroFin" class="form-control form-control-sm mr-2" placeholder="Hasta">
                        <button class="btn btn-primary btn-sm mr-2" id="btnFiltrar">
                            <i class="fas fa-filter"></i>
                        </button>

                        <!-- Botones de Exportación Directos -->
                        <div class="btn-group ml-2">
                            <button type="button" class="btn btn-success btn-sm" onclick="exportExcel()" title="Exportar a Excel">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="exportPDF()" title="Exportar a PDF">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="exportWord()" title="Exportar a Word">
                                <i class="fas fa-file-word"></i> Word
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped table-valign-middle" id="tablaDetalle">
                        <thead class="bg-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Bus / Unidad</th>
                                <th>Ruta</th>
                                <th class="text-center">Pasajes</th>
                                <th class="text-right">Total Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data['detalle'])): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No hay ventas registradas</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data['detalle'] as $d): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($d->fecha)); ?></td>
                                        <td>
                                            <?php echo $d->bus; ?>
                                            <?php if ($d->bus_numero): ?>
                                                <span class="badge badge-light border ml-1">#<?php echo $d->bus_numero; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $d->ruta; ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-info text-white rounded-pill px-3"><?php echo $d->pasajes_vendidos; ?></span>
                                        </td>
                                        <td class="text-right font-weight-bold text-success">
                                            Bs. <?php echo number_format($d->total_ingresos, 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Libraries for Export -->
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // --- GRÁFICO (ChartJS Checks) ---
        const canvas = document.getElementById('salesChart');
        if (canvas) {
            try {
                const ctx = canvas.getContext('2d');

                // DATA FROM PHP
                const labels = <?php echo $data['chart_labels']; ?>;
                const dataValues = <?php echo $data['chart_data']; ?>;

                if (!labels || labels.length === 0) {
                    // Fallback Text
                    ctx.font = "14px Arial";
                    ctx.fillStyle = "gray";
                    ctx.textAlign = "center";
                    ctx.fillText("No hay datos suficientes para el gráfico", canvas.width / 2, canvas.height / 2);
                } else {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Ventas (Bs.)',
                                backgroundColor: 'rgba(60, 141, 188, 0.1)',
                                borderColor: 'rgba(60, 141, 188, 1)',
                                pointRadius: 4,
                                pointBackgroundColor: '#3b8bba',
                                pointBorderColor: 'rgba(60, 141, 188, 1)',
                                data: dataValues,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        borderDash: [2, 4]
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error("Error ChartJS:", error);
            }
        }


        // --- FILTRADO DE TABLA (AJAX) ---
        const btnFiltrar = document.getElementById('btnFiltrar');
        if (btnFiltrar) {
            btnFiltrar.addEventListener('click', function() {
                const inicio = document.getElementById('filtroInicio').value;
                const fin = document.getElementById('filtroFin').value;

                if (!inicio || !fin) {
                    Swal.fire('Atención', 'Seleccione ambas fechas', 'warning');
                    return;
                }

                // Loading
                const tbody = document.querySelector('#tablaDetalle tbody');
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

                // AJAX
                const formData = new FormData();
                formData.append('fecha_inicio', inicio);
                formData.append('fecha_fin', fin);

                fetch('<?php echo URLROOT; ?>/reportes/financiero_ajax', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(res => {
                        if (res.status === 'success') {
                            let html = '';
                            if (res.data.length > 0) {
                                res.data.forEach(d => {
                                    html += `
                                    <tr>
                                        <td>${d.fecha}</td>
                                        <td>${d.bus} <span class="badge badge-light border ml-1">#${d.bus_numero || ''}</span></td>
                                        <td>${d.ruta}</td>
                                        <td class="text-center"><span class="badge badge-info text-white rounded-pill px-3">${d.pasajes_vendidos}</span></td>
                                        <td class="text-right font-weight-bold text-success">Bs. ${parseFloat(d.total_ingresos).toFixed(2)}</td>
                                    </tr>
                                `;
                                });
                            } else {
                                html = '<tr><td colspan="5" class="text-center text-muted">No se encontraron ventas en este periodo</td></tr>';
                            }
                            tbody.innerHTML = html;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'Error al filtrar datos', 'error');
                    });
            });
        }
    });

    // --- FUNCIONES DE EXPORTACIÓN (Manual Global Scope) ---
    // Make sure these function names don't conflict

    window.exportExcel = function() {
        try {
            if (typeof XLSX === 'undefined') {
                throw new Error('Librería Excel no cargada. Revise su conexión.');
            }

            const table = document.getElementById('tablaDetalle');
            const wb = XLSX.utils.table_to_book(table, {
                sheet: "Reporte Financiero"
            });
            XLSX.writeFile(wb, 'Reporte_Financiero_' + new Date().toISOString().slice(0, 10) + '.xlsx');
        } catch (e) {
            alert('Error al exportar Excel: ' + e.message);
        }
    }

    window.exportPDF = function() {
        try {
            if (typeof window.jspdf === 'undefined') {
                throw new Error('Librería PDF no cargada. Revise su conexión.');
            }

            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();

            // Título
            doc.setFontSize(18);
            doc.text("Reporte Financiero", 14, 22);
            doc.setFontSize(11);
            doc.text("Fecha: " + new Date().toLocaleDateString(), 14, 30);

            // Tabla
            doc.autoTable({
                html: '#tablaDetalle',
                startY: 35,
                theme: 'grid',
                headStyles: {
                    fillColor: [41, 128, 185]
                },
                styles: {
                    fontSize: 8
                }
            });

            doc.save('Reporte_Financiero.pdf');
        } catch (e) {
            alert('Error al exportar PDF: ' + e.message);
        }
    }

    window.exportWord = function() {
        try {
            const table = document.getElementById('tablaDetalle');
            const html = `
                <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
                <head><meta charset='utf-8'><title>Reporte Word</title>
                <style>
                    table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
                    th { background-color: #f2f2f2; border: 1px solid #000; padding: 8px; text-align: left; }
                    td { border: 1px solid #000; padding: 8px; }
                </style>
                </head><body>
                <h2>Reporte Financiero</h2>
                ${table.outerHTML}
                </body></html>`;

            const blob = new Blob(['\ufeff', html], {
                type: 'application/msword'
            });
            const url = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(html);

            const downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);

            if (navigator.msSaveOrOpenBlob) {
                navigator.msSaveOrOpenBlob(blob, 'Reporte_Financiero.doc');
            } else {
                downloadLink.href = url;
                downloadLink.download = 'Reporte_Financiero.doc';
                downloadLink.click();
            }
            document.body.removeChild(downloadLink);
        } catch (e) {
            alert('Error al exportar Word: ' + e.message);
        }
    }
</script>

@endsection
