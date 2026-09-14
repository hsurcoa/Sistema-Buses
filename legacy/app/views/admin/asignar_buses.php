<?php
$buses = $data['buses'] ?? [];
$asignaciones = $data['asignaciones'] ?? [];
$historial = !empty($data['historial']);
$busesSinChofer = array_values(array_filter($buses, fn($b) => empty($b->chofer_actual)));
$busesSinTipo = array_values(array_filter($buses, fn($b) => empty($b->tipo_bus_id)));
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h3 class="mb-0">Asignación de tripulación</h3>
                    <div class="text-muted small">Chofer y copiloto por defecto de cada bus. Al programar un viaje se proponen automáticamente.</div>
                </div>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo URLROOT; ?>/dashboard">Inicio</a></li>
                    <li class="breadcrumb-item">Registros</li>
                    <li class="breadcrumb-item active" aria-current="page">Asignar buses</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <?php if ($busesSinTipo): ?>
                <div class="alert alert-warning d-flex gap-3 align-items-start">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div>
                        <strong><?php echo count($busesSinTipo); ?> bus(es) sin tipo de bus definido:</strong>
                        <?php echo $e(implode(', ', array_map(fn($b) => "{$b->placa} ({$b->asientos} asientos)", $busesSinTipo))); ?>.
                        Sin tipo no se sabe su distribución de asientos y no se pueden programar viajes con ellos.
                        <a href="<?php echo URLROOT; ?>/admin/registrar_buses" class="alert-link">Completar en Gestión de Flota</a>.
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <!-- Formulario -->
                <div class="col-xl-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title" id="tituloFormulario"><i class="bi bi-person-plus me-2"></i>Nueva asignación</h3>
                        </div>
                        <div class="card-body">
                            <form id="formAsignacion" novalidate>
                                <input type="hidden" name="id" id="asignacionId">

                                <div class="mb-3">
                                    <label for="selectBus" class="form-label fw-semibold">Bus <span class="text-danger">*</span></label>
                                    <select class="form-select" id="selectBus" name="bus_id" required>
                                        <option value="">Seleccione un bus…</option>
                                        <?php foreach ($buses as $bus): ?>
                                            <option value="<?php echo $bus->id; ?>"
                                                data-tipo="<?php echo $bus->tipo_bus_id ? 1 : 0; ?>"
                                                data-chofer="<?php echo $e($bus->chofer_actual); ?>">
                                                <?php echo $e($bus->placa . ' · ' . trim($bus->marca . ' ' . $bus->modelo) . ' · ' . $bus->asientos . ' asientos'
                                                    . ($bus->tipo_nombre ? '' : ' · SIN TIPO')
                                                    . ($bus->chofer_actual ? ' · con ' . $bus->chofer_actual : '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text" id="ayudaBus"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="selectChofer" class="form-label fw-semibold">Chofer <span class="text-danger">*</span></label>
                                    <select class="form-select" id="selectChofer" name="chofer_id" required>
                                        <option value="">Seleccione un chofer…</option>
                                        <?php foreach ($data['choferes'] ?? [] as $p): ?>
                                            <option value="<?php echo $p->id; ?>">
                                                <?php echo $e("{$p->apellidos} {$p->nombres}" . ($p->numero_documento ? " · CI {$p->numero_documento}" : '') . ($p->bus_actual ? " · en {$p->bus_actual}" : '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="selectCopiloto" class="form-label fw-semibold">Copiloto <span class="text-muted fw-normal">(opcional)</span></label>
                                    <select class="form-select" id="selectCopiloto" name="copiloto_id">
                                        <option value="">Sin copiloto</option>
                                        <?php foreach ($data['copilotos'] ?? [] as $p): ?>
                                            <option value="<?php echo $p->id; ?>">
                                                <?php echo $e("{$p->apellidos} {$p->nombres}" . ($p->bus_actual ? " · en {$p->bus_actual}" : '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1" id="btnGuardar">
                                        <i class="bi bi-check2 me-1"></i> Guardar asignación
                                    </button>
                                    <button type="button" class="btn btn-light" id="btnCancelarEdicion" hidden>Cancelar</button>
                                </div>
                            </form>

                            <?php if ($busesSinChofer): ?>
                                <hr>
                                <div class="small text-muted mb-2">Buses activos sin tripulación:</div>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($busesSinChofer as $b): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="elegirBus(<?php echo $b->id; ?>)">
                                            <?php echo $e($b->placa); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Listado -->
                <div class="col-xl-8">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title"><i class="bi bi-list-ul me-2"></i><?php echo $historial ? 'Historial de asignaciones' : 'Asignaciones activas'; ?></h3>
                            <div class="card-tools">
                                <a class="btn btn-sm btn-light" href="<?php echo URLROOT; ?>/admin/asignar_buses<?php echo $historial ? '' : '?historial=1'; ?>">
                                    <?php echo $historial ? 'Ver solo activas' : 'Ver historial'; ?>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <input type="search" class="form-control mb-3" id="buscarAsignacion" placeholder="Buscar por chofer, copiloto, placa o tipo…">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="tablaAsignaciones">
                                    <thead>
                                        <tr>
                                            <th>Bus</th>
                                            <th>Asientos / tipo</th>
                                            <th>Chofer</th>
                                            <th>Copiloto</th>
                                            <th>Desde</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!$asignaciones): ?>
                                            <tr><td colspan="6" class="text-center text-muted py-4">No hay asignaciones <?php echo $historial ? 'registradas' : 'activas'; ?>.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($asignaciones as $a): ?>
                                            <?php $coincide = $a->tipo_capacidad !== null && (int) $a->tipo_capacidad === (int) $a->asientos; ?>
                                            <tr class="<?php echo $a->estado ? '' : 'text-muted'; ?>">
                                                <td>
                                                    <span class="badge bg-dark font-monospace"><?php echo $e($a->placa_bus); ?></span>
                                                    <div class="small text-muted"><?php echo $e(trim($a->marca . ' ' . $a->modelo)); ?></div>
                                                </td>
                                                <td>
                                                    <strong><?php echo (int) $a->asientos; ?></strong>
                                                    <?php if ($a->tipo_nombre): ?>
                                                        <div class="small <?php echo $coincide ? 'text-muted' : 'text-danger'; ?>">
                                                            <?php echo $e($a->tipo_nombre); ?> (<?php echo (int) $a->tipo_capacidad; ?>)
                                                            <?php if (!$coincide): ?><i class="bi bi-exclamation-triangle-fill" title="La capacidad del tipo no coincide con los asientos del bus"></i><?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="small text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Sin tipo</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $e($a->nombre_chofer); ?>
                                                    <?php if ($a->documento_chofer): ?><div class="small text-muted">CI <?php echo $e($a->documento_chofer); ?></div><?php endif; ?>
                                                </td>
                                                <td><?php echo $a->nombre_copiloto ? $e($a->nombre_copiloto) : '<span class="text-muted">—</span>'; ?></td>
                                                <td class="small">
                                                    <?php echo $a->fecha_asignacion ? date('d/m/Y', strtotime($a->fecha_asignacion)) : '—'; ?>
                                                    <?php if (!$a->estado): ?><div><span class="badge bg-secondary-subtle text-secondary">Finalizada</span></div><?php endif; ?>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <?php if ($a->estado): ?>
                                                        <button type="button" class="btn btn-sm btn-light text-primary" title="Editar"
                                                            onclick="editarAsignacion(<?php echo (int) $a->id; ?>, <?php echo (int) $a->bus_id; ?>, <?php echo (int) $a->chofer_id; ?>, <?php echo (int) $a->copiloto_id; ?>)">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-light text-danger" title="Finalizar asignación"
                                                            onclick="finalizarAsignacion(<?php echo (int) $a->id; ?>, '<?php echo $e($a->placa_bus); ?>')">
                                                            <i class="bi bi-person-dash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    (function() {
        const URL_GUARDAR = '<?php echo URLROOT; ?>/admin/guardar_asignacion';
        const form = document.getElementById('formAsignacion');
        const btnGuardar = document.getElementById('btnGuardar');
        const btnCancelar = document.getElementById('btnCancelarEdicion');
        const selectBus = document.getElementById('selectBus');
        const ayudaBus = document.getElementById('ayudaBus');

        function actualizarAyudaBus() {
            const opt = selectBus.selectedOptions[0];
            if (!opt || !opt.value) {
                ayudaBus.textContent = '';
                ayudaBus.className = 'form-text';
                return;
            }
            const avisos = [];
            if (opt.dataset.tipo === '0') avisos.push('Este bus no tiene tipo de bus definido.');
            if (opt.dataset.chofer) avisos.push('Actualmente lo maneja ' + opt.dataset.chofer + '.');
            ayudaBus.textContent = avisos.join(' ');
            ayudaBus.className = 'form-text ' + (opt.dataset.tipo === '0' ? 'text-danger' : 'text-muted');
        }
        selectBus.addEventListener('change', actualizarAyudaBus);

        window.elegirBus = function(id) {
            selectBus.value = id;
            actualizarAyudaBus();
            document.getElementById('selectChofer').focus();
        };

        window.editarAsignacion = function(id, busId, choferId, copilotoId) {
            document.getElementById('asignacionId').value = id;
            selectBus.value = busId;
            document.getElementById('selectChofer').value = choferId;
            document.getElementById('selectCopiloto').value = copilotoId || '';
            document.getElementById('tituloFormulario').innerHTML = '<i class="bi bi-pencil me-2"></i>Editar asignación';
            btnCancelar.hidden = false;
            actualizarAyudaBus();
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        btnCancelar.addEventListener('click', function() {
            form.reset();
            document.getElementById('asignacionId').value = '';
            document.getElementById('tituloFormulario').innerHTML = '<i class="bi bi-person-plus me-2"></i>Nueva asignación';
            btnCancelar.hidden = true;
            actualizarAyudaBus();
        });

        function enviar(reemplazar) {
            const datos = new FormData(form);
            if (reemplazar) datos.append('reemplazar', '1');

            btnGuardar.disabled = true;
            fetch(URL_GUARDAR, { method: 'POST', body: datos, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'conflict') {
                        return Swal.fire({
                            icon: 'warning',
                            title: 'La asignación choca con otras',
                            html: '<ul class="text-start mb-2">' + res.conflictos.map(c => '<li>' + escapeHtml(c) + '</li>').join('') + '</ul>' +
                                  '<div class="small text-muted">Si continúa, esas asignaciones se finalizan (quedan en el historial).</div>',
                            showCancelButton: true,
                            confirmButtonText: 'Reemplazar y guardar',
                            cancelButtonText: 'Revisar',
                        }).then(r => { if (r.isConfirmed) enviar(true); });
                    }
                    if (res.status === 'success') {
                        const avisos = (res.avisos || []).map(a => '<li>' + escapeHtml(a) + '</li>').join('');
                        return Swal.fire({
                            icon: avisos ? 'info' : 'success',
                            title: res.message,
                            html: avisos ? '<ul class="text-start mb-0">' + avisos + '</ul>' : undefined,
                        }).then(() => window.location.href = '<?php echo URLROOT; ?>/admin/asignar_buses');
                    }
                    Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: res.message || 'Error inesperado.' });
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo contactar al servidor.' }))
                .finally(() => { btnGuardar.disabled = false; });
        }

        form.addEventListener('submit', function(ev) {
            ev.preventDefault();
            if (!selectBus.value || !document.getElementById('selectChofer').value) {
                Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Seleccione el bus y el chofer.' });
                return;
            }
            enviar(false);
        });

        window.finalizarAsignacion = function(id, placa) {
            Swal.fire({
                icon: 'question',
                title: 'Finalizar asignación',
                text: 'El bus ' + placa + ' quedará sin tripulación por defecto. La asignación se conserva en el historial.',
                showCancelButton: true,
                confirmButtonText: 'Finalizar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            }).then(r => {
                if (r.isConfirmed) window.location.href = '<?php echo URLROOT; ?>/admin/eliminar_asignacion/' + id;
            });
        };

        document.getElementById('buscarAsignacion').addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('#tablaAsignaciones tbody tr').forEach(tr => {
                tr.hidden = q !== '' && !tr.textContent.toLowerCase().includes(q);
            });
        });

        function escapeHtml(t) {
            const d = document.createElement('div');
            d.textContent = t;
            return d.innerHTML;
        }
    })();
</script>
