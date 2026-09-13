<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold"><i class="bi bi-speedometer2 me-2"></i>Panel de Control</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                    </ol>
                </div>
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content Header-->

    <!--begin::App Content-->
    <div class="app-content pt-4">
        <!--begin::Container-->
        <div class="container-fluid">

            <!-- Row 1: KPIs Estilo Valhalla -->
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-6">
                    <div class="valhalla-kpi purple shadow-sm">
                        <div class="decor-circle"></div>
                        <div class="icon-box">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="content-box">
                            <h3><?php echo number_format($data['stats']['ingresos'], 2); ?></h3>
                            <p>Ingresos</p>
                        </div>
                        <!-- Link invisible que cubre toda la tarjeta -->
                        <a href="<?php echo URLROOT; ?>/caja" class="stretched-link"></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="valhalla-kpi blue shadow-sm">
                        <div class="decor-circle"></div>
                        <div class="icon-box">
                            <i class="bi bi-ticket-perforated"></i>
                        </div>
                        <div class="content-box">
                            <h3><?php echo $data['stats']['boletos_vendidos']; ?></h3>
                            <p>Boletos</p>
                        </div>
                        <a href="<?php echo URLROOT; ?>/ventas" class="stretched-link"></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="valhalla-kpi green shadow-sm">
                        <div class="decor-circle"></div>
                        <div class="icon-box">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="content-box">
                            <h3><?php echo $data['stats']['reservas_vencer']; ?></h3>
                            <p>Reservas</p>
                        </div>
                        <a href="<?php echo URLROOT; ?>/ventas/reservas" class="stretched-link"></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="valhalla-kpi red shadow-sm">
                        <div class="decor-circle"></div>
                        <div class="icon-box">
                            <i class="bi bi-bus-front"></i>
                        </div>
                        <div class="content-box">
                            <h3><?php echo $data['stats']['buses_ruta']; ?></h3>
                            <p>En Ruta</p>
                        </div>
                        <a href="<?php echo URLROOT; ?>/reportes/pasajeros" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Columna Principal (Izquierda) -->
                <div class="col-lg-9">



                    <!-- Buscador -->
                    <div class="input-group input-group-lg shadow-sm mb-4">
                        <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="busqueda_rapida" class="form-control border-0"
                            placeholder="Buscar pasajero, bus o encomienda...">
                        <button class="btn btn-primary px-4" type="button" onclick="buscarRapido()">Buscar</button>
                    </div>

                    <!-- Próximas Salidas -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center bg-white">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-clock-history me-2 text-primary"></i>Próximas Salidas
                            </h5>
                            <span class="badge bg-light text-dark rounded-pill">Programadas</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Hora</th>
                                        <th>Ruta / Destino</th>
                                        <th>Unidad</th>
                                        <th>Ocupación</th>
                                        <th>Estado</th>
                                        <th class="text-end pe-4">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($data['proximas_salidas'])): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                <i class="bi bi-cup-hot fs-1 d-block mb-2 opacity-50"></i>
                                                Sin salidas programadas próximamente
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($data['proximas_salidas'] as $viaje):
                                            $porcentaje = $viaje->capacidad > 0 ? round(($viaje->ocupados / $viaje->capacidad) * 100) : 0;

                                            $colorBarra = $porcentaje >= 75 ? 'bg-success' : ($porcentaje >= 40 ? 'bg-warning' : 'bg-danger');

                                            $estadoClass = 'bg-secondary';
                                            $estadoIcon = 'bi-circle-fill';
                                            switch ($viaje->estado) {
                                                case 'abordando':
                                                    $estadoClass = 'bg-warning text-dark';
                                                    $estadoIcon = 'bi-megaphone-fill';
                                                    break;
                                                case 'en_venta':
                                                    $estadoClass = 'bg-info text-dark';
                                                    $estadoIcon = 'bi-ticket-detailed-fill';
                                                    break;
                                            }
                                        ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bolder fs-5"><?php echo substr($viaje->hora_salida, 0, 5); ?></span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?php echo $viaje->destino; ?></div>
                                                    <small class="text-muted"><?php echo $viaje->ruta; ?></small>
                                                </td>
                                                <td>
                                                    <div class="badge bg-light text-dark border">
                                                        <?php echo $viaje->placa; ?>
                                                    </div>
                                                </td>
                                                <td style="width: 20%;">
                                                    <div class="d-flex justify-content-between small mb-1">
                                                        <span><?php echo $viaje->ocupados; ?>/<?php echo $viaje->capacidad; ?></span>
                                                        <span class="fw-bold"><?php echo $porcentaje; ?>%</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px; border-radius: 3px;">
                                                        <div class="progress-bar <?php echo $colorBarra; ?>" style="width: <?php echo $porcentaje; ?>%"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $estadoClass; ?> rounded-pill">
                                                        <i class="bi <?php echo $estadoIcon; ?> me-1"></i><?php echo ucfirst(str_replace('_', ' ', $viaje->estado)); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <a href="<?php echo URLROOT; ?>/ventas/seleccionar_asientos/<?php echo $viaje->id; ?>"
                                                        class="btn btn-primary btn-sm rounded-pill px-3">
                                                        Vender
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Gráfico Lineal -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="card-title mb-0 fw-bold">Tendencia de Ventas (24h)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartVentasHora" height="80"></canvas>
                        </div>
                    </div>

                </div>

                <!-- Columna Lateral (Derecha): Actividad y Feed -->
                <div class="col-lg-3">

                    <!-- Actividad Reciente (Alertas) -->
                    <div class="card mb-4">
                        <div class="card-header bg-white pt-4 pb-2 border-0">
                            <h6 class="fw-bold mb-0">Actividad Reciente</h6>
                        </div>
                        <div class="card-body pt-0">
                            <div class="timeline">
                                <?php if (!empty($data['alertas'])): ?>
                                    <?php foreach ($data['alertas'] as $alerta):
                                        $icon = 'bi-info-circle';
                                        $color = 'primary';
                                        if ($alerta['tipo'] == 'danger') {
                                            $icon = 'bi-exclamation-octagon';
                                            $color = 'danger';
                                        }
                                        if ($alerta['tipo'] == 'warning') {
                                            $icon = 'bi-exclamation-triangle';
                                            $color = 'warning';
                                        }
                                    ?>
                                        <div class="timeline-item">
                                            <div class="timeline-icon bg-<?php echo $color; ?>">
                                                <i class="bi <?php echo $icon; ?>"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <h6 class="text-dark"><?php echo $alerta['titulo']; ?></h6>
                                                <p class="small text-muted mb-1"><?php echo $alerta['mensaje']; ?></p>
                                                <a href="<?php echo $alerta['url']; ?>" class="btn btn-link btn-sm p-0 text-<?php echo $color; ?>" style="font-size: 0.8rem;">
                                                    <?php echo $alerta['accion']; ?> <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-check-circle text-success fs-1 opacity-50"></i>
                                        <p class="small text-muted mt-2">Todo está en orden.</p>
                                    </div>
                                <?php endif; ?>

                                <!-- Simulación de items estáticos si no hay suficientes alertas para rellenar visualmente -->
                                <?php if ($data['encomiendas_pendientes'] > 0): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-icon bg-success">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>Encomiendas</h6>
                                            <p class="small text-muted mb-1"><?php echo $data['encomiendas_pendientes']; ?> paquete(s) listos</p>
                                            <a href="<?php echo URLROOT; ?>/encomiendas" class="small text-success text-decoration-none">Ver Entregas</a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Top Destinos (Donut) -->
                    <div class="card">
                        <div class="card-header bg-white border-0 pt-3">
                            <h6 class="fw-bold mb-0">Top Destinos</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="chartTopDestinos" height="200"></canvas>
                        </div>
                    </div>

                </div>
            </div>
            <!-- /.row main -->

        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content-->
</main>
<!--end::App Main-->

<!-- BOTÓN MODO KIOSCO (Flotante) -->
<button id="btnModoKiosco" class="btn btn-dark btn-lg shadow-lg position-fixed"
    style="bottom: 20px; right: 20px; z-index: 1050; border-radius: 50%; width: 60px; height: 60px;"
    onclick="toggleModoKiosco()" title="Activar Modo Kiosco">
    <i class="bi bi-fullscreen fs-4"></i>
</button>

<!-- BOTÓN MODO NOCTURNO (Flotante) -->
<button id="btnModoNocturno" class="btn btn-secondary btn-lg shadow-lg position-fixed"
    style="bottom: 90px; right: 20px; z-index: 1050; border-radius: 50%; width: 60px; height: 60px;"
    onclick="toggleModoNocturno()" title="Modo Nocturno">
    <i class="bi bi-moon-stars-fill fs-4"></i>
</button>

<!-- Audio para notificaciones -->
<audio id="audioAlerta" preload="auto">
    <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIGWm98OScTgwOUKzn77RgGwU7k9r0yHUqBSh+zPLaizsKElyx6+unUxMKRp/h8r1rIAUsgs/z2Ik2Bxhpve/mnE4MDlCs5++zYBoFPJPa9Mh0KgUofszy2os7ChJcsevrp1MTCkaf4fK9ayAFLILP89iJNgcYab3v5pxODA5QrOfvs2AaBTyT2vTIdCoFKH7M8tqLOwoSXLHr66dTEwpGn+HyvWsgBSyCz/PYiTYHGGm97+acTgwOUKzn77NgGgU8k9r0yHQqBSh+zPLaizsKElyx6+unUxMKRp/h8r1rIAUsgs/z2Ik2Bxhpve/mnE4MDlCs5++zYBoFPJPa9Mh0KgUofszy2os7ChJcsevrp1MTCkaf4fK9ayAFLILP89iJNgcYab3v5pxODA5QrOfvs2AaBTyT2vTIdCoFKH7M8tqLOwoSXLHr66dTEwpGn+HyvWsgBSyCz/PYiTYHGGm97+acTgwOUKzn77NgGgU8k9r0yHQqBSh+zPLaizsKElyx6+unUxMKRp/h8r1rIAUsgs/z2Ik2Bxhpve/mnE4MDlCs5++zYBoFPJPa9Mh0KgUofszy2os7ChJcsevrp1MTCkaf4fK9ayAFLILP89iJNgcYab3v5pxODA5QrOfvs2AaBTyT2vTIdCoFKH7M8tqLOwoSXLHr66dTEwpGn+HyvWsgBSyCz/PYiTYHGGm97+acTgwOUKzn77NgGgU8k9r0yHQqBSh+zPLaizsKElyx6+unUxMKRp/h8r1rIAUsgs/z2Ik2Bxhpve/mnE4MDlCs5++zYBoFPJPa9Mh0KgUofszy2os7ChJcsevrp1MTCkaf4fK9ayAFLILP89iJNgcYab3v5pxODA5QrOfvs2AaBTyT2vTIdCoFKH7M8tqLOwoSXLHr66dTEwpGn+HyvWsgBSyCz/PYiTYHGGm97+acTgwOUKzn77NgGgU8k9r0yHQqBSh+zPLaizsKElyx6+unUxMKRp/h8r1rIAUsgs/z2Ik2Bxhpve/mnE4MDlCs5++zYBoFPJPa9Mh0KgUofszy2os7ChJcsevrp1MTCkaf4fK9ayAFLILP89iJNgcYab3v5pxODA5QrOfvs2AaBTyT2vTIdCoFKH7M8tqLOwoSXLHr66dTEwpGn+HyvWsgBQ==" type="audio/wav">
</audio>

<!-- Fuente Google Fonts: Nunito -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --font-main: 'Nunito', sans-serif;
        --color-bg: #F5F7FA;
        --color-text: #4A5568;
        --color-card-bg: #FFFFFF;

        /* Paleta Valhalla */
        --color-purple: #6F42C1;
        --color-purple-light: #F3E5F5;
        --color-blue: #33C5FF;
        --color-blue-light: #E1F5FE;
        --color-green: #20C997;
        --color-green-light: #E0F2F1;
        --color-red: #FF6B6B;
        --color-red-light: #FFEBEE;

        --radius-card: 20px;
        --radius-btn: 12px;
        --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.03);
        --shadow-hover: 0 15px 35px rgba(0, 0, 0, 0.08);
    }

    body {
        font-family: var(--font-main);
        background-color: var(--color-bg);
        color: var(--color-text);
    }

    .app-main {
        background-color: var(--color-bg);
    }

    /* Cards Generals */
    .card {
        border: none;
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-soft);
        background-color: var(--color-card-bg);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .card-header {
        background-color: transparent;
        border-bottom: 1px solid rgba(0, 0, 0, 0.03);
        padding: 1.5rem 1.5rem 0.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* KPI Cards (Valhalla Style) */
    .valhalla-kpi {
        position: relative;
        padding: 1.5rem;
        height: 100%;
        border-radius: var(--radius-card);
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        overflow: hidden;
    }

    .valhalla-kpi.purple {
        background: linear-gradient(135deg, #6F42C1, #8E24AA);
    }

    .valhalla-kpi.blue {
        background: linear-gradient(135deg, #33C5FF, #0288D1);
    }

    .valhalla-kpi.green {
        background: linear-gradient(135deg, #20C997, #009688);
    }

    .valhalla-kpi.red {
        background: linear-gradient(135deg, #FF6B6B, #D32F2F);
    }

    .valhalla-kpi .icon-box {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        backdrop-filter: blur(5px);
    }

    .valhalla-kpi .content-box {
        text-align: right;
        z-index: 2;
    }

    .valhalla-kpi h3 {
        font-weight: 800;
        margin: 0;
        font-size: 1.8rem;
    }

    .valhalla-kpi p {
        margin: 0;
        opacity: 0.9;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .valhalla-kpi .decor-circle {
        position: absolute;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        top: -20px;
        left: -20px;
        z-index: 1;
    }

    /* Accesos Rápidos */
    .quick-action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        border-radius: var(--radius-card);
        background-color: #fff;
        border: 1px solid #eee;
        transition: all 0.3s ease;
        height: 100%;
        text-decoration: none;
        color: var(--color-text);
    }

    .quick-action-btn:hover {
        background-color: var(--color-bg);
        border-color: var(--color-purple);
        transform: translateY(-3px);
    }

    .quick-action-btn i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: var(--color-purple);
    }

    .quick-action-btn span {
        font-weight: 700;
        font-size: 0.95rem;
    }

    /* Actividad Reciente / Timeline */
    .timeline {
        position: relative;
    }

    .timeline-item {
        position: relative;
        padding-left: 3rem;
        padding-bottom: 1.5rem;
        border-left: 2px solid #e9ecef;
    }

    .timeline-item:last-child {
        border-left: 2px solid transparent;
        padding-bottom: 0;
    }

    .timeline-icon {
        position: absolute;
        left: -1rem;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.8rem;
        margin-left: 0;
        /* Adjustment */
    }

    .timeline-content h6 {
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 0.2rem;
    }

    .timeline-content small {
        color: #888;
        font-size: 0.8rem;
    }

    /* Text Helpers */
    .text-purple {
        color: var(--color-purple) !important;
    }

    .bg-purple-soft {
        background-color: var(--color-purple-light);
    }

    /* Modo Nocturno (Adaptación Valhalla) */
    body.dark-mode {
        --color-bg: #1a1a1a;
        --color-text: #e0e0e0;
        --color-card-bg: #2d2d2d;
    }

    body.dark-mode .quick-action-btn {
        background-color: #2d2d2d;
        border-color: #444;
        color: #e0e0e0;
    }

    body.dark-mode .quick-action-btn:hover {
        background-color: #333;
    }

    body.dark-mode .card-header {
        border-bottom-color: #444;
    }

    /* Botones flotantes en modo oscuro */
    body.dark-mode #btnModoKiosco,
    body.dark-mode #btnModoNocturno {
        background-color: #333 !important;
        border-color: #555 !important;
    }

    /* Tables */
    .table th {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #888;
        border-bottom-width: 1px;
    }

    .table td {
        vertical-align: middle;
        font-weight: 600;
    }

    /* Nota: el theming del sidebar (colores, estados, marca) ahora vive
       exclusivamente en public/css/custom.css para que sea consistente
       en todo el sistema, no solo en este dashboard. */

    /* Ajuste Ergonómico del Dashboard (Centrado) */
    .app-content .container-fluid {
        max-width: 1600px;
        /* Límite de ancho para pantallas gigantes */
        margin: 0 auto;
        /* Centrado horizontal */
    }

    /* 3. CORRECCIÓN TOTAL DE MODO OSCURO */
    body.dark-mode {
        /* Redefinición Global de Variables para Dark Mode */
        --color-bg: #121212 !important;
        /* Fondo casi negro */
        --color-card-bg: #1E1E1E !important;
        /* Tarjetas gris oscuro */
        --color-text: #E0E0E0 !important;
        --color-border: #333333 !important;

        /* Forzar variables de AdminLTE/Bootstrap */
        --bs-body-bg: #121212;
        --bs-body-color: #E0E0E0;
    }

    /* Input Search & Cards en Dark Mode */
    body.dark-mode .input-group-text,
    body.dark-mode .form-control {
        background-color: #2D2D2D !important;
        border-color: #444 !important;
        color: #FFF !important;
    }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // ==================== VARIABLES GLOBALES ====================
    let modoKiosco = false;
    let autoRefreshInterval = null;
    let ultimasReservasUrgentes = 0;

    // ==================== FUNCIÓN DE BÚSQUEDA RÁPIDA ====================
    function buscarRapido() {
        const termino = document.getElementById('busqueda_rapida').value.trim();
        if (!termino) {
            Swal.fire('Atención', 'Ingrese un término de búsqueda', 'warning');
            return;
        }

        window.location.href = '<?php echo URLROOT; ?>/reportes/pasajeros?buscar=' + encodeURIComponent(termino);
    }

    document.getElementById('busqueda_rapida').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            buscarRapido();
        }
    });

    // ==================== AUTO-REFRESH (30 SEGUNDOS) ====================
    function actualizarDashboard() {
        $.ajax({
            url: '<?php echo URLROOT; ?>/dashboard/obtener_datos_ajax',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    // Actualizar KPIs
                    actualizarKPIs(res.stats);

                    // Actualizar tabla de salidas
                    actualizarTablaSalidas(res.proximas_salidas);

                    // Verificar alertas de audio
                    verificarAlertasAudio(res.stats.reservas_vencer);

                    // Actualizar gráficos
                    if (res.ventas_hora && res.top_destinos) {
                        actualizarGraficos(res.ventas_hora, res.top_destinos);
                    }

                    console.log('✅ Dashboard actualizado: ' + new Date().toLocaleTimeString());
                }
            },
            error: function() {
                console.error('❌ Error al actualizar dashboard');
            }
        });
    }

    function actualizarKPIs(stats) {
        // Actualizar valores con animación suave
        // Mapeo: Purple=Ingresos, Blue=Boletos, Green=Reservas, Red=Buses

        // Ingresos (Purple)
        $('.valhalla-kpi.purple h3').text('Bs. ' + parseFloat(stats.ingresos).toFixed(2));

        // Boletos (Blue)
        $('.valhalla-kpi.blue h3').text(stats.boletos_vendidos);

        // Reservas (Green)
        $('.valhalla-kpi.green h3').text(stats.reservas_vencer);

        // Buses (Red)
        $('.valhalla-kpi.red h3').text(stats.buses_ruta);
    }

    function actualizarTablaSalidas(salidas) {
        const tbody = document.querySelector('table tbody');

        if (salidas.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                    No hay salidas programadas en las próximas 2 horas
                </td>
            </tr>
        `;
            return;
        }

        let html = '';
        salidas.forEach(viaje => {
            const porcentaje = viaje.capacidad > 0 ? Math.round((viaje.ocupados / viaje.capacidad) * 100) : 0;

            let colorBarra = porcentaje >= 75 ? 'bg-success' : (porcentaje >= 40 ? 'bg-warning' : 'bg-danger');
            let textColor = porcentaje >= 75 ? 'text-success' : (porcentaje >= 40 ? 'text-warning' : 'text-danger');

            let estadoClass = 'bg-secondary';
            let estadoIcon = 'bi-circle-fill';
            switch (viaje.estado) {
                case 'abordando':
                    estadoClass = 'bg-warning text-dark';
                    estadoIcon = 'bi-megaphone-fill';
                    break;
                case 'en_venta':
                    estadoClass = 'bg-info text-dark';
                    estadoIcon = 'bi-ticket-detailed-fill';
                    break;
            }

            html += `
            <tr>
                <td class="ps-4">
                    <span class="fw-bolder fs-5">${viaje.hora_salida.substring(0, 5)}</span>
                </td>
                <td>
                    <div class="fw-bold text-dark">${viaje.destino}</div>
                    <small class="text-muted">${viaje.ruta}</small>
                </td>
                <td>
                    <div class="badge bg-light text-dark border">${viaje.placa}</div>
                </td>
                <td style="width: 20%;">
                    <div class="d-flex justify-content-between small mb-1">
                        <span>${viaje.ocupados}/${viaje.capacidad}</span>
                        <span class="fw-bold">${porcentaje}%</span>
                    </div>
                    <div class="progress" style="height: 6px; border-radius: 3px;">
                        <div class="progress-bar ${colorBarra}" style="width: ${porcentaje}%"></div>
                    </div>
                </td>
                <td>
                    <span class="badge ${estadoClass} rounded-pill">
                        <i class="bi ${estadoIcon} me-1"></i>${viaje.estado.replace('_', ' ').charAt(0).toUpperCase() + viaje.estado.slice(1).replace('_', ' ')}
                    </span>
                </td>
                <td class="text-end pe-4">
                    <a href="<?php echo URLROOT; ?>/ventas/seleccionar_asientos/${viaje.id}" 
                       class="btn btn-primary btn-sm rounded-pill px-3">
                        Vender
                    </a>
                </td>
            </tr>
        `;
        });

        tbody.innerHTML = html;
    }

    function actualizarGraficos(ventasHora, topDestinos) {
        // Actualizar gráfico de ventas
        if (window.chartVentas) {
            // Preparar array de 24 horas
            let dataVentas = new Array(24).fill(0);
            ventasHora.forEach(v => {
                dataVentas[parseInt(v.hora)] = v.cantidad;
            });

            window.chartVentas.data.datasets[0].data = dataVentas;
            window.chartVentas.update();
        }

        // Actualizar gráfico de destinos
        if (window.chartDestinos) {
            let labels = [];
            let data = [];

            topDestinos.forEach(d => {
                labels.push(d.destino);
                data.push(d.total_boletos);
            });

            window.chartDestinos.data.labels = labels;
            window.chartDestinos.data.datasets[0].data = data;
            window.chartDestinos.update();
        }
    }

    // ==================== NOTIFICACIONES DE AUDIO ====================
    function verificarAlertasAudio(reservasActuales) {
        // Si hay nuevas reservas urgentes, reproducir sonido
        if (reservasActuales > ultimasReservasUrgentes && reservasActuales > 0) {
            reproducirAlerta();

            // Mostrar notificación visual
            Swal.fire({
                icon: 'warning',
                title: '¡Reservas Urgentes!',
                text: `Hay ${reservasActuales} reserva(s) a punto de vencer`,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        ultimasReservasUrgentes = reservasActuales;
    }

    function reproducirAlerta() {
        const audio = document.getElementById('audioAlerta');
        audio.play().catch(e => console.log('Audio bloqueado por navegador'));
    }

    // ==================== MODO KIOSCO ====================
    function toggleModoKiosco() {
        modoKiosco = !modoKiosco;

        if (modoKiosco) {
            activarModoKiosco();
        } else {
            desactivarModoKiosco();
        }
    }

    function activarModoKiosco() {
        // Ocultar sidebar y header
        document.querySelector('.app-sidebar')?.classList.add('d-none');
        document.querySelector('.app-content-header')?.classList.add('d-none');

        // Expandir contenido principal
        document.querySelector('.app-main')?.classList.add('ms-0');

        // Cambiar icono del botón
        document.querySelector('#btnModoKiosco i').className = 'bi bi-fullscreen-exit fs-4';
        document.getElementById('btnModoKiosco').title = 'Salir de Modo Kiosco';

        // Intentar pantalla completa
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(err => {
                console.log('No se pudo activar pantalla completa:', err);
            });
        }

        // Aumentar tamaño de fuentes para mejor legibilidad
        document.body.style.fontSize = '1.1rem';

        // Mostrar notificación
        Swal.fire({
            icon: 'success',
            title: 'Modo Kiosco Activado',
            text: 'Interfaz optimizada para pantalla táctil',
            timer: 2000,
            showConfirmButton: false
        });
    }

    function desactivarModoKiosco() {
        // Mostrar sidebar y header
        document.querySelector('.app-sidebar')?.classList.remove('d-none');
        document.querySelector('.app-content-header')?.classList.remove('d-none');

        // Restaurar layout
        document.querySelector('.app-main')?.classList.remove('ms-0');

        // Cambiar icono del botón
        document.querySelector('#btnModoKiosco i').className = 'bi bi-fullscreen fs-4';
        document.getElementById('btnModoKiosco').title = 'Activar Modo Kiosco';

        // Salir de pantalla completa
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(err => {
                console.log('No se pudo salir de pantalla completa:', err);
            });
        }

        // Restaurar tamaño de fuente
        document.body.style.fontSize = '';
    }

    // Detectar salida de pantalla completa con ESC
    document.addEventListener('fullscreenchange', function() {
        if (!document.fullscreenElement && modoKiosco) {
            desactivarModoKiosco();
            modoKiosco = false;
        }
    });

    // ==================== MODO NOCTURNO AUTOMÁTICO INTELIGENTE ====================
    let modoNocturnoManual = null; // null = automático, true = forzado ON, false = forzado OFF
    const HORA_INICIO_NOCTURNO = 19; // 7:00 PM
    const HORA_FIN_NOCTURNO = 7; // 7:00 AM

    /**
     * Detecta si debería estar activo el modo nocturno según la hora actual
     */
    function deberiaEstarModoNocturno() {
        const horaActual = new Date().getHours();

        // Entre 19:00 (7 PM) y 23:59 (11:59 PM) o entre 00:00 (12 AM) y 06:59 (6:59 AM)
        return horaActual >= HORA_INICIO_NOCTURNO || horaActual < HORA_FIN_NOCTURNO;
    }

    /**
     * Aplica el modo nocturno al DOM
     */
    function aplicarModoNocturno() {
        document.body.classList.add('dark-mode');

        // Actualizar botón
        const btn = document.getElementById('btnModoNocturno');
        btn.classList.add('active');
        btn.title = 'Desactivar Modo Nocturno';
        btn.querySelector('i').className = 'bi bi-sun-fill fs-4';

        // Guardar preferencia
        localStorage.setItem('modoNocturno', 'true');

        console.log('🌙 Modo Nocturno ACTIVADO');
    }

    /**
     * Desactiva el modo nocturno del DOM
     */
    function desactivarModoNocturno() {
        document.body.classList.remove('dark-mode');

        // Actualizar botón
        const btn = document.getElementById('btnModoNocturno');
        btn.classList.remove('active');
        btn.title = 'Activar Modo Nocturno';
        btn.querySelector('i').className = 'bi bi-moon-stars-fill fs-4';

        // Guardar preferencia
        localStorage.setItem('modoNocturno', 'false');

        console.log('☀️ Modo Nocturno DESACTIVADO');
    }

    /**
     * Toggle manual del modo nocturno
     */
    function toggleModoNocturno() {
        const estaActivo = document.body.classList.contains('dark-mode');

        if (estaActivo) {
            // Usuario desactiva manualmente
            desactivarModoNocturno();
            modoNocturnoManual = false;
            localStorage.setItem('modoNocturnoManual', 'false');

            Swal.fire({
                icon: 'info',
                title: 'Modo Diurno Activado',
                text: 'Has desactivado el modo nocturno manualmente',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            // Usuario activa manualmente
            aplicarModoNocturno();
            modoNocturnoManual = true;
            localStorage.setItem('modoNocturnoManual', 'true');

            Swal.fire({
                icon: 'success',
                title: 'Modo Nocturno Activado',
                text: 'Interfaz optimizada para reducir fatiga visual',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    }

    /**
     * Verifica y aplica el modo nocturno automático
     */
    function verificarModoNocturnoAutomatico() {
        // Si hay preferencia manual, respetarla
        if (modoNocturnoManual !== null) {
            return;
        }

        const deberiaEstar = deberiaEstarModoNocturno();
        const estaActivo = document.body.classList.contains('dark-mode');

        if (deberiaEstar && !estaActivo) {
            aplicarModoNocturno();

            // Notificación sutil de activación automática
            Swal.fire({
                icon: 'info',
                title: 'Modo Nocturno Automático',
                html: `<small>Activado automáticamente (${HORA_INICIO_NOCTURNO}:00 - ${HORA_FIN_NOCTURNO}:00)</small>`,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'bottom-end',
                customClass: {
                    popup: 'small-toast'
                }
            });
        } else if (!deberiaEstar && estaActivo && modoNocturnoManual === null) {
            desactivarModoNocturno();

            Swal.fire({
                icon: 'info',
                title: 'Modo Diurno Automático',
                html: '<small>Desactivado automáticamente</small>',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'bottom-end'
            });
        }
    }

    /**
     * Inicializa el modo nocturno al cargar la página
     */
    function inicializarModoNocturno() {
        // Cargar preferencia manual del usuario
        const preferenciaModoManual = localStorage.getItem('modoNocturnoManual');
        const btn = document.getElementById('btnModoNocturno');

        if (preferenciaModoManual === 'true') {
            modoNocturnoManual = true;
            aplicarModoNocturno();
            btn.classList.remove('modo-automatico');
            console.log('🌙 Modo Nocturno: MANUAL (Forzado ON)');
        } else if (preferenciaModoManual === 'false') {
            modoNocturnoManual = false;
            desactivarModoNocturno();
            btn.classList.remove('modo-automatico');
            console.log('☀️ Modo Nocturno: MANUAL (Forzado OFF)');
        } else {
            // Modo automático
            modoNocturnoManual = null;
            btn.classList.add('modo-automatico');
            console.log('🤖 Modo Nocturno: AUTOMÁTICO');

            // Aplicar según horario
            if (deberiaEstarModoNocturno()) {
                aplicarModoNocturno();
            }
        }

        // Verificar cada 5 minutos si cambió la hora (solo en modo automático)
        setInterval(function() {
            if (modoNocturnoManual === null) {
                verificarModoNocturnoAutomatico();
            }
        }, 5 * 60 * 1000); // 5 minutos
    }

    // ==================== INICIALIZACIÓN ====================
    $(document).ready(function() {
        // Inicializar Modo Nocturno Inteligente
        inicializarModoNocturno();

        // Iniciar auto-refresh cada 30 segundos
        autoRefreshInterval = setInterval(actualizarDashboard, 30000);
        console.log('🔄 Auto-refresh activado (30s)');

        // Guardar valor inicial de reservas
        ultimasReservasUrgentes = <?php echo $data['stats']['reservas_vencer']; ?>;
    });

    // ==================== GRÁFICOS ====================
    // Gráfico de Ventas por Hora
    <?php if (!empty($data['ventas_hora'])): ?>
        const ventasData = {
            labels: [
                <?php
                for ($h = 0; $h < 24; $h++) {
                    echo "'$h:00',";
                }
                ?>
            ],
            datasets: [{
                label: 'Boletos Vendidos',
                data: [
                    <?php
                    $ventasMap = [];
                    foreach ($data['ventas_hora'] as $v) {
                        $ventasMap[$v->hora] = $v->cantidad;
                    }
                    for ($h = 0; $h < 24; $h++) {
                        echo isset($ventasMap[$h]) ? $ventasMap[$h] : 0;
                        echo ',';
                    }
                    ?>
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        };

        window.chartVentas = new Chart(document.getElementById('chartVentasHora'), {
            type: 'line',
            data: ventasData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    <?php endif; ?>

    // Gráfico de Top Destinos (Pie Chart)
    <?php if (!empty($data['top_destinos'])): ?>
        const destinosData = {
            labels: [
                <?php foreach ($data['top_destinos'] as $d): ?> '<?php echo $d->destino; ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($data['top_destinos'] as $d): ?>
                        <?php echo $d->total_boletos; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };

        window.chartDestinos = new Chart(document.getElementById('chartTopDestinos'), {
            type: 'doughnut',
            data: destinosData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    <?php endif; ?>
</script>
```