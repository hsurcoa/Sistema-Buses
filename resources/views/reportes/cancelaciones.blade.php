@extends('layouts.app')

@section('content')

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="bi bi-arrow-counterclockwise mr-2"></i> Bitácora de Cancelaciones</h1>
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

            <div class="card shadow-sm">
                <div class="card-header border-0 bg-white d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h3 class="card-title text-secondary mb-0">
                        <i class="bi bi-list-ul mr-1"></i> Boletos cancelados (pagados)
                    </h3>

                    <div class="card-tools d-flex align-items-center flex-wrap gap-2">
                        <input type="date" id="filtroInicio" class="form-control form-control-sm" style="width:auto" value="<?php echo $data['fecha_inicio']; ?>">
                        <span class="text-muted small">a</span>
                        <input type="date" id="filtroFin" class="form-control form-control-sm" style="width:auto" value="<?php echo $data['fecha_fin']; ?>">
                        <div class="form-check form-switch ms-2">
                            <input class="form-check-input" type="checkbox" id="filtroPendientes">
                            <label class="form-check-label small" for="filtroPendientes">Solo pendientes de devolución</label>
                        </div>
                        <button class="btn btn-primary btn-sm" id="btnFiltrar"><i class="bi bi-funnel"></i> Filtrar</button>
                    </div>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped table-valign-middle" id="tablaCancelaciones">
                        <thead class="bg-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Boleto</th>
                                <th>Pasajero</th>
                                <th>Ruta</th>
                                <th class="text-end">Monto</th>
                                <th>Método</th>
                                <th>Cancelado por</th>
                                <th>Motivo</th>
                                <th>Devolución</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyCancelaciones">
                            <?php if (empty($data['cancelaciones']) || count($data['cancelaciones']) === 0): ?>
                                <tr><td colspan="10" class="text-center text-muted">No hay cancelaciones en los últimos 30 días</td></tr>
                            <?php else: ?>
                                <?php foreach ($data['cancelaciones'] as $c): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($c->fecha_creacion)); ?></td>
                                        <td>#<?php echo htmlspecialchars($c->codigo_boleto ?? $c->boleto_id); ?> <span class="text-muted small">(asiento <?php echo htmlspecialchars($c->numero_asiento); ?>)</span></td>
                                        <td><?php echo htmlspecialchars($c->pasajero); ?></td>
                                        <td><?php echo htmlspecialchars($c->ruta); ?></td>
                                        <td class="text-end fw-bold">Bs. <?php echo number_format($c->monto, 2); ?></td>
                                        <td><?php echo htmlspecialchars($c->metodo_pago ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($c->cancelado_por); ?></td>
                                        <td class="small"><?php echo $c->motivo ? htmlspecialchars($c->motivo) : '<span class="text-muted">Sin motivo</span>'; ?></td>
                                        <td>
                                            <?php if ($c->devuelto): ?>
                                                <span class="badge bg-success">Sí · <?php echo htmlspecialchars($c->metodo_devolucion); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (! $c->devuelto): ?>
                                                <button class="btn btn-sm btn-outline-success" onclick="procesarDevolucion(<?php echo (int) $c->id; ?>)"><i class="bi bi-cash-coin"></i> Procesar devolución</button>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('tbodyCancelaciones');
    const sucursalQS = new URLSearchParams(window.location.search).get('sucursal');

    function badgeDevolucion(c) {
        if (c.devuelto) {
            return `<span class="badge bg-success">Sí · ${c.metodo_devolucion || ''}</span>`;
        }
        return `<span class="badge bg-warning text-dark">Pendiente</span>`;
    }

    function filaHtml(c) {
        const accion = c.devuelto
            ? '<span class="text-muted small">—</span>'
            : `<button class="btn btn-sm btn-outline-success" onclick="procesarDevolucion(${c.id})"><i class="bi bi-cash-coin"></i> Procesar devolución</button>`;

        return `<tr>
            <td>${new Date(c.fecha_creacion).toLocaleString('es-BO', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'})}</td>
            <td>#${c.codigo_boleto || c.boleto_id} <span class="text-muted small">(asiento ${c.numero_asiento})</span></td>
            <td>${c.pasajero}</td>
            <td>${c.ruta}</td>
            <td class="text-end fw-bold">Bs. ${parseFloat(c.monto).toFixed(2)}</td>
            <td>${c.metodo_pago || '-'}</td>
            <td>${c.cancelado_por}</td>
            <td class="small">${c.motivo || '<span class="text-muted">Sin motivo</span>'}</td>
            <td>${badgeDevolucion(c)}</td>
            <td class="text-center">${accion}</td>
        </tr>`;
    }

    function cargar() {
        const inicio = document.getElementById('filtroInicio').value;
        const fin = document.getElementById('filtroFin').value;
        const soloPendientes = document.getElementById('filtroPendientes').checked;

        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4"><i class="bi bi-hourglass-split"></i> Cargando...</td></tr>';

        const formData = new FormData();
        formData.append('fecha_inicio', inicio);
        formData.append('fecha_fin', fin);
        formData.append('solo_pendientes', soloPendientes ? '1' : '0');
        formData.append('csrf_token', '<?php echo csrf_token(); ?>');

        const url = '<?php echo URLROOT; ?>/reportes/cancelaciones_ajax' + (sucursalQS ? '?sucursal=' + encodeURIComponent(sucursalQS) : '');

        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') { tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Error al cargar</td></tr>'; return; }
                tbody.innerHTML = res.data.length
                    ? res.data.map(filaHtml).join('')
                    : '<tr><td colspan="10" class="text-center text-muted">No hay cancelaciones en este periodo</td></tr>';
            })
            .catch(() => { tbody.innerHTML = '<tr><td colspan="10" class="text-center text-danger">Error de conexión</td></tr>'; });
    }

    document.getElementById('btnFiltrar').addEventListener('click', cargar);

    window.procesarDevolucion = function(cancelacionId) {
        Swal.fire({
            title: 'Procesar devolución',
            html: `
                <label class="form-label text-start d-block mb-1">Método de devolución</label>
                <select id="swalMetodoDevol" class="form-select">
                    <option value="EFECTIVO">Efectivo (sale de la caja abierta)</option>
                    <option value="QR">Transferencia / QR</option>
                    <option value="OTRO">Otro</option>
                </select>
            `,
            showCancelButton: true,
            confirmButtonText: 'Confirmar devolución',
            cancelButtonText: 'Cancelar',
            preConfirm: () => document.getElementById('swalMetodoDevol').value
        }).then(r => {
            if (!r.isConfirmed) return;

            const formData = new FormData();
            formData.append('cancelacion_id', cancelacionId);
            formData.append('metodo_devolucion', r.value);
            formData.append('csrf_token', '<?php echo csrf_token(); ?>');

            fetch('<?php echo URLROOT; ?>/reportes/procesar_devolucion', { method: 'POST', body: formData })
                .then(resp => resp.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire('Listo', res.message, 'success');
                        cargar();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'No se pudo conectar con el servidor', 'error'));
        });
    };
});
</script>

@endsection
