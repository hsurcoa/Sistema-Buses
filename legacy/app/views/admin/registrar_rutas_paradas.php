<?php
$rutas = $data['rutas'] ?? [];
$ruta = $data['ruta'];
$puntos = $data['puntos'] ?? [];
$tarifas = $data['tarifas'] ?? [];
$esAdmin = !empty($data['es_admin']);
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$paradas = array_values(array_filter($puntos, fn($p) => $p['tipo'] === 'parada'));
$subidas = array_values(array_filter($puntos, fn($p) => $p['tipo'] !== 'destino'));
$bajadas = array_values(array_filter($puntos, fn($p) => $p['tipo'] !== 'origen'));
?>
<style>
    .ruta-lista a { display: flex; justify-content: space-between; gap: .5rem; padding: .65rem .8rem; border-radius: 10px; text-decoration: none; color: var(--color-text); }
    .ruta-lista a:hover { background: var(--color-bg); }
    .ruta-lista a.activa { background: var(--accent-light); color: var(--accent-dark); }
    body.dark-mode .ruta-lista a.activa { background: #24254a; color: #c7d2fe; }
    .ruta-lista .meta { font-size: .78rem; color: var(--color-text-muted); }
    .parada-fila { display: grid; grid-template-columns: 2rem minmax(0, 1fr) 130px auto; gap: .5rem; align-items: center; padding: .4rem 0; border-bottom: 1px solid var(--color-border); }
    .parada-fila .n { font-weight: 700; color: var(--color-text-muted); text-align: center; font-variant-numeric: tabular-nums; }
    .punto-fijo { display: flex; align-items: center; gap: .5rem; padding: .5rem 0; font-weight: 600; }
    .punto-fijo .dot { width: 12px; height: 12px; border-radius: 50%; background: var(--accent); }
    .matriz { border-collapse: separate; border-spacing: 0; font-size: .85rem; }
    .matriz th, .matriz td { padding: .35rem; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
    .matriz thead th { position: sticky; top: 0; background: var(--color-card-bg); font-size: .75rem; color: var(--color-text-muted); text-align: center; vertical-align: bottom; }
    .matriz tbody th { position: sticky; left: 0; background: var(--color-card-bg); text-align: left; font-weight: 600; }
    .matriz input { width: 84px; text-align: right; font-variant-numeric: tabular-nums; }
    .matriz input.sugerida { background: #fff7e0; border-color: #f0c36d; }
    .matriz input.alerta { border-color: #dc2626; box-shadow: 0 0 0 2px rgba(220, 38, 38, .15); }
    body.dark-mode .matriz input.sugerida { background: #3a2e12; border-color: #8a6a1f; }
    .matriz td.vacia { background: repeating-linear-gradient(45deg, transparent, transparent 4px, var(--color-border) 4px, var(--color-border) 5px); }
    .leyenda-matriz { display: flex; flex-wrap: wrap; gap: 1rem; font-size: .8rem; color: var(--color-text-muted); }
    .leyenda-matriz i { display: inline-block; width: 14px; height: 14px; border-radius: 3px; vertical-align: -2px; margin-right: 4px; border: 1px solid; }
</style>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Rutas y tarifas</h3>
            <div class="text-muted small">Paradas de cada ruta y precio de cada tramo. La venta cobra según dónde sube y dónde baja el pasajero, y un asiento solo queda ocupado en su tramo.</div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-3">
                <!-- Rutas -->
                <div class="col-xl-3">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Rutas</h3></div>
                        <div class="card-body p-2">
                            <nav class="ruta-lista d-grid gap-1" aria-label="Rutas">
                                <?php foreach ($rutas as $r): ?>
                                    <a href="?ruta=<?php echo (int) $r->id; ?>" class="<?php echo $ruta && (int) $ruta->id === (int) $r->id ? 'activa' : ''; ?>">
                                        <span>
                                            <span class="fw-semibold"><?php echo $e($r->origen); ?> → <?php echo $e($r->destino); ?></span>
                                            <span class="meta d-block"><?php echo (int) $r->paradas; ?> paradas · <?php echo (int) $r->viajes; ?> viajes<?php echo $r->estado ? '' : ' · inactiva'; ?></span>
                                        </span>
                                        <?php if ($r->tarifas_sugeridas > 0): ?><span class="badge bg-warning-subtle text-warning-emphasis align-self-center" title="Tarifas calculadas automáticamente, sin revisar">Revisar</span><?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </nav>
                        </div>
                        <?php if ($esAdmin): ?>
                            <div class="card-footer bg-transparent">
                                <form action="<?php echo URLROOT; ?>/admin/guardar_ruta" method="POST" class="d-grid gap-2">
                                    <div class="small fw-semibold">Nueva ruta</div>
                                    <input class="form-control form-control-sm" name="origen" placeholder="Origen (p. ej. EL ALTO)" aria-label="Origen" required>
                                    <input class="form-control form-control-sm" name="destino" placeholder="Destino final" aria-label="Destino final" required>
                                    <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-plus-lg me-1"></i>Crear ruta</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!$ruta): ?>
                    <div class="col-xl-9"><div class="alert alert-info">No hay rutas registradas.</div></div>
                <?php else: ?>
                    <!-- Paradas -->
                    <div class="col-xl-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo $e($ruta->origen); ?> → <?php echo $e($ruta->destino); ?></h3>
                                <?php if ($esAdmin): ?>
                                    <div class="card-tools">
                                        <a class="btn btn-sm btn-light" href="<?php echo URLROOT; ?>/admin/cambiar_estado_ruta/<?php echo (int) $ruta->id; ?>"><?php echo $ruta->estado ? 'Desactivar' : 'Activar'; ?></a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div class="small text-muted mb-2">Paradas intermedias en orden de recorrido. La tarifa base de encomienda se usa al registrar envíos.</div>
                                <div class="punto-fijo"><span class="dot" aria-hidden="true"></span><?php echo $e($ruta->origen); ?> <span class="text-muted fw-normal small">origen</span></div>
                                <div id="listaParadas">
                                    <?php foreach ($paradas as $p): ?>
                                        <div class="parada-fila" data-id="<?php echo (int) $p['id']; ?>">
                                            <span class="n"></span>
                                            <input class="form-control form-control-sm js-nombre" value="<?php echo $e($p['nombre']); ?>" aria-label="Nombre de la parada" <?php echo $esAdmin ? '' : 'disabled'; ?>>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text" title="Tarifa base de encomienda">Enc. Bs</span>
                                                <input type="number" min="0" step="0.50" class="form-control js-encomienda" value="<?php echo $e(number_format($data['precio_encomienda'][$p['id']] ?? 0, 2, '.', '')); ?>" aria-label="Tarifa base de encomienda" <?php echo $esAdmin ? '' : 'disabled'; ?>>
                                            </div>
                                            <?php if ($esAdmin): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-light js-subir" title="Subir"><i class="bi bi-arrow-up"></i></button>
                                                    <button type="button" class="btn btn-light js-bajar" title="Bajar"><i class="bi bi-arrow-down"></i></button>
                                                    <button type="button" class="btn btn-light text-danger js-quitar" title="Quitar"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="punto-fijo"><span class="dot" aria-hidden="true"></span><?php echo $e($ruta->destino); ?> <span class="text-muted fw-normal small">destino final</span></div>

                                <?php if ($esAdmin): ?>
                                    <div class="d-flex gap-2 mt-3">
                                        <button type="button" class="btn btn-sm btn-light" id="btnAgregarParada"><i class="bi bi-plus-lg me-1"></i>Agregar parada</button>
                                        <button type="button" class="btn btn-sm btn-primary ms-auto" id="btnGuardarParadas"><i class="bi bi-check2 me-1"></i>Guardar paradas</button>
                                    </div>
                                    <div class="small text-muted mt-2">Una parada con boletos vendidos no se borra: se desactiva y el historial conserva su nombre.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tarifas -->
                    <div class="col-xl-5">
                        <div class="card h-100">
                            <div class="card-header">
                                <h3 class="card-title">Tarifas por tramo (Bs)</h3>
                                <?php if ($esAdmin): ?><div class="card-tools"><button type="button" class="btn btn-sm btn-primary" id="btnGuardarTarifas"><i class="bi bi-check2 me-1"></i>Guardar tarifas</button></div><?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div class="leyenda-matriz mb-2">
                                    <span>Filas: <strong>sube en</strong> · columnas: <strong>baja en</strong></span>
                                    <span><i style="background:#fff7e0;border-color:#f0c36d"></i>Calculada automáticamente, revisar</span>
                                    <span><i style="border-color:#dc2626"></i>Cuesta más que llegar más lejos</span>
                                </div>
                                <div class="table-responsive" style="max-height: 520px;">
                                    <table class="matriz" id="matrizTarifas">
                                        <thead>
                                            <tr>
                                                <th scope="col"></th>
                                                <?php foreach ($bajadas as $b): ?>
                                                    <th scope="col"><?php echo $e($b['nombre']); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subidas as $s): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $e($s['nombre']); ?></th>
                                                    <?php foreach ($bajadas as $b): ?>
                                                        <?php
                                                        $desde = (int) $s['id'];
                                                        $hasta = $b['tipo'] === 'destino' ? 0 : (int) $b['id'];
                                                        $valida = $b['orden'] > $s['orden'];
                                                        $t = $tarifas[$desde . '-' . $hasta] ?? null;
                                                        ?>
                                                        <?php if (!$valida): ?>
                                                            <td class="vacia" aria-hidden="true"></td>
                                                        <?php else: ?>
                                                            <td>
                                                                <input type="number" min="0.5" step="0.50" class="form-control form-control-sm <?php echo ($t && $t['sugerido']) ? 'sugerida' : ''; ?>"
                                                                    data-desde="<?php echo $desde; ?>" data-hasta="<?php echo $hasta; ?>"
                                                                    value="<?php echo $t ? $e(number_format($t['precio'], 2, '.', '')) : ''; ?>"
                                                                    aria-label="Tarifa <?php echo $e($s['nombre'] . ' a ' . $b['nombre']); ?>" <?php echo $esAdmin ? '' : 'disabled'; ?>>
                                                            </td>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="small mt-2" id="avisosTarifas" aria-live="polite"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php if ($ruta): ?>
<script>
    (function() {
        const RUTA_ID = <?php echo (int) $ruta->id; ?>;
        const CSRF = '<?php echo $e($_SESSION['csrf_token'] ?? ''); ?>';
        const lista = document.getElementById('listaParadas');

        function numerar() {
            lista.querySelectorAll('.parada-fila').forEach((f, i) => f.querySelector('.n').textContent = i + 1);
        }
        numerar();

        // ---------- Paradas ----------
        function filaNueva() {
            const div = document.createElement('div');
            div.className = 'parada-fila';
            div.dataset.id = '';
            div.innerHTML = `<span class="n"></span>
                <input class="form-control form-control-sm js-nombre" placeholder="Nombre de la parada" aria-label="Nombre de la parada">
                <div class="input-group input-group-sm"><span class="input-group-text">Enc. Bs</span><input type="number" min="0" step="0.50" class="form-control js-encomienda" value="0.00" aria-label="Tarifa base de encomienda"></div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light js-subir" title="Subir"><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-light js-bajar" title="Bajar"><i class="bi bi-arrow-down"></i></button>
                    <button type="button" class="btn btn-light text-danger js-quitar" title="Quitar"><i class="bi bi-x-lg"></i></button>
                </div>`;
            return div;
        }

        lista.addEventListener('click', ev => {
            const fila = ev.target.closest('.parada-fila');
            if (!fila) return;
            if (ev.target.closest('.js-subir') && fila.previousElementSibling) lista.insertBefore(fila, fila.previousElementSibling);
            if (ev.target.closest('.js-bajar') && fila.nextElementSibling) lista.insertBefore(fila.nextElementSibling, fila);
            if (ev.target.closest('.js-quitar')) fila.remove();
            numerar();
        });

        const btnAgregar = document.getElementById('btnAgregarParada');
        if (btnAgregar) btnAgregar.addEventListener('click', () => {
            const f = filaNueva();
            lista.appendChild(f);
            numerar();
            f.querySelector('.js-nombre').focus();
        });

        const btnParadas = document.getElementById('btnGuardarParadas');
        if (btnParadas) btnParadas.addEventListener('click', () => {
            const paradas = [...lista.querySelectorAll('.parada-fila')].map(f => ({
                id: parseInt(f.dataset.id || '0', 10),
                nombre: f.querySelector('.js-nombre').value,
                precio_encomienda: f.querySelector('.js-encomienda').value
            }));
            enviar('guardar_paradas', { paradas });
        });

        // ---------- Tarifas ----------
        const inputs = [...document.querySelectorAll('#matrizTarifas input')];

        // Aviso: un tramo no deberia costar mas que ir mas lejos desde la misma subida
        function revisar() {
            const avisos = [];
            document.querySelectorAll('#matrizTarifas tbody tr').forEach(tr => {
                const celdas = [...tr.querySelectorAll('input')];
                celdas.forEach((inp, i) => {
                    const valor = parseFloat(inp.value || '0');
                    const masLejos = celdas.slice(i + 1).map(c => parseFloat(c.value || '0')).filter(v => v > 0);
                    const alerta = valor > 0 && masLejos.some(v => v < valor);
                    inp.classList.toggle('alerta', alerta);
                    if (alerta) avisos.push(`${tr.querySelector('th').textContent} → ${inp.getAttribute('aria-label').split(' a ').pop()}`);
                });
            });
            document.getElementById('avisosTarifas').innerHTML = avisos.length
                ? `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> ${avisos.length} tramo(s) cuestan más que llegar más lejos: ${avisos.slice(0, 4).join(', ')}${avisos.length > 4 ? '…' : ''}</span>`
                : '<span class="text-success"><i class="bi bi-check2-circle"></i> Tarifas coherentes.</span>';
        }
        inputs.forEach(inp => inp.addEventListener('input', () => { inp.classList.remove('sugerida'); revisar(); }));
        revisar();

        const btnTarifas = document.getElementById('btnGuardarTarifas');
        if (btnTarifas) btnTarifas.addEventListener('click', () => {
            const vacias = inputs.filter(i => !(parseFloat(i.value) > 0));
            if (vacias.length) {
                vacias[0].focus();
                Swal.fire({ icon: 'warning', title: 'Faltan tarifas', text: `Complete los ${vacias.length} tramo(s) vacíos o en 0.` });
                return;
            }
            enviar('guardar_tarifas', { tarifas: inputs.map(i => ({ desde: i.dataset.desde, hasta: i.dataset.hasta, precio: i.value })) });
        });

        function enviar(accion, datos) {
            fetch('<?php echo URLROOT; ?>/admin/' + accion, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(Object.assign({ csrf_token: CSRF, ruta_id: RUTA_ID }, datos))
            })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: res.message, timer: 1600, showConfirmButton: false }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: res.message });
                    }
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo contactar al servidor.' }));
        }
    })();
</script>
<?php endif; ?>
