@extends('layouts.app')

@section('content')
<?php
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$bs = fn($n) => 'Bs ' . number_format((float) $n, 2, ',', '.');
$ventas = $data['ventas'];
$ocup = $data['ocupacion'];
$porcOcupacion = $ocup->asientos_totales > 0 ? round($ocup->asientos_ocupados / $ocup->asientos_totales * 100) : null;
$promedio = $ventas->boletos > 0 ? $ventas->ingresos / $ventas->boletos : 0;
$porcQr = $ventas->ingresos > 0 ? $ventas->ingresos_qr / $ventas->ingresos * 100 : 0;
$enc = $data['encomiendas'];
$maxGrupo = max(array_map(fn($g) => (float) $g->ingresos, $data['por_grupo']->all()) ?: [0]);
$maxRuta = max(array_map(fn($r) => (int) $r->boletos, $data['rutas']->all()) ?: [0]);
$etiquetaPeriodo = $data['periodos'][$data['periodo']][0];
$rangoTexto = $data['desde'] === $data['hasta'] ? date('d/m/Y', strtotime($data['desde'])) : date('d/m/Y', strtotime($data['desde'])) . ' – ' . date('d/m/Y', strtotime($data['hasta']));
$qs = function ($cambios) use ($data) {
    $p = ['periodo' => $data['periodo']];
    if ($data['es_global'] && $data['sucursal_id']) {
        $p['sucursal'] = $data['sucursal_id'];
    }
    return '?' . http_build_query(array_merge($p, $cambios));
};
?>
<style>
    .dash-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; }
    .dash-head h3 { margin: 0; font-weight: 700; }
    .dash-sub { color: var(--color-text-muted); font-size: .9rem; }
    .dash-filtros { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .seg { display: inline-flex; background: var(--color-card-bg); border: 1px solid var(--color-border); border-radius: 10px; padding: 3px; }
    .seg a { padding: .35rem .7rem; border-radius: 8px; font-size: .84rem; font-weight: 600; color: var(--color-text-muted); text-decoration: none; white-space: nowrap; }
    .seg a:hover { color: var(--color-text); }
    .seg a.activo { background: var(--accent); color: #fff; }

    /* contenedor: el perspective() es lo que da profundidad al giro 3D */
    .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; perspective: 1400px; }
    
    /* tarjeta base */
    .kpi { position: relative; color: #fff; padding: 1.25rem 1.15rem; border: 0; border-radius: 22px; display: flex; flex-direction: column; gap: .35rem; transform-style: preserve-3d; will-change: transform; transition: transform .35s cubic-bezier(.2,.8,.2,1), box-shadow .35s ease; overflow: hidden; cursor: default; }
    
    /* el brillo: un gradiente radial que sigue al mouse via --mx/--my */
    .kpi::before { content: ""; position: absolute; inset: 0; background: radial-gradient(160px 160px at var(--mx,50%) var(--my,0%), #ffffff3d, transparent 70%); opacity: 0; transition: opacity .35s ease; pointer-events: none; }
    
    /* fallback sin JS */
    .kpi:hover { transform: translateY(-8px); }
    .kpi:hover::before { opacity: 1; }
    
    /* el icono flota */
    .kpi .icon { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 12px; background: linear-gradient(155deg, #ffffff4d, #ffffff14); border: 1px solid #ffffff40; box-shadow: inset 0 1px 1px #ffffff80, 0 10px 18px #0000001f; transition: transform .4s cubic-bezier(.2,.8,.2,1), box-shadow .4s ease; color: #fff; font-size: 1.1rem; }
    .kpi:hover .icon { transform: scale(1.14) rotate(-6deg); box-shadow: inset 0 1px 1px #ffffffb0, 0 16px 26px #00000030; }
    
    /* los 4 degradados */
    .kpi-3d--blue   { background: linear-gradient(150deg, #2f6fed 0%, #7c3aed 100%); box-shadow: 0 10px 26px #4338ca40; }
    .kpi-3d--purple { background: linear-gradient(150deg, #c026d3 0%, #ec4899 100%); box-shadow: 0 10px 26px #c026d340; }
    .kpi-3d--orange { background: linear-gradient(150deg, #f59e0b 0%, #f43f5e 100%); box-shadow: 0 10px 26px #f59e0b40; }
    .kpi-3d--green  { background: linear-gradient(150deg, #10b981 0%, #0891b2 100%); box-shadow: 0 10px 26px #10b98140; }

    .kpi-label { font-size: .74rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #ffffffcc; display: flex; align-items: center; gap: .6rem; }
    .kpi-valor { font-size: 1.75rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; color: #fff; }
    .kpi-nota { font-size: .82rem; color: #ffffffcc; }
    .delta { font-weight: 700; }
    .delta.sube { color: #a7f3d0; }
    .delta.baja { color: #fca5a5; }
    .barra-split { display: flex; height: 6px; border-radius: 999px; overflow: hidden; background: var(--color-border); margin-top: .2rem; }
    .barra-split span:first-child { background: var(--accent); }
    .barra-split span:last-child { background: var(--accent-emerald); }
    .leyenda { display: flex; gap: .9rem; font-size: .78rem; color: var(--color-text-muted); }
    .leyenda i { display: inline-block; width: 9px; height: 9px; border-radius: 2px; margin-right: 4px; vertical-align: -1px; }

    .panel { background: var(--color-card-bg); border: 1px solid var(--color-border); border-radius: var(--radius-card); height: 100%; display: flex; flex-direction: column; }
    .panel-h { display: flex; justify-content: space-between; align-items: center; gap: .5rem; padding: .85rem 1.15rem; border-bottom: 1px solid var(--color-border); }
    .panel-h h5 { margin: 0; font-size: .98rem; font-weight: 700; }
    .panel-b { padding: 1rem 1.15rem; flex: 1; }
    .vacio { color: var(--color-text-muted); font-size: .9rem; text-align: center; padding: 1.5rem .5rem; }

    .fila-barra { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .15rem .75rem; margin-bottom: .75rem; font-size: .88rem; }
    .fila-barra .nombre { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fila-barra .valor { font-variant-numeric: tabular-nums; font-weight: 600; text-align: right; }
    .fila-barra .pista { grid-column: 1 / -1; height: 6px; background: var(--color-border); border-radius: 999px; overflow: hidden; }
    .fila-barra .pista span { display: block; height: 100%; background: var(--accent); border-radius: 999px; }

    .tabla-viajes td, .tabla-viajes th { vertical-align: middle; }
    .ocup { min-width: 120px; }
    .ocup .pista { height: 6px; background: var(--color-border); border-radius: 999px; overflow: hidden; display: flex; }
    .ocup .pista .v { background: var(--accent); }
    .ocup .pista .r { background: #f59e0b; }
    .ocup small { font-variant-numeric: tabular-nums; color: var(--color-text-muted); }

    .pend { display: flex; gap: .75rem; align-items: flex-start; padding: .65rem 0; border-bottom: 1px solid var(--color-border); }
    .pend:last-child { border-bottom: 0; }
    .pend .num { min-width: 2.2rem; height: 2.2rem; border-radius: 10px; display: grid; place-items: center; font-weight: 800; font-variant-numeric: tabular-nums; }
    .pend.critico .num { background: #fde8e8; color: #b91c1c; }
    .pend.alto .num { background: #fef3c7; color: #b45309; }
    .pend.medio .num { background: var(--accent-light); color: var(--accent-dark); }
    body.dark-mode .pend.critico .num { background: #3b1717; color: #fca5a5; }
    body.dark-mode .pend.alto .num { background: #3a2a0f; color: #fcd34d; }
    body.dark-mode .pend.medio .num { background: #24254a; color: #c7d2fe; }
    .pend .texto { font-size: .88rem; }
    .pend a { font-size: .82rem; font-weight: 600; }

    .caja-item { display: flex; justify-content: space-between; gap: .75rem; padding: .6rem 0; border-bottom: 1px solid var(--color-border); font-size: .88rem; }
    .caja-item:last-child { border-bottom: 0; }
    .caja-item .monto { font-weight: 700; font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
</style>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="dash-head">
                <div>
                    <h3>Panel de control</h3>
                    <div class="dash-sub">
                        <?php echo $data['sucursal_nombre'] ? 'Sucursal ' . $e($data['sucursal_nombre']) : 'Todas las sucursales'; ?>
                        · <?php echo $e($etiquetaPeriodo); ?> (<?php echo $rangoTexto; ?>)
                    </div>
                </div>
                <div class="dash-filtros">
                    <?php if ($data['es_global']): ?>
                        <form method="get" class="d-flex gap-2 align-items-center">
                            <input type="hidden" name="periodo" value="<?php echo $e($data['periodo']); ?>">
                            <label for="filtroSucursal" class="visually-hidden">Sucursal</label>
                            <select class="form-select form-select-sm" id="filtroSucursal" name="sucursal" onchange="this.form.submit()">
                                <option value="">Todas las sucursales</option>
                                <?php foreach ($data['sucursales'] as $s): ?>
                                    <option value="<?php echo (int) $s->id; ?>" <?php echo (int) $data['sucursal_id'] === (int) $s->id ? 'selected' : ''; ?>><?php echo $e($s->nombre_sede); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php endif; ?>
                    <nav class="seg" aria-label="Periodo">
                        <?php foreach ($data['periodos'] as $clave => [$etiqueta]): ?>
                            <a href="<?php echo $e($qs(['periodo' => $clave])); ?>" class="<?php echo $clave === $data['periodo'] ? 'activo' : ''; ?>" <?php echo $clave === $data['periodo'] ? 'aria-current="true"' : ''; ?>><?php echo $e($etiqueta); ?></a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid d-grid gap-3">

            <?php if ($data['sin_sucursal']): ?>
                <div class="alert alert-warning mb-0">Su usuario no tiene sucursal asignada: el tablero no puede mostrar datos. Pida al administrador que se la asigne.</div>
            <?php endif; ?>

            <?php if (!$ventas->boletos && $data['ultima_venta']): ?>
                <div class="alert alert-info mb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>No hay ventas en este periodo. La última venta registrada fue el <strong><?php echo date('d/m/Y', strtotime($data['ultima_venta'])); ?></strong>.</span>
                    <?php if ($data['periodo'] !== '12m'): ?><a class="btn btn-sm btn-light" href="<?php echo $e($qs(['periodo' => '12m'])); ?>">Ver últimos 12 meses</a><?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Indicadores -->
            <section class="kpis kpis-3d" aria-label="Indicadores del periodo">
                <div class="kpi kpi-3d kpi-3d--blue">
                    <div class="kpi-label"><span class="icon"><i class="bi bi-cash-coin"></i></span> Ingresos por pasajes</div>
                    <div class="kpi-valor"><?php echo $bs($ventas->ingresos); ?></div>
                    <div class="kpi-nota">
                        <?php if ($data['variacion'] === null): ?>
                            Sin ventas en el periodo anterior
                        <?php else: ?>
                            <span class="delta <?php echo $data['variacion'] >= 0 ? 'sube' : 'baja'; ?>"><?php echo ($data['variacion'] >= 0 ? '▲ ' : '▼ ') . number_format(abs($data['variacion']), 1, ',', '.'); ?>%</span> vs periodo anterior
                        <?php endif; ?>
                    </div>
                    <div class="barra-split" role="img" aria-label="Efectivo <?php echo round(100 - $porcQr); ?>%, QR <?php echo round($porcQr); ?>%">
                        <span style="width: <?php echo $ventas->ingresos > 0 ? 100 - $porcQr : 0; ?>%"></span><span style="width: <?php echo $porcQr; ?>%"></span>
                    </div>
                    <div class="leyenda"><span><i style="background: var(--accent)"></i>Efectivo <?php echo $bs($ventas->ingresos_efectivo); ?></span><span><i style="background: var(--accent-emerald)"></i>QR <?php echo $bs($ventas->ingresos_qr); ?></span></div>
                </div>

                <div class="kpi kpi-3d kpi-3d--purple">
                    <div class="kpi-label"><span class="icon"><i class="bi bi-ticket-perforated"></i></span> Boletos vendidos</div>
                    <div class="kpi-valor"><?php echo number_format($ventas->boletos, 0, ',', '.'); ?></div>
                    <div class="kpi-nota text-white-50">Precio promedio <?php echo $bs($promedio); ?></div>
                </div>

                <div class="kpi kpi-3d kpi-3d--orange">
                    <div class="kpi-label"><span class="icon"><i class="bi bi-people"></i></span> Ocupación de buses</div>
                    <div class="kpi-valor"><?php echo $porcOcupacion === null ? '—' : $porcOcupacion . '%'; ?></div>
                    <div class="kpi-nota text-white-50">
                        <?php echo (int) $ocup->viajes; ?> viaje(s) con salida en el periodo<?php if ($ocup->asientos_totales): ?> · <?php echo (int) $ocup->asientos_ocupados; ?> de <?php echo (int) $ocup->asientos_totales; ?> asientos<?php endif; ?>
                    </div>
                </div>

                <div class="kpi kpi-3d kpi-3d--green">
                    <div class="kpi-label"><span class="icon"><i class="bi bi-box-seam"></i></span> Encomiendas</div>
                    <div class="kpi-valor"><?php echo (int) $enc->registradas; ?></div>
                    <div class="kpi-nota text-white-50"><?php echo $bs($enc->monto); ?> registradas · <?php echo (int) $enc->por_despachar; ?> por despachar · <?php echo (int) $enc->por_entregar; ?> por entregar</div>
                </div>
            </section>

            <!-- Grafico + distribucion -->
            <div class="row g-3">
                <div class="col-xl-8">
                    <section class="panel">
                        <div class="panel-h">
                            <h5>Ingresos <?php echo $data['periodo'] === '12m' ? 'por mes' : 'por día'; ?></h5>
                            <div class="leyenda"><span><i style="background: var(--accent)"></i>Efectivo</span><span><i style="background: var(--accent-emerald)"></i>QR</span></div>
                        </div>
                        <div class="panel-b">
                            <div style="position: relative; height: 280px;">
                                <canvas id="graficoIngresos" role="img" aria-label="Gráfico de ingresos por <?php echo $data['periodo'] === '12m' ? 'mes' : 'día'; ?>"></canvas>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-xl-4">
                    <section class="panel">
                        <div class="panel-h"><h5><?php echo $data['sucursal_id'] ? 'Ventas por vendedor' : 'Ventas por sucursal'; ?></h5></div>
                        <div class="panel-b">
                            <?php if (!$data['por_grupo']): ?>
                                <div class="vacio">Sin ventas en el periodo.</div>
                            <?php endif; ?>
                            <?php foreach ($data['por_grupo'] as $g): ?>
                                <div class="fila-barra">
                                    <span class="nombre" title="<?php echo $e($g->nombre); ?>"><?php echo $e($g->nombre); ?></span>
                                    <span class="valor"><?php echo $bs($g->ingresos); ?> <small class="text-muted fw-normal">· <?php echo (int) $g->boletos; ?></small></span>
                                    <div class="pista"><span style="width: <?php echo $maxGrupo > 0 ? round($g->ingresos / $maxGrupo * 100) : 0; ?>%"></span></div>
                                </div>
                            <?php endforeach; ?>

                            <div class="panel-h px-0 pt-3 pb-2 border-0"><h5>Tramos más vendidos</h5></div>
                            <?php if (!$data['rutas']): ?>
                                <div class="vacio py-2">Sin ventas en el periodo.</div>
                            <?php endif; ?>
                            <?php foreach ($data['rutas'] as $r): ?>
                                <div class="fila-barra">
                                    <span class="nombre" title="<?php echo $e($r->tramo); ?>"><?php echo $e($r->tramo); ?></span>
                                    <span class="valor"><?php echo (int) $r->boletos; ?> <small class="text-muted fw-normal">boletos</small></span>
                                    <div class="pista"><span style="width: <?php echo $maxRuta > 0 ? round($r->boletos / $maxRuta * 100) : 0; ?>%; background: var(--accent-emerald)"></span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Operacion -->
            <div class="row g-3">
                <div class="col-xl-8">
                    <section class="panel">
                        <div class="panel-h">
                            <h5>Viajes abiertos a la venta</h5>
                            <a class="btn btn-sm btn-light" href="<?php echo URLROOT; ?>/ventas/crear_ruta">Programar viajes</a>
                        </div>
                        <div class="panel-b p-0">
                            <?php if (!$data['viajes']): ?>
                                <div class="vacio">No hay viajes programados<?php echo $data['sucursal_nombre'] ? ' con salida desde ' . $e($data['sucursal_nombre']) : ''; ?>.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 tabla-viajes">
                                        <thead>
                                            <tr><th>Salida</th><th>Ruta</th><th>Bus / chofer</th><th>Ocupación</th><th></th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($data['viajes'] as $v): ?>
                                                <?php
                                                $cap = max(1, (int) $v->capacidad);
                                                $pv = min(100, $v->vendidos / $cap * 100);
                                                $pr = min(100 - $pv, $v->reservados / $cap * 100);
                                                ?>
                                                <tr>
                                                    <td class="text-nowrap">
                                                        <div class="fw-semibold"><?php echo date('d/m/Y', strtotime($v->fecha_salida)); ?></div>
                                                        <div class="small text-muted"><?php echo substr($v->hora_salida ?: date('H:i:s', strtotime($v->fecha_salida)), 0, 5); ?></div>
                                                        <?php if ($v->atrasado): ?><span class="badge bg-danger-subtle text-danger">Fecha pasada</span><?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo $e($v->origen); ?> → <?php echo $e($v->destino); ?></div>
                                                        <div class="small text-muted">Viaje #<?php echo (int) $v->id; ?> · <?php echo $bs($v->precio_base); ?></div>
                                                    </td>
                                                    <td class="small">
                                                        <?php echo $v->placa ? '<span class="badge bg-dark font-monospace">' . $e($v->placa) . '</span>' : '<span class="text-danger">Sin bus</span>'; ?>
                                                        <div class="text-muted"><?php echo $v->chofer ? $e($v->chofer) : 'Sin chofer'; ?></div>
                                                    </td>
                                                    <td class="ocup">
                                                        <div class="pista" role="img" aria-label="<?php echo (int) $v->vendidos; ?> vendidos y <?php echo (int) $v->reservados; ?> reservados de <?php echo (int) $v->capacidad; ?>">
                                                            <span class="v" style="width: <?php echo $pv; ?>%"></span><span class="r" style="width: <?php echo $pr; ?>%"></span>
                                                        </div>
                                                        <small><?php echo (int) $v->vendidos; ?> vendidos<?php if ($v->reservados): ?> · <?php echo (int) $v->reservados; ?> reserv.<?php endif; ?> / <?php echo (int) $v->capacidad; ?></small>
                                                    </td>
                                                    <td class="text-end">
                                                        <a class="btn btn-sm btn-primary" href="<?php echo URLROOT; ?>/ventas/venta_pasajes?viaje_id=<?php echo (int) $v->id; ?>">Vender</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <div class="col-xl-4 d-grid gap-3 align-content-start">
                    <section class="panel">
                        <div class="panel-h"><h5>Requiere atención</h5></div>
                        <div class="panel-b py-1">
                            <?php if (!$data['pendientes']): ?>
                                <div class="vacio"><i class="bi bi-check2-circle text-success"></i> Nada pendiente.</div>
                            <?php endif; ?>
                            <?php foreach ($data['pendientes'] as $p): ?>
                                <div class="pend <?php echo $e($p['nivel']); ?>">
                                    <div class="num"><?php echo (int) $p['cantidad']; ?></div>
                                    <div>
                                        <div class="texto"><?php echo $e($p['texto']); ?></div>
                                        <a href="<?php echo $e($p['url']); ?>"><?php echo $e($p['accion']); ?> →</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="panel">
                        <div class="panel-h">
                            <h5>Cajas abiertas</h5>
                            <a class="btn btn-sm btn-light" href="<?php echo URLROOT; ?>/caja">Mi caja</a>
                        </div>
                        <div class="panel-b py-1">
                            <?php if (!$data['cajas']): ?>
                                <div class="vacio">No hay cajas abiertas.</div>
                            <?php endif; ?>
                            <?php foreach ($data['cajas'] as $c): ?>
                                <div class="caja-item">
                                    <div>
                                        <div class="fw-semibold"><?php echo $e($c->usuario); ?></div>
                                        <div class="small text-muted"><?php echo $e($c->sucursal); ?> · desde <?php echo date('d/m H:i', strtotime($c->fecha_apertura)); ?><?php if ($c->horas >= 14): ?> <span class="text-danger">(<?php echo (int) $c->horas; ?> h)</span><?php endif; ?></div>
                                    </div>
                                    <div class="monto">
                                        <?php echo $bs($c->efectivo_esperado); ?>
                                        <div class="small text-muted fw-normal">efectivo<?php if ($c->cobrado_qr > 0): ?> · QR <?php echo $bs($c->cobrado_qr); ?><?php endif; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    (function() {
        const serie = <?php echo json_encode($data['serie_diaria'], JSON_UNESCAPED_UNICODE); ?>;
        const canvas = document.getElementById('graficoIngresos');
        if (!canvas || typeof Chart === 'undefined') return;

        const css = getComputedStyle(document.body);
        const token = (n, def) => (css.getPropertyValue(n) || def).trim();
        const colorTexto = token('--color-text-muted', '#6b7280');
        const colorGrilla = token('--color-border', '#e5e7eb');
        const fmt = v => 'Bs ' + Number(v).toLocaleString('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: serie.map(d => d.etiqueta),
                datasets: [
                    { label: 'Efectivo', data: serie.map(d => d.efectivo), backgroundColor: token('--accent', '#6366f1'), borderRadius: 4, stack: 'i' },
                    { label: 'QR', data: serie.map(d => d.qr), backgroundColor: token('--accent-emerald', '#10b981'), borderRadius: 4, stack: 'i' },
                ]
            },
            options: {
                maintainAspectRatio: false,
                animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 400 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: ${fmt(ctx.parsed.y)}`,
                            footer: items => {
                                const d = serie[items[0].dataIndex];
                                return `Total ${fmt(d.efectivo + d.qr)} · ${d.boletos} boletos`;
                            }
                        }
                    }
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { color: colorTexto, maxRotation: 0, autoSkipPadding: 12 } },
                    y: { stacked: true, beginAtZero: true, grid: { color: colorGrilla }, ticks: { color: colorTexto, callback: fmt } }
                }
            }
        });
    })();
</script>

<!-- Inclinacion 3D + brillo en las tarjetas de KPI (ver Tarjetas-3D-Degradado) -->
<script>
    (function() {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const hasFinePointer = window.matchMedia('(pointer: fine)').matches;
        if (reduceMotion || !hasFinePointer) return;

        document.querySelectorAll('.kpi-3d').forEach(function(card) {
            const icon = card.querySelector(".icon");

            card.addEventListener('pointermove', function(event) {
                const rect = card.getBoundingClientRect();
                const px = (event.clientX - rect.left) / rect.width - 0.5;
                const py = (event.clientY - rect.top) / rect.height - 0.5;

                card.style.transform =
                    'perspective(900px) rotateX(' + (-py * 9).toFixed(2) + 'deg) rotateY(' + (px * 11).toFixed(2) + 'deg) translateY(-8px)';
                card.style.setProperty('--mx', ((px + 0.5) * 100) + '%');
                card.style.setProperty('--my', ((py + 0.5) * 100) + '%');
                
                if (icon) icon.style.transform = `translate3d(${px * 12}px, ${py * 12}px, 24px) scale(1.14)`;
            });
            card.addEventListener('pointerleave', function() {
                card.style.transform = '';
                if (icon) icon.style.transform = '';
            });
        });
    })();
</script>
@endsection
