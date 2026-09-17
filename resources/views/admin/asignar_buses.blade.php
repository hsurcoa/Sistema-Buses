@extends('layouts.app')

@section('content')
<?php
$buses = $data['buses'] ?? [];
$choferes = $data['choferes'] ?? [];
$copilotos = $data['copilotos'] ?? [];
$esAdmin = !empty($data['es_admin']);
$busesSinTipo = array_values(array_filter($buses, fn($b) => empty($b->tipo_bus_id)));
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<style>
    /* ---------------- Personal disponible (chips) ---------------- */
    .personal-lista { display: flex; flex-direction: column; gap: .4rem; max-height: 260px; overflow-y: auto; padding-right: 2px; }
    .chip-persona {
        display: flex; align-items: center; gap: .6rem;
        background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-btn);
        padding: .45rem .6rem; cursor: grab; user-select: none;
    }
    .chip-persona:active { cursor: grabbing; }
    .chip-persona.dragging { opacity: .4; }
    .chip-persona .avatar {
        width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, var(--accent), var(--accent-dark)); color: #fff; font-weight: 700; font-size: .8rem;
    }
    .chip-persona.es-copiloto .avatar { background: linear-gradient(135deg, var(--accent-emerald), #0891b2); }
    .chip-persona .info { min-width: 0; flex: 1; }
    .chip-persona .nombre { font-size: .85rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chip-persona .meta { font-size: .72rem; color: var(--color-text-muted); }
    .chip-persona .meta.ocupado { color: #b45309; }

    /* ---------------- Tarjetas de bus ---------------- */
    .bus-card { background: var(--color-card-bg); border: 1px solid var(--color-border); border-radius: var(--radius-card); padding: .9rem; height: 100%; display: flex; flex-direction: column; gap: .6rem; }
    .bus-card .placa { font-family: monospace; font-weight: 800; font-size: 1rem; background: #111827; color: #fff; padding: .15rem .55rem; border-radius: 6px; display: inline-block; }
    .bus-card .bus-info { font-size: .76rem; color: var(--color-text-muted); }
    .bus-card .bus-info.text-danger { color: #dc2626 !important; }

    .slot { border: 2px dashed var(--color-border); border-radius: var(--radius-btn); padding: .5rem .6rem; min-height: 52px; display: flex; align-items: center; gap: .5rem; cursor: pointer; transition: border-color .15s ease, background-color .15s ease; }
    .slot:hover { border-color: var(--accent); background: var(--color-bg); }
    .slot.sobre-drag { border-color: var(--accent); background: var(--accent-light); }
    .slot .slot-vacio { color: var(--color-text-muted); font-size: .82rem; display: flex; align-items: center; gap: .4rem; }
    .slot.slot-falta .slot-vacio { color: #dc2626; font-weight: 600; }
    .slot .chip-persona { flex: 1; border: 0; background: transparent; padding: 0; cursor: default; }
    .slot .btn-quitar { border: 0; background: none; color: var(--color-text-muted); padding: .2rem; flex-shrink: 0; }
    .slot .btn-quitar:hover { color: #dc2626; }
    .slot-label { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--color-text-muted); margin-bottom: .25rem; }
</style>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h3 class="mb-0">Choferes</h3>
                    <div class="text-muted small">Arrastrá o tocá para asignar chofer y chofer de relevo a cada bus. Se guarda al toque, sin formularios.</div>
                </div>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo URLROOT; ?>/dashboard">Inicio</a></li>
                    <li class="breadcrumb-item">Registros</li>
                    <li class="breadcrumb-item active" aria-current="page">Choferes</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <?php if ($busesSinTipo): ?>
                <div class="alert alert-warning d-flex gap-3 align-items-start mb-3">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div>
                        <strong><?php echo count($busesSinTipo); ?> bus(es) sin tipo de bus definido:</strong>
                        <?php echo $e(implode(', ', collect($busesSinTipo)->map(fn($b) => $b->placa)->all())); ?>.
                        <a href="<?php echo URLROOT; ?>/admin/registrar_buses" class="alert-link">Completar en Gestión de Flota</a>.
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <!-- Personal disponible -->
                <div class="col-xl-3">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="bi bi-people me-2"></i>Personal disponible</h3></div>
                        <?php if ($esAdmin): ?>
                            <div class="card-body pb-0 d-grid gap-2">
                                <button type="button" class="btn btn-primary" id="btnNuevoChofer"><i class="bi bi-person-plus me-1"></i>Nuevo chofer</button>
                                <button type="button" class="btn btn-outline-secondary" id="btnNuevoCopiloto"><i class="bi bi-person-plus me-1"></i>Nuevo chofer de relevo</button>
                            </div>
                            <hr class="my-2 mx-3">
                        <?php endif; ?>
                        <div class="card-body pt-0">
                            <input type="search" class="form-control form-control-sm mb-3" id="buscarPersona" placeholder="Buscar por nombre o CI…">

                            <div class="slot-label">Choferes</div>
                            <div class="personal-lista mb-3" id="poolChoferes" data-perfil="Chofer">
                                <?php foreach ($choferes as $p): ?>
                                    <div class="chip-persona" draggable="true" data-id="<?php echo (int) $p->id; ?>" data-perfil="Chofer"
                                        data-buscar="<?php echo $e(mb_strtolower($p->nombres . ' ' . $p->apellidos . ' ' . $p->numero_documento)); ?>">
                                        <span class="avatar"><?php echo $e(mb_strtoupper(mb_substr($p->nombres, 0, 1))); ?></span>
                                        <div class="info">
                                            <div class="nombre"><?php echo $e($p->nombres . ' ' . $p->apellidos); ?></div>
                                            <div class="meta<?php echo $p->bus_actual ? ' ocupado' : ''; ?>">
                                                <?php echo $p->numero_documento ? 'CI ' . $e($p->numero_documento) : 'Sin CI'; ?>
                                                <?php echo $p->bus_actual ? ' · en ' . $e($p->bus_actual) : ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!$choferes): ?><div class="small text-muted">No hay choferes registrados.</div><?php endif; ?>
                            </div>

                            <div class="slot-label">Choferes de relevo</div>
                            <div class="personal-lista" id="poolCopilotos" data-perfil="Copiloto">
                                <?php foreach ($copilotos as $p): ?>
                                    <div class="chip-persona es-copiloto" draggable="true" data-id="<?php echo (int) $p->id; ?>" data-perfil="Copiloto"
                                        data-buscar="<?php echo $e(mb_strtolower($p->nombres . ' ' . $p->apellidos)); ?>">
                                        <span class="avatar"><?php echo $e(mb_strtoupper(mb_substr($p->nombres, 0, 1))); ?></span>
                                        <div class="info">
                                            <div class="nombre"><?php echo $e($p->nombres . ' ' . $p->apellidos); ?></div>
                                            <div class="meta<?php echo $p->bus_actual ? ' ocupado' : ''; ?>"><?php echo $p->bus_actual ? 'en ' . $e($p->bus_actual) : 'Disponible'; ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!$copilotos): ?><div class="small text-muted">No hay choferes de relevo registrados.</div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tablero de buses -->
                <div class="col-xl-9">
                    <div class="row g-3" id="tableroBuses">
                        <?php foreach ($buses as $b): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="bus-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="placa"><?php echo $e($b->placa); ?></span>
                                            <div class="bus-info mt-1"><?php echo $e(trim($b->marca . ' ' . $b->modelo)); ?> · <?php echo (int) $b->asientos; ?> asientos</div>
                                        </div>
                                        <?php if (!$b->tipo_nombre): ?>
                                            <span class="badge bg-danger-subtle text-danger" title="Sin tipo de bus">Sin tipo</span>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <div class="slot-label">Chofer</div>
                                        <div class="slot<?php echo $b->chofer_id ? '' : ' slot-falta'; ?>" data-bus="<?php echo (int) $b->id; ?>" data-tipo="chofer" data-asignacion="<?php echo (int) ($b->asignacion_id ?? 0); ?>">
                                            <?php if ($b->chofer_id): ?>
                                                <div class="chip-persona" data-id="<?php echo (int) $b->chofer_id; ?>">
                                                    <span class="avatar"><?php echo $e(mb_strtoupper(mb_substr($b->chofer_actual, 0, 1))); ?></span>
                                                    <div class="info"><div class="nombre"><?php echo $e($b->chofer_actual); ?></div></div>
                                                </div>
                                                <button type="button" class="btn-quitar js-finalizar" data-asignacion="<?php echo (int) $b->asignacion_id; ?>" data-placa="<?php echo $e($b->placa); ?>" title="Quitar chofer"><i class="bi bi-x-lg"></i></button>
                                            <?php else: ?>
                                                <span class="slot-vacio"><i class="bi bi-plus-circle"></i> Sin chofer — tocá o soltá aquí</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="slot-label">Chofer de relevo (opcional)</div>
                                        <div class="slot" data-bus="<?php echo (int) $b->id; ?>" data-tipo="copiloto" data-asignacion="<?php echo (int) ($b->asignacion_id ?? 0); ?>">
                                            <?php if ($b->copiloto_id): ?>
                                                <div class="chip-persona es-copiloto" data-id="<?php echo (int) $b->copiloto_id; ?>">
                                                    <span class="avatar"><?php echo $e(mb_strtoupper(mb_substr($b->nombre_copiloto, 0, 1))); ?></span>
                                                    <div class="info"><div class="nombre"><?php echo $e($b->nombre_copiloto); ?></div></div>
                                                </div>
                                                <button type="button" class="btn-quitar js-quitar-copiloto" data-asignacion="<?php echo (int) $b->asignacion_id; ?>" data-bus="<?php echo (int) $b->id; ?>" data-chofer="<?php echo (int) $b->chofer_id; ?>" title="Quitar chofer de relevo"><i class="bi bi-x-lg"></i></button>
                                            <?php else: ?>
                                                <span class="slot-vacio"><i class="bi bi-plus-circle"></i> Sin chofer de relevo</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$buses): ?><div class="col-12"><div class="alert alert-info">No hay buses activos.</div></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal: nuevo chofer/copiloto -->
<div class="modal fade" id="modalNuevaPersona" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalPersona">Nuevo chofer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaPersona">
                    <input type="hidden" id="npPerfil" value="Chofer">
                    <div class="mb-3">
                        <label class="form-label">Nombres <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="npNombres" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="npApellidos" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">CI</label>
                            <input type="text" class="form-control" id="npDocumento">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Celular</label>
                            <input type="text" class="form-control" id="npCelular">
                        </div>
                    </div>
                    <div class="small text-muted mt-3"><i class="bi bi-info-circle me-1"></i>No se crea cuenta de acceso al sistema: solo queda registrado para poder asignarlo a un bus.</div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarPersona"><i class="bi bi-check2 me-1"></i>Registrar</button>
            </div>
        </div>
    </div>
</div>

<?php if ($esAdmin): ?>
<script>
    (function() {
        const CSRF = '<?php echo $e(csrf_token()); ?>';
        const URLROOT = '<?php echo URLROOT; ?>';

        // ---------- Buscador de personal ----------
        document.getElementById('buscarPersona').addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.personal-lista .chip-persona').forEach(chip => {
                chip.hidden = q !== '' && !chip.dataset.buscar.includes(q);
            });
        });

        // ---------- Arrastrar chips desde el pool a un casillero ----------
        document.querySelectorAll('.personal-lista .chip-persona').forEach(chip => {
            chip.addEventListener('dragstart', ev => {
                chip.classList.add('dragging');
                ev.dataTransfer.setData('text/plain', JSON.stringify({ id: chip.dataset.id, perfil: chip.dataset.perfil }));
                ev.dataTransfer.effectAllowed = 'copy';
            });
            chip.addEventListener('dragend', () => chip.classList.remove('dragging'));
        });

        document.querySelectorAll('.slot').forEach(slot => {
            slot.addEventListener('dragover', ev => {
                const tipo = slot.dataset.tipo; // chofer | copiloto
                ev.preventDefault();
                ev.dataTransfer.dropEffect = 'copy';
                slot.classList.add('sobre-drag');
            });
            slot.addEventListener('dragleave', () => slot.classList.remove('sobre-drag'));
            slot.addEventListener('drop', ev => {
                ev.preventDefault();
                slot.classList.remove('sobre-drag');
                let datos;
                try { datos = JSON.parse(ev.dataTransfer.getData('text/plain')); } catch (e) { return; }
                const perfilEsperado = slot.dataset.tipo === 'chofer' ? 'Chofer' : 'Copiloto';
                if (datos.perfil !== perfilEsperado) {
                    Swal.fire({ icon: 'warning', title: 'No corresponde', text: `Este casillero es para ${perfilEsperado === 'Chofer' ? 'un chofer' : 'un chofer de relevo'}.` });
                    return;
                }
                asignar(slot, datos.id);
            });

            // ---------- Click / touch: elegir de una lista (siempre funciona, sin arrastrar) ----------
            slot.addEventListener('click', ev => {
                if (ev.target.closest('.btn-quitar')) return;
                const tipo = slot.dataset.tipo;
                const etiqueta = tipo === 'chofer' ? 'chofer' : 'chofer de relevo';
                const pool = tipo === 'chofer' ? window.CHOFERES : window.COPILOTOS;
                if (!pool.length) {
                    Swal.fire({ icon: 'info', title: 'No hay nadie disponible', text: `Registrá un ${etiqueta} primero con el botón "Nuevo ${etiqueta}".` });
                    return;
                }
                const opciones = {};
                pool.forEach(p => { opciones[p.id] = p.nombres + ' ' + p.apellidos + (p.numero_documento ? ' · CI ' + p.numero_documento : '') + (p.bus_actual ? ' · en ' + p.bus_actual : ''); });
                Swal.fire({
                    icon: 'question',
                    title: tipo === 'chofer' ? 'Elegir chofer' : 'Elegir chofer de relevo',
                    input: 'select',
                    inputOptions: opciones,
                    inputPlaceholder: 'Seleccione…',
                    showCancelButton: true,
                    confirmButtonText: 'Asignar',
                }).then(r => { if (r.isConfirmed && r.value) asignar(slot, r.value); });
            });
        });

        function asignar(slot, personaId, reemplazar) {
            const bus = slot.dataset.bus;
            const tipo = slot.dataset.tipo;
            const asignacionId = slot.dataset.asignacion || '';

            // El chofer no se toca si estamos asignando copiloto (y viceversa): se
            // lee del casillero hermano dentro de la misma tarjeta.
            const tarjeta = slot.closest('.bus-card');
            const slotChofer = tarjeta.querySelector('.slot[data-tipo="chofer"] .chip-persona');
            const slotCopiloto = tarjeta.querySelector('.slot[data-tipo="copiloto"] .chip-persona');

            const choferId = tipo === 'chofer' ? personaId : (slotChofer ? slotChofer.dataset.id : '');
            const copilotoId = tipo === 'copiloto' ? personaId : (slotCopiloto ? slotCopiloto.dataset.id : '');

            if (!choferId) {
                Swal.fire({ icon: 'warning', title: 'Falta el chofer', text: 'Asigná primero un chofer a este bus.' });
                return;
            }

            const datos = new FormData();
            datos.append('id', asignacionId);
            datos.append('bus_id', bus);
            datos.append('chofer_id', choferId);
            datos.append('copiloto_id', copilotoId || '');
            if (reemplazar) datos.append('reemplazar', '1');

            fetch(URLROOT + '/admin/guardar_asignacion', { method: 'POST', body: datos, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'conflict') {
                        return Swal.fire({
                            icon: 'warning',
                            title: 'Choca con otra asignación',
                            html: '<ul class="text-start mb-2">' + res.conflictos.map(c => '<li>' + escapeHtml(c) + '</li>').join('') + '</ul>' +
                                  '<div class="small text-muted">Si continúa, esas asignaciones se finalizan (quedan en el historial).</div>',
                            showCancelButton: true,
                            confirmButtonText: 'Reemplazar y guardar',
                            cancelButtonText: 'Cancelar',
                        }).then(r => { if (r.isConfirmed) asignar(slot, personaId, true); });
                    }
                    if (res.status === 'success') {
                        return Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false }).then(() => location.reload());
                    }
                    Swal.fire({ icon: 'error', title: 'No se pudo asignar', text: res.message || 'Error inesperado.' });
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo contactar al servidor.' }));
        }

        // ---------- Quitar chofer (finaliza toda la asignación) ----------
        document.querySelectorAll('.js-finalizar').forEach(btn => btn.addEventListener('click', ev => {
            ev.stopPropagation();
            const id = btn.dataset.asignacion;
            const placa = btn.dataset.placa;
            Swal.fire({
                icon: 'question',
                title: 'Quitar chofer',
                text: `El bus ${placa} quedará sin tripulación por defecto.`,
                showCancelButton: true,
                confirmButtonText: 'Quitar',
                confirmButtonColor: '#dc3545',
            }).then(r => { if (r.isConfirmed) window.location.href = URLROOT + '/admin/eliminar_asignacion/' + id; });
        }));

        // ---------- Quitar solo el copiloto (mantiene el chofer) ----------
        document.querySelectorAll('.js-quitar-copiloto').forEach(btn => btn.addEventListener('click', ev => {
            ev.stopPropagation();
            const datos = new FormData();
            datos.append('id', btn.dataset.asignacion);
            datos.append('bus_id', btn.dataset.bus);
            datos.append('chofer_id', btn.dataset.chofer);
            datos.append('copiloto_id', '');
            fetch(URLROOT + '/admin/guardar_asignacion', { method: 'POST', body: datos, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') location.reload();
                    else Swal.fire({ icon: 'error', title: 'No se pudo quitar', text: res.message });
                });
        }));

        function escapeHtml(t) {
            const d = document.createElement('div');
            d.textContent = t;
            return d.innerHTML;
        }

        // ---------- Nuevo chofer / copiloto ----------
        // OJO: bootstrap.bundle.min.js se carga en layouts/footer.php, DESPUES
        // del contenido de esta vista - si se crea el Modal() aca arriba (fuera
        // de una funcion), "bootstrap" todavia no existe y esto corta la
        // ejecucion de TODO el script que sigue (por eso el boton "Nuevo
        // chofer" quedaba sin funcionar). Se crea recien al primer click, para
        // entonces la pagina ya termino de cargar.
        function abrirModalPersona(perfil) {
            document.getElementById('formNuevaPersona').reset();
            document.getElementById('npPerfil').value = perfil;
            document.getElementById('tituloModalPersona').textContent = perfil === 'Chofer' ? 'Nuevo chofer' : 'Nuevo chofer de relevo';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNuevaPersona')).show();
        }
        document.getElementById('btnNuevoChofer').addEventListener('click', () => abrirModalPersona('Chofer'));
        document.getElementById('btnNuevoCopiloto').addEventListener('click', () => abrirModalPersona('Copiloto'));

        document.getElementById('btnGuardarPersona').addEventListener('click', () => {
            const nombres = document.getElementById('npNombres').value.trim();
            const apellidos = document.getElementById('npApellidos').value.trim();
            if (!nombres || !apellidos) {
                Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Nombre y apellido son obligatorios.' });
                return;
            }
            const perfil = document.getElementById('npPerfil').value;
            fetch(URLROOT + '/admin/guardar_chofer_rapido', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: CSRF,
                    perfil: perfil,
                    nombres: nombres,
                    apellidos: apellidos,
                    numero_documento: document.getElementById('npDocumento').value.trim(),
                    celular: document.getElementById('npCelular').value.trim(),
                })
            })
                .then(r => r.json())
                .then(res => {
                    if (res.status !== 'success') {
                        Swal.fire({ icon: 'error', title: 'No se pudo registrar', text: res.message });
                        return;
                    }
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNuevaPersona')).hide();
                    agregarChipAlPool(res.persona);
                    Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false });
                });
        });

        function agregarChipAlPool(persona) {
            const esChofer = persona.perfil === 'Chofer';
            const pool = document.getElementById(esChofer ? 'poolChoferes' : 'poolCopilotos');
            const div = document.createElement('div');
            div.className = 'chip-persona' + (esChofer ? '' : ' es-copiloto');
            div.draggable = true;
            div.dataset.id = persona.id;
            div.dataset.perfil = persona.perfil;
            div.dataset.buscar = (persona.nombres + ' ' + persona.apellidos + ' ' + (persona.numero_documento || '')).toLowerCase();
            div.innerHTML = `<span class="avatar">${escapeHtml(persona.nombres.charAt(0).toUpperCase())}</span>
                <div class="info">
                    <div class="nombre">${escapeHtml(persona.nombres + ' ' + persona.apellidos)}</div>
                    <div class="meta">${persona.numero_documento ? 'CI ' + escapeHtml(persona.numero_documento) : 'Disponible'}</div>
                </div>`;
            div.addEventListener('dragstart', ev => {
                div.classList.add('dragging');
                ev.dataTransfer.setData('text/plain', JSON.stringify({ id: div.dataset.id, perfil: div.dataset.perfil }));
                ev.dataTransfer.effectAllowed = 'copy';
            });
            div.addEventListener('dragend', () => div.classList.remove('dragging'));
            pool.appendChild(div);
            (esChofer ? window.CHOFERES : window.COPILOTOS).push(persona);
        }
    })();
</script>
<?php endif; ?>

<script>
    window.CHOFERES = <?php echo json_encode(collect($choferes)->map(fn($p) => ['id' => (int) $p->id, 'nombres' => $p->nombres, 'apellidos' => $p->apellidos, 'numero_documento' => $p->numero_documento, 'bus_actual' => $p->bus_actual])->values()->all(), JSON_UNESCAPED_UNICODE); ?>;
    window.COPILOTOS = <?php echo json_encode(collect($copilotos)->map(fn($p) => ['id' => (int) $p->id, 'nombres' => $p->nombres, 'apellidos' => $p->apellidos, 'numero_documento' => $p->numero_documento ?? null, 'bus_actual' => $p->bus_actual])->values()->all(), JSON_UNESCAPED_UNICODE); ?>;
</script>

@endsection
