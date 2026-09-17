@extends('layouts.app')

@section('content')
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
$idPunto = fn($p) => $p['tipo'] === 'destino' ? 0 : (int) $p['id'];
?>
<style>
    .ruta-lista a { display: flex; justify-content: space-between; gap: .5rem; padding: .65rem .8rem; border-radius: 10px; text-decoration: none; color: var(--color-text); }
    .ruta-lista a:hover { background: var(--color-bg); }
    .ruta-lista a.activa { background: var(--accent-light); color: var(--accent-dark); }
    body.dark-mode .ruta-lista a.activa { background: #24254a; color: #c7d2fe; }
    .ruta-lista .meta { font-size: .78rem; color: var(--color-text-muted); }

    /* ---------------- Mapa del recorrido ---------------- */
    .mapa-ruta-wrap { position: relative; }
    .mapa-ruta { height: 320px; width: 100%; border-radius: var(--radius-card); border: 1px solid var(--color-border); background: var(--color-bg); z-index: 0; }
    .marcador-parada {
        width: 28px; height: 28px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg);
        background: var(--accent); border: 2px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,.35);
        display: flex; align-items: center; justify-content: center;
    }
    .marcador-parada span { transform: rotate(45deg); color: #fff; font-weight: 800; font-size: .78rem; }
    .marcador-origen { background: var(--accent); }
    .marcador-destino { background: var(--accent-emerald); }

    .paso-titulo { display: flex; align-items: center; gap: .6rem; font-size: 1.05rem; font-weight: 700; margin-bottom: 1rem; }
    .paso-numero {
        width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--accent); color: #fff; font-weight: 800; font-size: .95rem;
    }

    /* ---------------- Columna 1: paradas (filas compactas, una linea) ---------------- */
    .pv-lista { position: relative; }
    .pv-lista::before {
        content: "";
        position: absolute;
        left: 47px; top: 22px; bottom: 22px;
        width: 2px;
        background: var(--color-border);
        z-index: 0;
    }
    .pv-nodo { position: relative; display: flex; align-items: center; gap: .5rem; padding: .3rem 0; z-index: 1; }

    .pv-gutter { width: 22px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .pv-handle { cursor: grab; color: var(--color-text-muted); font-size: 1.2rem; touch-action: none; }
    .pv-handle:active { cursor: grabbing; }

    .pv-punto {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; font-weight: 800; color: #fff; background: var(--accent);
        box-shadow: 0 0 0 4px var(--color-card-bg);
    }
    .pv-punto-origen { background: var(--accent); font-size: 1.05rem; }
    .pv-punto-destino { background: var(--accent-emerald); font-size: 1.05rem; }

    .pv-info { display: flex; flex-direction: column; padding: .3rem 0; }
    .pv-badge { font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--color-text-muted); }
    .pv-nombre-fijo { font-weight: 700; font-size: 1.05rem; }

    .pv-card {
        flex: 1; display: flex; align-items: center; gap: .5rem;
        background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-btn);
        padding: .4rem .6rem; min-width: 0;
    }
    .pv-card:hover { border-color: var(--accent); }
    .pv-nodo.sortable-ghost .pv-card { opacity: .35; }
    .pv-nodo.sortable-chosen .pv-card { border-color: var(--accent); box-shadow: 0 4px 14px rgba(99, 102, 241, .18); }
    .pv-nombre { flex: 1 1 auto; min-width: 70px; font-size: .95rem; font-weight: 600; border: 0; background: transparent; padding: .2rem 0; }
    .pv-nombre:focus { outline: none; border-bottom: 2px solid var(--accent); }
    .pv-encomienda { display: flex; align-items: center; gap: .3rem; font-size: .74rem; color: var(--color-text-muted); flex-shrink: 0; }
    .pv-encomienda input { width: 58px; font-size: .78rem; padding: .2rem .3rem; }
    .pv-quitar { border: 0; background: none; color: var(--color-text-muted); font-size: .95rem; padding: .2rem; flex-shrink: 0; }
    .pv-quitar:hover { color: #dc2626; }

    /* ---------------- Columna 2: precio por tramo ---------------- */
    .tr-lista { display: flex; flex-direction: column; gap: .6rem; }
    .tr-fila { display: flex; align-items: center; gap: .75rem; background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-card); padding: .65rem .9rem; }
    .tr-nombres { flex: 1; font-size: .92rem; font-weight: 600; }
    .tr-nombres .flecha { color: var(--color-text-muted); margin: 0 .3rem; }
    .tr-precio { width: 130px; flex-shrink: 0; }
    .tr-precio .input-group-text { font-weight: 700; }
    .tr-precio input { font-size: 1.05rem; font-weight: 700; text-align: right; }
    .tr-precio input.sugerida { background: #fff7e0; border-color: #f0c36d; }
    .tr-precio input.alerta { border-color: #dc2626; box-shadow: 0 0 0 2px rgba(220, 38, 38, .15); }
    body.dark-mode .tr-precio input.sugerida { background: #3a2e12; border-color: #8a6a1f; }

    /* ---------------- Matriz avanzada (colapsada por defecto) ---------------- */
    .matriz { border-collapse: separate; border-spacing: 0; font-size: .85rem; }
    .matriz th, .matriz td { padding: .35rem; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
    .matriz thead th { position: sticky; top: 0; background: var(--color-card-bg); font-size: .75rem; color: var(--color-text-muted); text-align: center; vertical-align: bottom; z-index: 2; }
    .matriz tbody th { position: sticky; left: 0; background: var(--color-card-bg); text-align: left; font-weight: 600; z-index: 1; }
    .matriz thead th:first-child { left: 0; z-index: 3; }
    .matriz th.destacada { background: var(--accent-light); color: var(--accent-dark); }
    body.dark-mode .matriz th.destacada { background: #24254a; color: #c7d2fe; }
    .matriz input { width: 84px; text-align: right; font-variant-numeric: tabular-nums; }
    .matriz input.sugerida { background: #fff7e0; border-color: #f0c36d; }
    .matriz input.alerta { border-color: #dc2626; box-shadow: 0 0 0 2px rgba(220, 38, 38, .15); }
    body.dark-mode .matriz input.sugerida { background: #3a2e12; border-color: #8a6a1f; }
    .matriz td.vacia { background: repeating-linear-gradient(45deg, transparent, transparent 4px, var(--color-border) 4px, var(--color-border) 5px); }
    .matriz td.es-consecutivo { background: var(--accent-light); color: var(--color-text-muted); font-size: .72rem; text-align: center; }
    body.dark-mode .matriz td.es-consecutivo { background: #1b1f3d; }
</style>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Rutas y tarifas</h3>
            <div class="text-muted small">Armá el recorrido y ponele precio a cada tramo. Todo lo demás se calcula solo.</div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-3">
                <!-- Rutas -->
                <div class="col-xl-3">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Rutas</h3></div>
                        <?php if ($esAdmin): ?>
                            <div class="card-body p-2 pb-0">
                                <form action="<?php echo URLROOT; ?>/admin/guardar_ruta" method="POST" class="d-grid gap-2">
                                    <div class="small fw-semibold">Nueva ruta</div>
                                    <input class="form-control form-control-sm" name="origen" placeholder="Origen (p. ej. EL ALTO)" aria-label="Origen" required>
                                    <input class="form-control form-control-sm" name="destino" placeholder="Destino final" aria-label="Destino final" required>
                                    <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-plus-lg me-1"></i>Crear ruta</button>
                                </form>
                            </div>
                            <hr class="my-2 mx-2">
                        <?php endif; ?>
                        <div class="card-body p-2 pt-0" style="max-height: 480px; overflow-y: auto;">
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
                    </div>
                </div>

                <?php if (!$ruta): ?>
                    <div class="col-xl-9"><div class="alert alert-info">No hay rutas registradas.</div></div>
                <?php else: ?>
                    <div class="col-xl-9">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo $e($ruta->origen); ?> → <?php echo $e($ruta->destino); ?></h3>
                                <?php if ($esAdmin): ?>
                                    <div class="card-tools">
                                        <a class="btn btn-sm btn-light" href="<?php echo URLROOT; ?>/admin/cambiar_estado_ruta/<?php echo (int) $ruta->id; ?>"
                                            title="<?php echo $ruta->estado ? 'Deja de aparecer para programar viajes o encomiendas nuevas. No borra nada: las paradas, tarifas y viajes ya creados siguen intactos, y se puede reactivar cuando quiera.' : 'Vuelve a aparecer disponible para programar viajes y encomiendas nuevas.'; ?>">
                                            <?php echo $ruta->estado ? 'Desactivar' : 'Activar'; ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div class="mapa-ruta-wrap mb-4">
                                    <div id="mapaRuta" class="mapa-ruta"></div>
                                    <div class="small text-muted mt-1" id="mapaRutaAviso"></div>
                                </div>

                                <div class="row g-4">
                                    <!-- Paso 1: paradas -->
                                    <div class="col-lg-6">
                                        <div class="paso-titulo"><span class="paso-numero">1</span> Paradas del recorrido</div>

                                        <div class="pv-lista" id="listaParadas">
                                            <div class="pv-nodo pv-fijo" data-id="0" data-rol="origen">
                                                <span class="pv-gutter" aria-hidden="true"></span>
                                                <span class="pv-punto pv-punto-origen" aria-hidden="true"><i class="bi bi-flag-fill"></i></span>
                                                <div class="pv-info"><span class="pv-badge">Origen</span><span class="pv-nombre-fijo"><?php echo $e($ruta->origen); ?></span></div>
                                            </div>

                                            <?php foreach ($paradas as $i => $p): ?>
                                                <div class="pv-nodo pv-parada" data-id="<?php echo (int) $p['id']; ?>">
                                                    <?php if ($esAdmin): ?>
                                                        <span class="pv-gutter pv-handle" title="Arrastrar para reordenar"><i class="bi bi-grip-vertical"></i></span>
                                                    <?php else: ?>
                                                        <span class="pv-gutter" aria-hidden="true"></span>
                                                    <?php endif; ?>
                                                    <span class="pv-punto"><?php echo $i + 1; ?></span>
                                                    <div class="pv-card">
                                                        <input class="pv-nombre js-nombre" value="<?php echo $e($p['nombre']); ?>" placeholder="Nombre de la parada" aria-label="Nombre de la parada" <?php echo $esAdmin ? '' : 'disabled'; ?>>
                                                        <div class="pv-encomienda">
                                                            <span>Enc.</span>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">Bs</span>
                                                                <input type="number" min="0" step="0.50" class="form-control js-encomienda" value="<?php echo $e(number_format($data['precio_encomienda'][$p['id']] ?? 0, 2, '.', '')); ?>" aria-label="Tarifa base de encomienda" <?php echo $esAdmin ? '' : 'disabled'; ?>>
                                                            </div>
                                                        </div>
                                                        <?php if ($esAdmin): ?>
                                                            <button type="button" class="pv-quitar js-quitar" title="Quitar parada"><i class="bi bi-x-lg"></i></button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>

                                            <div class="pv-nodo pv-fijo" data-id="0" data-rol="destino">
                                                <span class="pv-gutter" aria-hidden="true"></span>
                                                <span class="pv-punto pv-punto-destino" aria-hidden="true"><i class="bi bi-flag-fill"></i></span>
                                                <div class="pv-info"><span class="pv-badge">Destino final</span><span class="pv-nombre-fijo"><?php echo $e($ruta->destino); ?></span></div>
                                            </div>
                                        </div>

                                        <?php if ($esAdmin): ?>
                                            <button type="button" class="btn btn-light mt-3" id="btnAgregarParada"><i class="bi bi-plus-lg me-1"></i>Agregar parada</button>
                                            <div class="small text-muted mt-2">Arrastrá <i class="bi bi-arrows-move"></i> para cambiar el orden. Una parada con boletos vendidos no se borra: se desactiva.</div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Paso 2: precio por tramo -->
                                    <div class="col-lg-6">
                                        <div class="paso-titulo"><span class="paso-numero">2</span> Precio por tramo</div>
                                        <div class="tr-lista" id="listaTramos"><!-- generado por JS a partir de la columna 1 --></div>
                                        <div class="small mt-2" id="avisosTarifas" aria-live="polite"></div>

                                        <?php if ($esAdmin): ?>
                                            <button type="button" class="btn btn-sm btn-link ps-0 mt-2" data-bs-toggle="collapse" data-bs-target="#avanzadoTarifas">
                                                Ajustar precios entre paradas no consecutivas (avanzado)
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($esAdmin): ?>
                                    <div class="d-flex mt-4 pt-3 border-top">
                                        <button type="button" class="btn btn-primary btn-lg ms-auto" id="btnGuardarTodo"><i class="bi bi-check2 me-1"></i>Guardar cambios</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Matriz avanzada -->
                        <div class="collapse mt-3" id="avanzadoTarifas">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">Todos los tramos (Bs)</h3></div>
                                <div class="card-body">
                                    <div class="small text-muted mb-2">Filas: <strong>sube en</strong> · columnas: <strong>baja en</strong>. Los resaltados en amarillo se calcularon solos a partir del paso 2; el celeste son los tramos consecutivos (se editan en el paso 2).</div>
                                    <div class="table-responsive" style="max-height: 420px;">
                                        <table class="matriz" id="matrizTarifas">
                                            <thead>
                                                <tr>
                                                    <th scope="col"></th>
                                                    <?php foreach ($bajadas as $i => $b): ?>
                                                        <th scope="col" data-col="<?php echo $i; ?>" data-punto-id="<?php echo $idPunto($b); ?>"><?php echo $e($b['nombre']); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($subidas as $si => $s): ?>
                                                    <tr>
                                                        <th scope="row" data-row="<?php echo $si; ?>" data-punto-id="<?php echo $idPunto($s); ?>"><?php echo $e($s['nombre']); ?></th>
                                                        <?php foreach ($bajadas as $bi => $b): ?>
                                                            <?php
                                                            $desde = $idPunto($s);
                                                            $hasta = $idPunto($b);
                                                            $valida = $b['orden'] > $s['orden'];
                                                            $dist = $b['orden'] - $s['orden'];
                                                            $t = $tarifas[$desde . '-' . $hasta] ?? null;
                                                            ?>
                                                            <?php if (!$valida): ?>
                                                                <td class="vacia" aria-hidden="true"></td>
                                                            <?php elseif ($dist === 1): ?>
                                                                <td class="es-consecutivo" data-dist="1">se edita en el paso 2 ↑</td>
                                                            <?php else: ?>
                                                                <td data-row="<?php echo $si; ?>" data-col="<?php echo $bi; ?>" data-dist="<?php echo $dist; ?>">
                                                                    <input type="number" min="0.5" step="0.50" class="form-control form-control-sm js-tarifa <?php echo ($t && $t['sugerido']) ? 'sugerida' : ''; ?>"
                                                                        data-desde="<?php echo $desde; ?>" data-hasta="<?php echo $hasta; ?>" data-dist="<?php echo $dist; ?>"
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
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php if ($ruta): ?>
<!-- SortableJS: arrastrar y soltar paradas (columna 1) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js" integrity="sha512-pFqZtL0X6qq5+jSVXjhkOs2xnigrIKKxJ9pquUn+f7UML8DmOJ+3lB2AKX8B8pJUOsdxbC3ipiBnuF9Wm+dSaQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<!-- Leaflet: mapa real del recorrido (OpenStreetMap, sin API key) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script>
    window.TARIFAS = <?php
        $tarifasJs = [];
        foreach ($tarifas as $clave => $t) {
            $tarifasJs[$clave] = ['precio' => $t['precio'], 'sugerido' => (bool) $t['sugerido']];
        }
        echo json_encode($tarifasJs, JSON_UNESCAPED_UNICODE);
    ?>;
</script>
<script>
    (function() {
        const RUTA_ID = <?php echo (int) $ruta->id; ?>;
        const CSRF = '<?php echo $e(csrf_token()); ?>';
        const listaParadas = document.getElementById('listaParadas');
        const listaTramos = document.getElementById('listaTramos');
        const tablaAvanzada = document.getElementById('matrizTarifas');

        function nodosPuntos() {
            return [...listaParadas.querySelectorAll('.pv-nodo')];
        }

        function nombreDe(nodo) {
            if (nodo.classList.contains('pv-fijo')) return nodo.querySelector('.pv-nombre-fijo').textContent;
            return nodo.querySelector('.js-nombre').value || '(sin nombre)';
        }

        function numerar() {
            let n = 1;
            listaParadas.querySelectorAll('.pv-parada').forEach(nodo => { nodo.querySelector('.pv-punto').textContent = n++; });
        }

        // ---------- Mapa del recorrido (Leaflet + OpenStreetMap, sin API key) ----------
        // Las coordenadas no se guardan en la base de datos (esto es solo para
        // mostrar el mapa, el orden real de la ruta lo sigue definiendo la
        // columna 1): pueblos conocidos de la zona van de una, cualquier otro
        // nombre se busca una sola vez en Nominatim (geocodificador gratuito de
        // OpenStreetMap) y queda en cache mientras dure la pagina.
        const COORDS_CONOCIDAS = {
            'EL ALTO': [-16.5000, -68.1500], 'LA PAZ': [-16.5000, -68.1193],
            'BATALLAS': [-16.2833, -68.4833], 'HUARINA': [-16.2167, -68.6333],
            'ACHACACHI': [-16.0500, -68.6833], 'ANCORAIMES': [-15.8833, -68.7500],
            'CARABUCO': [-15.7333, -68.8667], 'COPACABANA': [-16.1667, -69.0833],
            'LAJA': [-16.5333, -68.3167], 'ESCOMA': [-15.6667, -68.9500],
            'PUERTO ACOSTA': [-15.5167, -69.0667],
        };
        const cacheCoords = {};
        let mapa = null, capaMarcadores = null, lineaRuta = null, tokenMapa = 0;

        async function coordenadasDe(nombre) {
            const clave = (nombre || '').trim().toUpperCase();
            if (!clave) return null;
            if (clave in cacheCoords) return cacheCoords[clave];
            if (COORDS_CONOCIDAS[clave]) { cacheCoords[clave] = COORDS_CONOCIDAS[clave]; return cacheCoords[clave]; }
            try {
                const resp = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=bo&q=' + encodeURIComponent(nombre + ', Bolivia'));
                const datos = await resp.json();
                cacheCoords[clave] = (datos && datos[0]) ? [parseFloat(datos[0].lat), parseFloat(datos[0].lon)] : null;
            } catch (e) {
                cacheCoords[clave] = null;
            }
            return cacheCoords[clave];
        }

        function iniciarMapa() {
            if (mapa || !window.L) return;
            mapa = L.map('mapaRuta', { scrollWheelZoom: false }).setView([-16.3, -68.5], 8);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; colaboradores de OpenStreetMap',
                maxZoom: 18,
            }).addTo(mapa);
            capaMarcadores = L.layerGroup().addTo(mapa);
        }

        function iconoParada(texto, claseExtra) {
            return L.divIcon({
                className: '',
                html: `<div class="marcador-parada ${claseExtra}"><span>${texto}</span></div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 28],
            });
        }

        async function actualizarMapa() {
            if (!window.L) return;
            iniciarMapa();
            const miToken = ++tokenMapa;

            const puntos = [];
            const noEncontrados = [];
            let n = 1;
            for (const nodo of nodosPuntos()) {
                const nombre = nombreDe(nodo);
                const coords = await coordenadasDe(nombre);
                if (miToken !== tokenMapa) return; // se pidio otra actualizacion mas nueva mientras esta esperaba la red
                const esOrigen = nodo.dataset.rol === 'origen';
                const esDestino = nodo.dataset.rol === 'destino';
                if (coords) {
                    const etiqueta = esOrigen ? 'A' : esDestino ? 'B' : String(n);
                    const clase = esOrigen ? 'marcador-origen' : esDestino ? 'marcador-destino' : '';
                    puntos.push({ coords, nombre, icono: iconoParada(etiqueta, clase) });
                } else {
                    noEncontrados.push(nombre);
                }
                if (!esOrigen && !esDestino) n++;
            }

            capaMarcadores.clearLayers();
            if (lineaRuta) { mapa.removeLayer(lineaRuta); lineaRuta = null; }

            if (puntos.length) {
                puntos.forEach(p => L.marker(p.coords, { icon: p.icono, draggable: true, title: p.nombre }).addTo(capaMarcadores));
                if (puntos.length > 1) {
                    lineaRuta = L.polyline(puntos.map(p => p.coords), { color: '#6366f1', weight: 4, opacity: .7 }).addTo(mapa);
                    mapa.fitBounds(lineaRuta.getBounds(), { padding: [30, 30] });
                } else {
                    mapa.setView(puntos[0].coords, 12);
                }
            }

            document.getElementById('mapaRutaAviso').textContent = noEncontrados.length
                ? 'No se pudo ubicar en el mapa: ' + noEncontrados.join(', ') + '.'
                : '';
        }

        let debounceMapa;
        function actualizarMapaDespacio() {
            clearTimeout(debounceMapa);
            debounceMapa = setTimeout(actualizarMapa, 700);
        }

        // ---------- Columna 2: se reconstruye siempre a partir del orden actual de la columna 1 ----------
        function reconstruirTramos() {
            // Conserva el valor ya escrito de cada tramo que sigue existiendo
            // (mismo par desde-hasta); los tramos nuevos (por un reordenamiento
            // o una parada agregada) quedan vacios para completar.
            const previos = {};
            listaTramos.querySelectorAll('.js-tarifa').forEach(inp => {
                previos[inp.dataset.desde + '-' + inp.dataset.hasta] = { valor: inp.value, sugerida: inp.classList.contains('sugerida') };
            });

            const nodos = nodosPuntos();
            listaTramos.innerHTML = '';
            for (let i = 0; i < nodos.length - 1; i++) {
                const a = nodos[i], b = nodos[i + 1];
                const idA = a.dataset.id || '';
                const idB = b.dataset.id || '';
                const clave = idA + '-' + idB;
                const guardado = previos[clave] || (window.TARIFAS[clave] ? { valor: window.TARIFAS[clave].precio, sugerida: window.TARIFAS[clave].sugerido } : null);

                const fila = document.createElement('div');
                fila.className = 'tr-fila';
                fila.innerHTML = `<div class="tr-nombres">${escapeHtml(nombreDe(a))}<span class="flecha">→</span>${escapeHtml(nombreDe(b))}</div>
                    <div class="input-group input-group-sm tr-precio">
                        <span class="input-group-text">Bs</span>
                        <input type="number" min="0.5" step="0.50" class="form-control js-tarifa" data-desde="${idA}" data-hasta="${idB}" data-dist="1" value="${guardado ? guardado.valor : ''}">
                    </div>`;
                const input = fila.querySelector('.js-tarifa');
                if (guardado && guardado.sugerida) input.classList.add('sugerida');
                input.addEventListener('input', () => { input.classList.remove('sugerida'); recomputarMatrizAvanzada(); revisar(); });
                listaTramos.appendChild(fila);
            }
            recomputarMatrizAvanzada();
            revisar();
        }

        if (window.Sortable && listaParadas) {
            Sortable.create(listaParadas, {
                draggable: '.pv-parada',
                handle: '.pv-handle',
                animation: 180,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: () => { numerar(); reconstruirTramos(); actualizarMapa(); },
            });
        }

        function nodoParadaNuevo() {
            const div = document.createElement('div');
            div.className = 'pv-nodo pv-parada';
            div.dataset.id = '';
            div.innerHTML = `<span class="pv-gutter pv-handle" title="Arrastrar para reordenar"><i class="bi bi-grip-vertical"></i></span>
                <span class="pv-punto"></span>
                <div class="pv-card">
                    <input class="pv-nombre js-nombre" placeholder="Nombre de la parada" aria-label="Nombre de la parada">
                    <div class="pv-encomienda">
                        <span>Enc.</span>
                        <div class="input-group input-group-sm"><span class="input-group-text">Bs</span><input type="number" min="0" step="0.50" class="form-control js-encomienda" value="0.00" aria-label="Tarifa base de encomienda"></div>
                    </div>
                    <button type="button" class="pv-quitar js-quitar" title="Quitar parada"><i class="bi bi-x-lg"></i></button>
                </div>`;
            return div;
        }

        const btnAgregar = document.getElementById('btnAgregarParada');
        if (btnAgregar) btnAgregar.addEventListener('click', () => {
            const destinoFijo = listaParadas.querySelector('.pv-fijo[data-rol="destino"]');
            const nodo = nodoParadaNuevo();
            listaParadas.insertBefore(nodo, destinoFijo);
            numerar();
            reconstruirTramos();
            actualizarMapa();
            nodo.querySelector('.js-nombre').focus();
        });

        listaParadas.addEventListener('click', ev => {
            const btn = ev.target.closest('.js-quitar');
            if (!btn) return;
            btn.closest('.pv-parada').remove();
            numerar();
            reconstruirTramos();
            actualizarMapa();
        });

        listaParadas.addEventListener('input', ev => {
            if (ev.target.classList.contains('js-nombre')) {
                reconstruirTramos();
                actualizarMapaDespacio();
            }
        });

        // ---------- Autocompletar la matriz avanzada (tramos no consecutivos) ----------
        function recomputarMatrizAvanzada() {
            if (!tablaAvanzada) return;
            const nodos = nodosPuntos();
            const puntos = nodos.map(n => n.dataset.id || '');
            const chips = [...listaTramos.querySelectorAll('.js-tarifa')].map(inp => inp.value !== '' ? parseFloat(inp.value) : NaN);

            document.querySelectorAll('#matrizTarifas tbody tr').forEach(tr => {
                const idDesde = tr.querySelector('th[data-row]').dataset.puntoId;
                const posDesde = puntos.indexOf(idDesde);
                if (posDesde === -1) return;
                tr.querySelectorAll('td[data-dist]').forEach(td => {
                    const input = td.querySelector('input');
                    if (!input) return; // dist=1: se edita en el paso 2
                    const idHasta = tablaAvanzada.querySelector(`thead th[data-col="${td.dataset.col}"]`)?.dataset.puntoId;
                    let posHasta = -1;
                    for (let k = posDesde + 1; k < puntos.length; k++) { if (puntos[k] === idHasta) { posHasta = k; break; } }
                    if (posHasta === -1) return;
                    const suma = chips.slice(posDesde, posHasta).reduce((a, b) => a + b, 0);
                    if (input.classList.contains('sugerida') || input.value === '') {
                        input.value = Number.isNaN(suma) ? '' : suma.toFixed(2);
                        input.classList.add('sugerida');
                    }
                });
            });
        }

        if (tablaAvanzada) {
            tablaAvanzada.addEventListener('mouseover', ev => {
                const celda = ev.target.closest('td[data-row]');
                if (celda) destacar(celda.dataset.row, celda.dataset.col, true);
            });
            tablaAvanzada.addEventListener('mouseout', ev => {
                const celda = ev.target.closest('td[data-row]');
                if (celda) destacar(celda.dataset.row, celda.dataset.col, false);
            });
        }
        function destacar(row, col, on) {
            const th1 = tablaAvanzada.querySelector(`tbody th[data-row="${row}"]`);
            const th2 = tablaAvanzada.querySelector(`thead th[data-col="${col}"]`);
            if (th1) th1.classList.toggle('destacada', on);
            if (th2) th2.classList.toggle('destacada', on);
        }

        function revisar() {
            let cantidad = 0;
            document.querySelectorAll('#matrizTarifas tbody tr').forEach(tr => {
                const celdas = [...tr.querySelectorAll('td[data-dist]')]
                    .filter(td => td.dataset.dist !== '1')
                    .sort((a, b) => parseInt(a.dataset.dist) - parseInt(b.dataset.dist));
                celdas.forEach((td, i) => {
                    const input = td.querySelector('input');
                    const valor = parseFloat(input.value || '0');
                    const masLejos = celdas.slice(i + 1).map(c => parseFloat(c.querySelector('input').value || '0')).filter(v => v > 0);
                    const alerta = valor > 0 && masLejos.some(v => v < valor);
                    input.classList.toggle('alerta', alerta);
                    if (alerta) cantidad++;
                });
            });
            document.getElementById('avisosTarifas').innerHTML = cantidad
                ? `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> ${cantidad} tramo(s) avanzados cuestan más que llegar más lejos.</span>`
                : '';
        }

        function escapeHtml(t) {
            const d = document.createElement('div');
            d.textContent = t;
            return d.innerHTML;
        }

        // ---------- Guardar ----------
        function enviar(accion, datos, reload) {
            return fetch('<?php echo URLROOT; ?>/admin/' + accion, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(Object.assign({ csrf_token: CSRF, ruta_id: RUTA_ID }, datos))
            })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        if (!reload) return true;
                        return Swal.fire({ icon: 'success', title: res.message, timer: 1400, showConfirmButton: false }).then(() => { location.reload(); return true; });
                    }
                    Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: res.message });
                    return false;
                })
                .catch(() => { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo contactar al servidor.' }); return false; });
        }

        function datosParadas() {
            return [...listaParadas.querySelectorAll('.pv-parada')].map(f => ({
                id: parseInt(f.dataset.id || '0', 10),
                nombre: f.querySelector('.js-nombre').value,
                precio_encomienda: f.querySelector('.js-encomienda').value
            }));
        }

        const btnGuardarTodo = document.getElementById('btnGuardarTodo');
        if (btnGuardarTodo) btnGuardarTodo.addEventListener('click', async () => {
            const hayParadaNueva = [...listaParadas.querySelectorAll('.pv-parada')].some(n => !n.dataset.id);

            if (hayParadaNueva) {
                btnGuardarTodo.disabled = true;
                await enviar('guardar_paradas', { paradas: datosParadas() }, true);
                btnGuardarTodo.disabled = false;
                return;
            }

            const inputsTarifa = [...document.querySelectorAll('.js-tarifa')];
            const vacias = inputsTarifa.filter(i => !(parseFloat(i.value) > 0));
            if (vacias.length) {
                vacias[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                vacias[0].focus();
                Swal.fire({ icon: 'warning', title: 'Faltan precios', text: `Complete los ${vacias.length} tramo(s) vacíos o en 0.` });
                return;
            }

            btnGuardarTodo.disabled = true;
            const okParadas = await enviar('guardar_paradas', { paradas: datosParadas() }, false);
            if (okParadas) {
                await enviar('guardar_tarifas', { tarifas: inputsTarifa.map(i => ({ desde: i.dataset.desde, hasta: i.dataset.hasta, precio: i.value })) }, true);
            }
            btnGuardarTodo.disabled = false;
        });

        // ---------- Inicio ----------
        numerar();
        reconstruirTramos();
        actualizarMapa();
    })();
</script>
<?php endif; ?>

@endsection
