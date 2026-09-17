<?php
/**
 * Pantalla del pasajero para pagar con QR (se abre en un segundo monitor
 * orientado al cliente). No muestra datos personales: solo monto, asiento,
 * destino y el estado del pago, que se actualiza solo.
 */
$cobro = $data['cobro'];
$cfg = $data['config'];
$qrFijo = $data['qr_fijo'] ?? null;
$qrLibelula = $data['qr_libelula'] ?? null;
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$empresa = $cfg['empresa_nombre'] ?? 'Venta de pasajes';
$hayDosOpciones = $qrFijo && $qrLibelula;
// Si hay una sola opción de QR-imagen, esa es "la" opción por defecto. Con
// las dos disponibles, se arranca en Libélula (confirmación automática).
$qr = $qrLibelula ?: $qrFijo;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pagar con QR · <?php echo $e($empresa); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --fondo: #f4f5f9;
            --tarjeta: #ffffff;
            --texto: #111827;
            --suave: #6b7280;
            --acento: #4f46e5;
            --ok: #059669;
            --aviso: #b45309;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: var(--fondo);
            color: var(--texto);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
        }
        .tarjeta {
            width: min(100%, 880px);
            background: var(--tarjeta);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
            padding: clamp(24px, 4vw, 48px);
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: clamp(24px, 4vw, 48px);
            align-items: center;
        }
        .qr {
            width: 100%;
            max-width: 380px;
            aspect-ratio: 1;
            object-fit: contain;
            justify-self: center;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
        }
        .tabs-qr { display: flex; gap: 8px; justify-content: center; margin-bottom: 14px; }
        .tabs-qr button {
            border: 2px solid #e5e7eb; background: #fff; color: var(--suave);
            font-weight: 700; font-size: 0.9rem; padding: 8px 18px; border-radius: 999px;
            cursor: pointer; font-family: inherit;
        }
        .tabs-qr button.activa { border-color: var(--acento); color: var(--acento); background: #eef2ff; }
        .empresa { font-weight: 700; color: var(--suave); margin: 0 0 8px; }
        h1 { font-size: clamp(1.6rem, 3vw, 2.2rem); margin: 0 0 20px; line-height: 1.15; }
        .monto-label { color: var(--suave); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700; }
        .monto { font-size: clamp(2.6rem, 6vw, 4rem); font-weight: 800; color: var(--acento); line-height: 1; margin: 4px 0 20px; }
        .detalle { font-size: 1.05rem; line-height: 1.6; margin: 0 0 20px; }
        .reloj { font-variant-numeric: tabular-nums; font-weight: 800; }
        .instrucciones { color: var(--suave); font-size: 0.95rem; margin: 0; }
        .estado { display: none; text-align: center; grid-column: 1 / -1; }
        .estado .icono { font-size: 5rem; line-height: 1; }
        .estado h2 { font-size: clamp(1.8rem, 4vw, 2.6rem); margin: 12px 0 8px; }
        .estado p { color: var(--suave); font-size: 1.1rem; margin: 0; }
        body.pagado .pendiente, body.vencido .pendiente { display: none; }
        body.pagado .estado-pagado, body.vencido .estado-vencido { display: block; }
        .estado-pagado h2 { color: var(--ok); }
        .estado-vencido h2 { color: var(--aviso); }
        @media (max-width: 700px) {
            .tarjeta { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="<?php echo !$cobro ? 'vencido' : ($cobro->estado === 'vendido' ? 'pagado' : ($cobro->estado !== 'reservado' ? 'vencido' : '')); ?>">
    <main class="tarjeta" aria-live="polite">
        <?php if ($cobro && $qr): ?>
            <div class="pendiente" style="grid-column: 1 / -1; display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: clamp(24px, 4vw, 48px); align-items: center;">
                <div>
                    <?php if ($hayDosOpciones): ?>
                        <div class="tabs-qr">
                            <button type="button" id="tabQrLibelula" class="activa" onclick="elegirQr('libelula')">Libélula</button>
                            <button type="button" id="tabQrFijo" onclick="elegirQr('fijo')">QR Fijo</button>
                        </div>
                    <?php endif; ?>
                    <img class="qr" id="imgQrPantalla" src="<?php echo $e($qr['imagen']); ?>"
                         data-src-libelula="<?php echo $qrLibelula ? $e($qrLibelula['imagen']) : ''; ?>"
                         data-src-fijo="<?php echo $qrFijo ? $e($qrFijo['imagen']) : ''; ?>"
                         alt="Código QR para pagar">
                </div>
                <section>
                    <p class="empresa"><?php echo $e($empresa); ?></p>
                    <h1>Escanee el código para pagar su pasaje</h1>
                    <div class="monto-label">Monto a pagar</div>
                    <div class="monto">Bs. <?php echo number_format((float) $cobro->precio_final, 2); ?></div>
                    <p class="detalle">
                        Asiento <strong><?php echo (int) $cobro->numero_asiento; ?></strong><br>
                        <?php echo $e($cobro->origen); ?> → <?php echo $e($cobro->destino); ?><br>
                        Tiempo para pagar: <span class="reloj" id="reloj">--:--</span>
                    </p>
                    <p class="instrucciones" id="leyendaQrPantalla"
                       data-leyenda-libelula="<?php echo $qrLibelula ? $e(trim($qrLibelula['titular'] . ($qrLibelula['entidad'] ? ' · ' . $qrLibelula['entidad'] : ''), ' ·')) : ''; ?>"
                       data-leyenda-fija="<?php echo $qrFijo ? $e(trim($qrFijo['titular'] . ($qrFijo['entidad'] ? ' · ' . $qrFijo['entidad'] : ''), ' ·')) : ''; ?>">
                        <?php if ($qr['titular'] || $qr['entidad']): ?>Beneficiario: <?php echo $e(trim($qr['titular'] . ($qr['entidad'] ? ' · ' . $qr['entidad'] : ''), ' ·')); ?><?php endif; ?>
                    </p>
                    <?php if (!empty($cfg['pago_qr_instrucciones'])): ?>
                        <p class="instrucciones"><?php echo $e($cfg['pago_qr_instrucciones']); ?></p>
                    <?php endif; ?>
                </section>
            </div>
        <?php elseif ($cobro && !empty($data['libelula_url_pasarela'])): ?>
            <section class="pendiente" style="grid-column: 1 / -1; text-align: center;">
                <p class="empresa"><?php echo $e($empresa); ?></p>
                <h1>Complete su pago en la pasarela de Libélula</h1>
                <div class="monto-label">Monto a pagar</div>
                <div class="monto">Bs. <?php echo number_format((float) $cobro->precio_final, 2); ?></div>
                <p class="detalle">Tiempo para pagar: <span class="reloj" id="reloj">--:--</span></p>
                <a href="<?php echo $e($data['libelula_url_pasarela']); ?>" target="_blank" rel="noopener"
                   style="display:inline-block; margin-top:12px; padding:14px 28px; background:var(--acento); color:#fff; border-radius:12px; font-weight:700; text-decoration:none;">
                    Abrir pasarela de pago
                </a>
            </section>
        <?php endif; ?>

        <div class="estado estado-pagado">
            <div class="icono" aria-hidden="true">✅</div>
            <h2>¡Pago recibido!</h2>
            <p>Gracias. Su boleto se está imprimiendo.</p>
        </div>
        <div class="estado estado-vencido">
            <div class="icono" aria-hidden="true">⏱️</div>
            <h2>Este cobro ya no está activo</h2>
            <p>Consulte al vendedor.</p>
        </div>
    </main>

    <script>
        function elegirQr(opcion) {
            const img = document.getElementById('imgQrPantalla');
            const leyenda = document.getElementById('leyendaQrPantalla');
            if (!img) return;

            const esLibelula = opcion === 'libelula';
            const src = esLibelula ? img.dataset.srcLibelula : img.dataset.srcFijo;
            if (!src) return;
            img.src = src;

            if (leyenda) {
                const texto = esLibelula ? leyenda.dataset.leyendaLibelula : leyenda.dataset.leyendaFija;
                leyenda.textContent = texto ? ('Beneficiario: ' + texto) : '';
            }

            const tabLibelula = document.getElementById('tabQrLibelula');
            const tabFijo = document.getElementById('tabQrFijo');
            if (tabLibelula && tabFijo) {
                tabLibelula.classList.toggle('activa', esLibelula);
                tabFijo.classList.toggle('activa', !esLibelula);
            }
        }

        (function() {
            const URL_ESTADO = '<?php echo URLROOT; ?>/ventas/estado_cobro/<?php echo (int) ($cobro->id ?? 0); ?>';
            let segundos = <?php echo (int) ($cobro->segundos_restantes ?? 0); ?>;
            const reloj = document.getElementById('reloj');

            function pintarReloj() {
                if (!reloj) return;
                reloj.textContent = String(Math.floor(segundos / 60)).padStart(2, '0') + ':' + String(segundos % 60).padStart(2, '0');
            }

            function cambiarEstado(clase) {
                document.body.className = clase;
                clearInterval(tic);
                if (clase === 'pagado') setTimeout(() => window.close(), 8000);
            }

            pintarReloj();
            const tic = setInterval(() => { segundos = Math.max(0, segundos - 1); pintarReloj(); }, 1000);

            if (document.body.className) return;
            const poll = setInterval(() => {
                fetch(URL_ESTADO, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
                    .then(r => r.json())
                    .then(res => {
                        if (!res.success) return;
                        segundos = parseInt(res.data.segundos_restantes || 0, 10);
                        if (res.data.estado === 'vendido') { clearInterval(poll); cambiarEstado('pagado'); }
                        else if (res.data.estado !== 'reservado') { clearInterval(poll); cambiarEstado('vencido'); }
                    })
                    .catch(() => {});
            }, 3000);
        })();
    </script>
</body>
</html>
