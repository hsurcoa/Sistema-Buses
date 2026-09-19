@extends('layouts.app')

@section('content')

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0">Gestión de Encomiendas</h3>
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
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <?php if (!empty($data['sin_sucursal'])): ?>
                <div class="alert alert-warning">Su usuario no tiene sucursal asignada: no se pueden mostrar encomiendas. Pida al administrador que se la asigne.</div>
            <?php endif; ?>
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center flex-wrap gap-2">
                    <h3 class="card-title mb-0">Envíos Recientes<?php echo !empty($data['sucursal_id']) ? ' · ' . htmlspecialchars($data['sucursales']->firstWhere('id', $data['sucursal_id'])->nombre_sede ?? '') : ''; ?></h3>
                    <div class="card-tools ms-auto d-flex gap-2">
                        <a href="<?php echo URLROOT; ?>/encomiendas/crear" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Nuevo Envío
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="d-flex flex-wrap gap-2 p-3 border-bottom bg-light">
                        <input type="text" id="buscarEncomienda" class="form-control form-control-sm" style="max-width: 260px" placeholder="Buscar por guía, remitente o destinatario...">
                        <select id="filtroEstadoEncomienda" class="form-select form-select-sm" style="max-width: 200px">
                            <option value="">Todos los estados</option>
                            <option value="REGISTRADO">Registrado</option>
                            <option value="EN_ALMACEN_ORIGEN">En almacén origen</option>
                            <option value="EN_RUTA">En ruta</option>
                            <option value="EN_DESTINO">En destino</option>
                            <option value="ENTREGADO">Entregado</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped" id="tablaEncomiendas">
                            <thead>
                                <tr>
                                    <th>Guía</th>
                                    <th>Fecha</th>
                                    <th>Ruta</th>
                                    <th>Sucursal origen → destino</th>
                                    <th>Remitente</th>
                                    <th>Destinatario</th>
                                    <th>Contenido</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data['encomiendas'])): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No hay encomiendas registradas</td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $etiquetasEstado = [
                                        'REGISTRADO' => ['Registrado', 'bg-info'],
                                        'EN_ALMACEN_ORIGEN' => ['En almacén origen', 'bg-secondary'],
                                        'EN_RUTA' => ['En ruta', 'bg-primary'],
                                        'EN_DESTINO' => ['En destino', 'bg-warning text-dark'],
                                        'ENTREGADO' => ['Entregado', 'bg-success'],
                                    ];
                                    $ordenEstados = array_keys($etiquetasEstado);
                                    ?>
                                    <?php foreach ($data['encomiendas'] as $enc): ?>
                                        <?php
                                        [$etiqueta, $badge] = $etiquetasEstado[$enc->estado] ?? [$enc->estado, 'bg-secondary'];
                                        $idxActual = array_search($enc->estado, $ordenEstados, true);
                                        $siguientes = $idxActual !== false ? array_slice($ordenEstados, $idxActual + 1) : [];
                                        ?>
                                        <tr data-guia="<?php echo strtolower(htmlspecialchars($enc->codigo_guia)); ?>" data-remitente="<?php echo strtolower(htmlspecialchars($enc->remitente_nombre)); ?>" data-destinatario="<?php echo strtolower(htmlspecialchars($enc->destinatario_nombre)); ?>" data-estado="<?php echo htmlspecialchars($enc->estado); ?>">
                                            <td><span class="badge bg-dark"><?php echo $enc->codigo_guia; ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($enc->fecha_registro)); ?></td>
                                            <td><?php echo $enc->origen . ' - ' . $enc->destino; ?></td>
                                            <td class="small">
                                                <?php echo htmlspecialchars($enc->sucursal_origen_nombre ?? '—'); ?>
                                                <i class="bi bi-arrow-right mx-1 text-muted"></i>
                                                <?php echo htmlspecialchars($enc->sucursal_destino_nombre ?? '—'); ?>
                                            </td>
                                            <td><?php echo $enc->remitente_nombre; ?></td>
                                            <td><?php echo $enc->destinatario_nombre; ?></td>
                                            <td><?php echo $enc->contenido_breve; ?></td>
                                            <td>
                                                <span class="badge <?php echo $badge; ?>"><?php echo $etiqueta; ?></span>
                                                <?php if (($enc->estado_pago ?? 'PAGADO') !== 'PAGADO'): ?>
                                                    <span class="badge bg-danger-subtle text-danger">Pago pendiente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-nowrap">
                                                <a href="<?php echo URLROOT; ?>/encomiendas/recibo/<?php echo $enc->id; ?>" class="btn btn-sm btn-secondary" title="Imprimir Recibo">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                                <?php if ($siguientes): ?>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Cambiar estado
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php foreach ($siguientes as $sig): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="#" onclick="cambiarEstadoEncomienda(<?php echo (int) $enc->id; ?>, '<?php echo $sig; ?>', <?php echo !empty($enc->clave_retiro) ? 'true' : 'false'; ?>, '<?php echo htmlspecialchars($enc->codigo_guia); ?>'); return false;">
                                                                        Marcar <?php echo strtolower($etiquetasEstado[$sig][0]); ?>
                                                                    </a>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
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
        </div>
    </div>
</main>

<script>
    const CSRF_TOKEN_ENC = '<?php echo csrf_token(); ?>';
    const ETIQUETAS_ESTADO_ENC = {
        REGISTRADO: 'registrado', EN_ALMACEN_ORIGEN: 'en almacén origen',
        EN_RUTA: 'en ruta', EN_DESTINO: 'en destino', ENTREGADO: 'entregado',
    };

    function cambiarEstadoEncomienda(id, estado, tieneClave, guia) {
        const pedirClave = () => {
            if (!tieneClave) return Promise.resolve({ confirmado: true, clave: null });
            return Swal.fire({
                title: 'Confirmar entrega',
                html: `Ingrese la clave de retiro de 4 dígitos que dio el remitente para la guía <b>${guia}</b>.`,
                input: 'text',
                inputAttributes: { maxlength: 4, inputmode: 'numeric', autocomplete: 'off' },
                showCancelButton: true,
                confirmButtonText: 'Confirmar entrega',
                cancelButtonText: 'Cancelar',
            }).then(r => ({ confirmado: r.isConfirmed, clave: r.value }));
        };

        const confirmarSimple = () => Swal.fire({
            icon: 'question',
            title: `¿Marcar la guía ${guia} como "${ETIQUETAS_ESTADO_ENC[estado] || estado}"?`,
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar',
        }).then(r => ({ confirmado: r.isConfirmed, clave: null }));

        (estado === 'ENTREGADO' ? pedirClave() : confirmarSimple()).then(({ confirmado, clave }) => {
            if (!confirmado) return;

            fetch(`<?php echo URLROOT; ?>/encomiendas/cambiar_estado/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ csrf_token: CSRF_TOKEN_ENC, estado: estado, clave_retiro: clave }),
            })
                .then(r => r.json())
                .then(res => {
                    if (res.status) {
                        Swal.fire({ icon: 'success', title: 'Estado actualizado', timer: 1400, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire('No se pudo actualizar', res.message || 'Error desconocido', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'No se pudo conectar con el servidor', 'error'));
        });
    }

    (function () {
        const buscar = document.getElementById('buscarEncomienda');
        const filtroEstado = document.getElementById('filtroEstadoEncomienda');
        const filas = document.querySelectorAll('#tablaEncomiendas tbody tr[data-guia]');

        function aplicarFiltros() {
            const texto = buscar.value.trim().toLowerCase();
            const estado = filtroEstado.value;
            filas.forEach(fila => {
                const coincideTexto = !texto || fila.dataset.guia.includes(texto) || fila.dataset.remitente.includes(texto) || fila.dataset.destinatario.includes(texto);
                const coincideEstado = !estado || fila.dataset.estado === estado;
                fila.style.display = (coincideTexto && coincideEstado) ? '' : 'none';
            });
        }

        if (buscar && filtroEstado) {
            buscar.addEventListener('input', aplicarFiltros);
            filtroEstado.addEventListener('change', aplicarFiltros);
        }
    })();
</script>

@endsection
