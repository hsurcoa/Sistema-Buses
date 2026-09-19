@extends('layouts.app')

@section('content')

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="bi bi-box-seam mr-2"></i> Reporte de Encomiendas</h1>
                </div>
                <?php if (!empty($data['es_global'])): ?>
                    <div class="col-sm-6">
                        <form method="get" class="d-flex justify-content-sm-end gap-2">
                            <select class="form-select form-select-sm w-auto" name="sucursal" onchange="this.form.submit()">
                                <option value="">Todas las sucursales</option>
                                <?php foreach ($data['sucursales'] as $s): ?>
                                    <option value="<?php echo (int) $s->id; ?>" <?php echo (int) $data['sucursal_id'] === (int) $s->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($s->nombre_sede); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if (!empty($data['sin_sucursal'])): ?>
                <div class="alert alert-warning">Su usuario no tiene sucursal asignada: no se pueden mostrar datos. Pida al administrador que se la asigne.</div>
            <?php endif; ?>

            <!-- KPIs -->
            <div class="row g-3 mb-3">
                <div class="col-md-2">
                    <div class="card border-start border-4 border-primary h-100 shadow-sm">
                        <div class="card-body py-2">
                            <div class="small text-muted fw-bold text-uppercase">Total</div>
                            <h3 class="fw-bold text-dark mb-0" id="kpi_total">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-start border-4 border-success h-100 shadow-sm">
                        <div class="card-body py-2">
                            <div class="small text-muted fw-bold text-uppercase">Ingresos</div>
                            <h3 class="fw-bold text-dark mb-0" id="kpi_ingresos">Bs. 0.00</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-start border-4 border-info h-100 shadow-sm">
                        <div class="card-body py-2">
                            <div class="small text-muted fw-bold text-uppercase">Peso total</div>
                            <h3 class="fw-bold text-dark mb-0" id="kpi_peso">0 kg</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-start border-4 border-success h-100 shadow-sm">
                        <div class="card-body py-2">
                            <div class="small text-muted fw-bold text-uppercase">Entregadas</div>
                            <h3 class="fw-bold text-dark mb-0" id="kpi_entregadas">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-start border-4 border-warning h-100 shadow-sm">
                        <div class="card-body py-2">
                            <div class="small text-muted fw-bold text-uppercase">Pendientes</div>
                            <h3 class="fw-bold text-dark mb-0" id="kpi_pendientes">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-start border-4 border-danger h-100 shadow-sm">
                        <div class="card-body py-2">
                            <div class="small text-muted fw-bold text-uppercase">Pago pendiente</div>
                            <h3 class="fw-bold text-dark mb-0" id="kpi_pago_pendiente">0</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header border-0 bg-white d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h3 class="card-title text-secondary mb-0">
                        <i class="bi bi-list-ul mr-1"></i> Encomiendas del periodo
                    </h3>

                    <div class="card-tools d-flex align-items-center flex-wrap gap-2">
                        <input type="date" id="filtroInicio" class="form-control form-control-sm" style="width:auto" value="<?php echo $data['fecha_inicio']; ?>">
                        <span class="text-muted small">a</span>
                        <input type="date" id="filtroFin" class="form-control form-control-sm" style="width:auto" value="<?php echo $data['fecha_fin']; ?>">
                        <select id="filtroEstado" class="form-select form-select-sm" style="width:auto">
                            <option value="">Todos los estados</option>
                            <option value="REGISTRADO">Registrado</option>
                            <option value="EN_ALMACEN_ORIGEN">En almacén origen</option>
                            <option value="EN_RUTA">En ruta</option>
                            <option value="EN_DESTINO">En destino</option>
                            <option value="ENTREGADO">Entregado</option>
                        </select>
                        <button class="btn btn-primary btn-sm" id="btnFiltrar"><i class="bi bi-funnel"></i> Filtrar</button>
                    </div>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped table-valign-middle" id="tablaEncomiendasReporte">
                        <thead class="bg-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Guía</th>
                                <th>Ruta</th>
                                <th>Sucursal origen → destino</th>
                                <th>Remitente</th>
                                <th>Destinatario</th>
                                <th class="text-end">Peso</th>
                                <th class="text-end">Total</th>
                                <th>Pago</th>
                                <th>Estado</th>
                                <th>Registrado por</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyEncomiendasReporte">
                            <?php if (empty($data['encomiendas']) || count($data['encomiendas']) === 0): ?>
                                <tr><td colspan="11" class="text-center text-muted">No hay encomiendas en los últimos 30 días</td></tr>
                            <?php else: ?>
                                <?php foreach ($data['encomiendas'] as $e): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($e->fecha_registro)); ?></td>
                                        <td><span class="badge bg-dark"><?php echo htmlspecialchars($e->codigo_guia); ?></span></td>
                                        <td><?php echo htmlspecialchars($e->ruta); ?></td>
                                        <td class="small"><?php echo htmlspecialchars($e->sucursal_origen ?? '—'); ?> <i class="bi bi-arrow-right mx-1 text-muted"></i> <?php echo htmlspecialchars($e->sucursal_destino ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($e->remitente_nombre); ?></td>
                                        <td><?php echo htmlspecialchars($e->destinatario_nombre); ?></td>
                                        <td class="text-end"><?php echo number_format((float) $e->peso, 2); ?> kg</td>
                                        <td class="text-end fw-bold">Bs. <?php echo number_format((float) $e->total_pagar, 2); ?></td>
                                        <td><span class="badge <?php echo $e->estado_pago === 'PAGADO' ? 'bg-success' : 'bg-danger'; ?>"><?php echo htmlspecialchars($e->estado_pago ?? 'PAGADO'); ?></span></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($e->estado); ?></span></td>
                                        <td class="small"><?php echo htmlspecialchars($e->registrado_por ?? '—'); ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('tbodyEncomiendasReporte');
    const sucursalQS = new URLSearchParams(window.location.search).get('sucursal');

    function badgeEstado(estado) {
        const mapa = { REGISTRADO: 'bg-info', EN_ALMACEN_ORIGEN: 'bg-secondary', EN_RUTA: 'bg-primary', EN_DESTINO: 'bg-warning text-dark', ENTREGADO: 'bg-success' };
        return `<span class="badge ${mapa[estado] || 'bg-secondary'}">${estado}</span>`;
    }

    function filaHtml(e) {
        return `<tr>
            <td>${new Date(e.fecha_registro.replace(' ', 'T')).toLocaleString('es-BO', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'})}</td>
            <td><span class="badge bg-dark">${e.codigo_guia}</span></td>
            <td>${e.ruta}</td>
            <td class="small">${e.sucursal_origen || '—'} <i class="bi bi-arrow-right mx-1 text-muted"></i> ${e.sucursal_destino || '—'}</td>
            <td>${e.remitente_nombre}</td>
            <td>${e.destinatario_nombre}</td>
            <td class="text-end">${parseFloat(e.peso || 0).toFixed(2)} kg</td>
            <td class="text-end fw-bold">Bs. ${parseFloat(e.total_pagar).toFixed(2)}</td>
            <td><span class="badge ${e.estado_pago === 'PAGADO' ? 'bg-success' : 'bg-danger'}">${e.estado_pago || 'PAGADO'}</span></td>
            <td>${badgeEstado(e.estado)}</td>
            <td class="small">${e.registrado_por || '—'}</td>
        </tr>`;
    }

    function actualizarKpis(stats) {
        if (!stats) return;
        document.getElementById('kpi_total').textContent = stats.total || 0;
        document.getElementById('kpi_ingresos').textContent = 'Bs. ' + parseFloat(stats.total_ingresos || 0).toFixed(2);
        document.getElementById('kpi_peso').textContent = parseFloat(stats.total_peso || 0).toFixed(2) + ' kg';
        document.getElementById('kpi_entregadas').textContent = stats.entregadas || 0;
        document.getElementById('kpi_pendientes').textContent = stats.pendientes || 0;
        document.getElementById('kpi_pago_pendiente').textContent = stats.pago_pendiente || 0;
    }

    function cargar() {
        const inicio = document.getElementById('filtroInicio').value;
        const fin = document.getElementById('filtroFin').value;
        const estado = document.getElementById('filtroEstado').value;

        tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4"><i class="bi bi-hourglass-split"></i> Cargando...</td></tr>';

        const formData = new FormData();
        formData.append('fecha_inicio', inicio);
        formData.append('fecha_fin', fin);
        formData.append('estado', estado);
        formData.append('csrf_token', '<?php echo csrf_token(); ?>');

        const url = '<?php echo URLROOT; ?>/reportes/encomiendas_ajax' + (sucursalQS ? '?sucursal=' + encodeURIComponent(sucursalQS) : '');

        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') { tbody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Error al cargar</td></tr>'; return; }
                actualizarKpis(res.stats);
                tbody.innerHTML = res.data.length
                    ? res.data.map(filaHtml).join('')
                    : '<tr><td colspan="11" class="text-center text-muted">No hay encomiendas en este periodo</td></tr>';
            })
            .catch(() => { tbody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Error de conexión</td></tr>'; });
    }

    document.getElementById('btnFiltrar').addEventListener('click', cargar);
    cargar();
});
</script>

@endsection
