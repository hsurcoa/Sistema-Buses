<?php

/**
 * Componente: Mapa de Asientos Dinámico
 * Descripción: Genera el diagrama de asientos automáticamente según los datos del bus
 * Uso: Pasar $busData con la configuración del bus
 */

// Validar que existan los datos del bus
if (!isset($busData)) {
    echo '<div class="alert alert-warning">No se proporcionaron datos del bus</div>';
    return;
}

// Extraer datos del bus
$busId = $busData['id'] ?? 0;
$busNombre = $busData['nombre'] ?? 'Bus';
$capacidadTotal = $busData['capacidad'] ?? 0;
$numeroPisos = $busData['pisos'] ?? 1;
$configuracion = isset($busData['configuracion_asientos']) ? json_decode($busData['configuracion_asientos'], true) : null;

// Si no hay configuración personalizada, generar una automática
if (!$configuracion) {
    $configuracion = generarConfiguracionAutomatica($capacidadTotal, $numeroPisos);
}

/**
 * Función para generar configuración automática
 */
function generarConfiguracionAutomatica($capacidad, $pisos)
{
    $config = [
        'layout' => '2-2', // 2 asientos - pasillo - 2 asientos
        'asientos_premium' => 2, // Asientos en cabina
        'pisos' => []
    ];

    if ($pisos == 1) {
        // Bus de 1 piso
        $asientosNormales = $capacidad - 2; // Restar los premium
        $filasPiso1 = ceil($asientosNormales / 4);

        $config['pisos'][1] = [
            'numero' => 1,
            'tiene_cabina' => true,
            'filas' => $filasPiso1,
            'asientos_por_fila' => 4,
            'tiene_servicios' => true
        ];
    } else {
        // Bus de 2 pisos
        $asientosPiso1 = ceil($capacidad * 0.6); // 60% en piso 1
        $asientosPiso2 = $capacidad - $asientosPiso1;

        $filasPiso1 = ceil(($asientosPiso1 - 2) / 4); // Restar premium
        $filasPiso2 = ceil($asientosPiso2 / 4);

        $config['pisos'][1] = [
            'numero' => 1,
            'tiene_cabina' => true,
            'filas' => $filasPiso1,
            'asientos_por_fila' => 4,
            'tiene_servicios' => true
        ];

        $config['pisos'][2] = [
            'numero' => 2,
            'tiene_cabina' => false,
            'filas' => $filasPiso2,
            'asientos_por_fila' => 4,
            'tiene_servicios' => false
        ];
    }

    return $config;
}

/**
 * Función para obtener estados de asientos desde la BD
 */
function obtenerEstadosAsientos($busId, $asientos = null)
{
    // Si se pasaron asientos desde el controlador, usarlos
    if ($asientos && is_array($asientos)) {
        $estados = [];
        foreach ($asientos as $asiento) {
            $key = $asiento->piso . '-' . $asiento->numero;
            $estados[$key] = [
                'id' => $asiento->id,
                'numero' => $asiento->numero,
                'piso' => $asiento->piso,
                'estado' => $asiento->estado ?? 'disponible',
                'tipo' => $asiento->tipo ?? 'normal'
            ];
        }
        return $estados;
    }

    // Si no, generar estados por defecto
    return [];
}

// Obtener estados de asientos
$estadosAsientos = obtenerEstadosAsientos($busId, $asientos ?? null);
?>

<!-- Contenedor Principal del Mapa Dinámico -->
<div class="mapa-asientos-dinamico" id="mapaAsientosDinamico" data-bus-id="<?php echo $busId; ?>">

    <!-- Header con Info del Bus -->
    <div class="bus-info-header-dinamico mb-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-1">
                    <i class="bi bi-bus-front-fill text-primary me-2"></i>
                    <?php echo htmlspecialchars($busNombre); ?>
                </h5>
                <div class="bus-stats">
                    <span class="badge bg-primary me-2">
                        <i class="bi bi-people-fill me-1"></i><?php echo $capacidadTotal; ?> asientos
                    </span>
                    <span class="badge bg-info">
                        <i class="bi bi-layers-fill me-1"></i><?php echo $numeroPisos; ?> piso(s)
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-sm btn-outline-secondary" onclick="resetearSeleccionDinamica()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Limpiar
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs para Pisos (si hay más de 1) -->
    <?php if ($numeroPisos > 1): ?>
        <div class="mb-3">
            <div class="btn-group w-100" role="group">
                <?php for ($p = 1; $p <= $numeroPisos; $p++): ?>
                    <button type="button"
                        class="btn btn-outline-primary <?php echo $p === 1 ? 'active' : ''; ?>"
                        id="tabPisoDinamico<?php echo $p; ?>"
                        onclick="cambiarPisoDinamico(<?php echo $p; ?>)">
                        <i class="bi bi-<?php echo $p; ?>-circle-fill me-2"></i>Piso <?php echo $p; ?>
                    </button>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Renderizar cada piso -->
    <?php
    $asientoNumero = 1;
    foreach ($configuracion['pisos'] as $numPiso => $configPiso):
    ?>
        <div id="pisoDinamico<?php echo $numPiso; ?>"
            class="piso-container-dinamico"
            style="<?php echo $numPiso > 1 ? 'display: none;' : ''; ?>">

            <!-- Chasis del Bus -->
            <div class="bus-chassis-dinamico piso-<?php echo $numPiso; ?>">

                <?php if ($configPiso['tiene_cabina']): ?>
                    <!-- Cabina del Conductor -->
                    <div class="bus-cabin-dinamico">
                        <div class="steering-wheel-dinamico">
                            <i class="bi bi-circle-fill"></i>
                        </div>
                        <div class="cabin-seats-dinamico">
                            <?php
                            // Asientos Premium en cabina
                            for ($i = 0; $i < 2; $i++):
                                $asientoKey = $numPiso . '-' . $asientoNumero;
                                $estadoAsiento = $estadosAsientos[$asientoKey] ?? ['estado' => 'disponible', 'tipo' => 'premium'];
                            ?>
                                <div class="seat-dinamico <?php echo $estadoAsiento['estado']; ?> <?php echo $estadoAsiento['tipo']; ?>"
                                    data-asiento="<?php echo $asientoNumero; ?>"
                                    data-piso="<?php echo $numPiso; ?>"
                                    data-tipo="<?php echo $estadoAsiento['tipo']; ?>"
                                    onclick="seleccionarAsientoDinamico(this)">
                                    <div class="seat-body-dinamico"></div>
                                    <div class="seat-arm-dinamico left"></div>
                                    <div class="seat-arm-dinamico right"></div>
                                    <span class="seat-number-dinamico"><?php echo $asientoNumero; ?></span>
                                </div>
                            <?php
                                $asientoNumero++;
                            endfor;
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Grid Principal de Asientos -->
                <div class="seats-grid-dinamico">
                    <?php
                    // Generar filas de asientos
                    for ($fila = 0; $fila < $configPiso['filas']; $fila++):
                    ?>
                        <div class="seat-row-dinamico">
                            <?php
                            // Generar asientos por fila según el layout
                            $asientosPorLado = $configPiso['asientos_por_fila'] / 2;

                            // Lado izquierdo
                            for ($col = 0; $col < $asientosPorLado; $col++):
                                if ($asientoNumero > $capacidadTotal) break;

                                $asientoKey = $numPiso . '-' . $asientoNumero;
                                $estadoAsiento = $estadosAsientos[$asientoKey] ?? ['estado' => 'disponible', 'tipo' => 'normal'];
                            ?>
                                <div class="seat-dinamico <?php echo $estadoAsiento['estado']; ?> <?php echo $estadoAsiento['tipo']; ?>"
                                    data-asiento="<?php echo $asientoNumero; ?>"
                                    data-piso="<?php echo $numPiso; ?>"
                                    data-tipo="<?php echo $estadoAsiento['tipo']; ?>"
                                    onclick="seleccionarAsientoDinamico(this)">
                                    <div class="seat-body-dinamico"></div>
                                    <div class="seat-arm-dinamico left"></div>
                                    <div class="seat-arm-dinamico right"></div>
                                    <span class="seat-number-dinamico"><?php echo $asientoNumero; ?></span>
                                </div>
                            <?php
                                $asientoNumero++;
                            endfor;
                            ?>

                            <!-- Pasillo Central -->
                            <div class="aisle-dinamico"></div>

                            <?php
                            // Lado derecho
                            for ($col = 0; $col < $asientosPorLado; $col++):
                                if ($asientoNumero > $capacidadTotal) break;

                                $asientoKey = $numPiso . '-' . $asientoNumero;
                                $estadoAsiento = $estadosAsientos[$asientoKey] ?? ['estado' => 'disponible', 'tipo' => 'normal'];
                            ?>
                                <div class="seat-dinamico <?php echo $estadoAsiento['estado']; ?> <?php echo $estadoAsiento['tipo']; ?>"
                                    data-asiento="<?php echo $asientoNumero; ?>"
                                    data-piso="<?php echo $numPiso; ?>"
                                    data-tipo="<?php echo $estadoAsiento['tipo']; ?>"
                                    onclick="seleccionarAsientoDinamico(this)">
                                    <div class="seat-body-dinamico"></div>
                                    <div class="seat-arm-dinamico left"></div>
                                    <div class="seat-arm-dinamico right"></div>
                                    <span class="seat-number-dinamico"><?php echo $asientoNumero; ?></span>
                                </div>
                            <?php
                                $asientoNumero++;
                            endfor;
                            ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <?php if ($configPiso['tiene_servicios']): ?>
                    <!-- Sección de Servicios -->
                    <div class="bus-services-dinamico">
                        <div class="service-item-dinamico bathroom">
                            <i class="bi bi-door-closed-fill"></i>
                            <span>BAÑO</span>
                        </div>
                        <div class="service-item-dinamico stairs">
                            <i class="bi bi-arrow-up-circle-fill"></i>
                            <span>ESCALERA</span>
                        </div>
                        <div class="service-item-dinamico bedroom">
                            <i class="bi bi-moon-stars-fill"></i>
                            <span>DORMITORIO</span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($numPiso > 1): ?>
                    <!-- Indicador de Piso -->
                    <div class="floor-indicator-dinamico">
                        <i class="bi bi-<?php echo $numPiso; ?>-circle-fill"></i>
                        <span>PISO <?php echo $numPiso; ?></span>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    <?php endforeach; ?>

    <!-- Panel de Resumen -->
    <div class="row mt-4">
        <!-- Leyenda -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="bi bi-info-circle-fill text-info me-2"></i>Leyenda
                    </h6>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="legend-item-dinamico">
                            <div class="seat-preview-dinamico disponible"></div>
                            <span class="ms-2">Disponible</span>
                        </div>
                        <div class="legend-item-dinamico">
                            <div class="seat-preview-dinamico ocupado"></div>
                            <span class="ms-2">Ocupado</span>
                        </div>
                        <div class="legend-item-dinamico">
                            <div class="seat-preview-dinamico seleccionado"></div>
                            <span class="ms-2">Seleccionado</span>
                        </div>
                        <div class="legend-item-dinamico">
                            <div class="seat-preview-dinamico premium"></div>
                            <span class="ms-2">Premium</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen de Compra -->
        <div class="col-md-6">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="bi bi-cart-check-fill text-primary me-2"></i>Resumen
                    </h6>
                    <div class="summary-content-dinamico">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Asientos:</span>
                            <strong id="resumenAsientosDinamico">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Cantidad:</span>
                            <strong id="resumenCantidadDinamico">0</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Precio unitario:</span>
                            <strong>Bs. <span id="precioDinamico">50.00</span></strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="h6 mb-0">Total:</span>
                            <strong class="h5 mb-0 text-primary">Bs. <span id="totalDinamico">0.00</span></strong>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 mt-3" onclick="confirmarReservaDinamica()" id="btnConfirmarDinamico" disabled>
                        <i class="bi bi-check-circle-fill me-2"></i>Confirmar Reserva
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Campo oculto para datos -->
<input type="hidden" id="asientosSeleccionadosDinamico" name="asientos_seleccionados" value="">

<style>
    /* ========================================
   ESTILOS DINÁMICOS DEL MAPA
   ======================================== */

    .mapa-asientos-dinamico {
        padding: 1.5rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 1rem;
    }

    .bus-info-header-dinamico {
        background: white;
        padding: 1.5rem;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .piso-container-dinamico {
        animation: fadeInDinamico 0.4s ease-out;
    }

    @keyframes fadeInDinamico {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Chasis del Bus */
    .bus-chassis-dinamico {
        background: linear-gradient(180deg, #e8e8e8 0%, #f5f5f5 100%);
        border: 5px solid #5a5a5a;
        border-radius: 3rem;
        padding: 2rem 1.5rem;
        box-shadow:
            0 10px 30px rgba(0, 0, 0, 0.2),
            inset 0 2px 4px rgba(255, 255, 255, 0.5);
        position: relative;
        max-width: 650px;
        margin: 0 auto;
    }

    .bus-chassis-dinamico.piso-2 {
        background: linear-gradient(180deg, #e0e7ff 0%, #f5f5f5 100%);
    }

    /* Cabina */
    .bus-cabin-dinamico {
        background: linear-gradient(180deg, #2c2c2c 0%, #1a1a1a 100%);
        border-radius: 2.5rem 2.5rem 0 0;
        padding: 1.25rem;
        margin: -2rem -1.5rem 2rem -1.5rem;
        display: grid;
        grid-template-columns: 80px 1fr;
        gap: 1rem;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }

    .steering-wheel-dinamico {
        width: 55px;
        height: 55px;
        border: 4px solid #888;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #666;
        font-size: 1.5rem;
        background: radial-gradient(circle, #444 0%, #222 100%);
        margin: 0 auto;
    }

    .cabin-seats-dinamico {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    /* Grid de Asientos */
    .seats-grid-dinamico {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 0 0.5rem;
    }

    .seat-row-dinamico {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.75rem;
    }

    /* Asiento Individual */
    .seat-dinamico {
        position: relative;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
        user-select: none;
    }

    .seat-dinamico:hover:not(.ocupado) {
        transform: scale(1.15) translateY(-3px);
    }

    .seat-body-dinamico {
        width: 55px;
        height: 55px;
        border-radius: 0.875rem;
        position: relative;
        box-shadow:
            0 4px 8px rgba(0, 0, 0, 0.25),
            inset 0 2px 4px rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }

    .seat-arm-dinamico {
        position: absolute;
        width: 10px;
        height: 45px;
        background: linear-gradient(180deg, #555 0%, #333 100%);
        border-radius: 5px;
        top: 5px;
        box-shadow:
            inset 0 2px 4px rgba(0, 0, 0, 0.4),
            0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .seat-arm-dinamico.left {
        left: -12px;
    }

    .seat-arm-dinamico.right {
        right: -12px;
    }

    .seat-number-dinamico {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: 700;
        font-size: 0.95rem;
        color: white;
        text-shadow:
            0 1px 2px rgba(0, 0, 0, 0.6),
            0 0 4px rgba(0, 0, 0, 0.4);
        z-index: 10;
        pointer-events: none;
    }

    /* Estados */
    .seat-dinamico.disponible .seat-body-dinamico {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: 3px solid #1d4ed8;
    }

    .seat-dinamico.ocupado .seat-body-dinamico {
        background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
        border: 3px solid #4b5563;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .seat-dinamico.seleccionado .seat-body-dinamico {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: 3px solid #047857;
        animation: pulseDinamico 1.5s ease-in-out infinite;
    }

    .seat-dinamico.premium .seat-body-dinamico {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border: 3px solid #b45309;
    }

    @keyframes pulseDinamico {

        0%,
        100% {
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4), 0 0 0 0 rgba(16, 185, 129, 0.7);
        }

        50% {
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.6), 0 0 0 8px rgba(16, 185, 129, 0);
        }
    }

    /* Pasillo */
    .aisle-dinamico {
        width: 50px;
        height: 65px;
        background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.2) 20%, rgba(255, 255, 255, 0.4) 50%, rgba(255, 255, 255, 0.2) 80%, transparent 100%);
        border-left: 2px dashed rgba(0, 0, 0, 0.15);
        border-right: 2px dashed rgba(0, 0, 0, 0.15);
        position: relative;
    }

    /* Servicios */
    .bus-services-dinamico {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 3px dashed rgba(0, 0, 0, 0.15);
    }

    .service-item-dinamico {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        border: 3px solid #334155;
        border-radius: 1rem;
        padding: 1.25rem;
        text-align: center;
        color: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
    }

    .service-item-dinamico i {
        font-size: 2rem;
    }

    .service-item-dinamico span {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .service-item-dinamico.bathroom {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        border-color: #0e7490;
    }

    .service-item-dinamico.stairs {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        border-color: #6d28d9;
    }

    .service-item-dinamico.bedroom {
        background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
        border-color: #be185d;
    }

    /* Indicador de Piso */
    .floor-indicator-dinamico {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        padding: 0.75rem 1.25rem;
        border-radius: 2rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        font-size: 0.9rem;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        letter-spacing: 1px;
    }

    /* Leyenda */
    .legend-item-dinamico {
        display: flex;
        align-items: center;
    }

    .seat-preview-dinamico {
        width: 35px;
        height: 35px;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    .seat-preview-dinamico.disponible {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: 2px solid #1d4ed8;
    }

    .seat-preview-dinamico.ocupado {
        background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
        border: 2px solid #4b5563;
    }

    .seat-preview-dinamico.seleccionado {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: 2px solid #047857;
    }

    .seat-preview-dinamico.premium {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border: 2px solid #b45309;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .bus-chassis-dinamico {
            padding: 1.5rem 1rem;
            border-radius: 2rem;
        }

        .seat-body-dinamico {
            width: 45px;
            height: 45px;
        }

        .seat-number-dinamico {
            font-size: 0.8rem;
        }

        .aisle-dinamico {
            width: 35px;
        }
    }
</style>

<script>
    // ========================================
    // JAVASCRIPT DINÁMICO
    // ========================================

    const mapaDinamico = {
        asientosSeleccionados: [],
        precioUnitario: 50.00,
        pisoActual: 1
    };

    /**
     * Cambiar piso
     */
    function cambiarPisoDinamico(numeroPiso) {
        // Ocultar todos los pisos
        document.querySelectorAll('.piso-container-dinamico').forEach(piso => {
            piso.style.display = 'none';
        });

        // Mostrar el piso seleccionado
        const pisoSeleccionado = document.getElementById('pisoDinamico' + numeroPiso);
        if (pisoSeleccionado) {
            pisoSeleccionado.style.display = 'block';
        }

        // Actualizar tabs
        document.querySelectorAll('[id^="tabPisoDinamico"]').forEach(tab => {
            tab.classList.remove('active');
        });
        const tabActivo = document.getElementById('tabPisoDinamico' + numeroPiso);
        if (tabActivo) {
            tabActivo.classList.add('active');
        }

        mapaDinamico.pisoActual = numeroPiso;
    }

    /**
     * Seleccionar asiento
     */
    function seleccionarAsientoDinamico(elemento) {
        if (elemento.classList.contains('ocupado')) {
            Swal.fire({
                icon: 'warning',
                title: 'Asiento Ocupado',
                text: 'Este asiento ya está reservado',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
            return;
        }

        const numero = elemento.getAttribute('data-asiento');
        const piso = elemento.getAttribute('data-piso');
        const tipo = elemento.getAttribute('data-tipo');
        const key = piso + '-' + numero;

        // Toggle selección
        if (elemento.classList.contains('seleccionado')) {
            elemento.classList.remove('seleccionado');
            elemento.classList.add('disponible');

            mapaDinamico.asientosSeleccionados = mapaDinamico.asientosSeleccionados.filter(
                a => a.key !== key
            );
        } else {
            elemento.classList.remove('disponible');
            elemento.classList.add('seleccionado');

            mapaDinamico.asientosSeleccionados.push({
                key: key,
                numero: numero,
                piso: piso,
                tipo: tipo
            });
        }

        actualizarResumenDinamico();
    }

    /**
     * Actualizar resumen
     */
    function actualizarResumenDinamico() {
        const cantidad = mapaDinamico.asientosSeleccionados.length;
        const total = cantidad * mapaDinamico.precioUnitario;

        document.getElementById('resumenCantidadDinamico').textContent = cantidad;

        const asientosTexto = cantidad > 0 ?
            mapaDinamico.asientosSeleccionados.map(a => a.numero).join(', ') :
            '-';
        document.getElementById('resumenAsientosDinamico').textContent = asientosTexto;

        document.getElementById('totalDinamico').textContent = total.toFixed(2);

        const btnConfirmar = document.getElementById('btnConfirmarDinamico');
        btnConfirmar.disabled = cantidad === 0;

        // Actualizar campo oculto
        document.getElementById('asientosSeleccionadosDinamico').value = JSON.stringify(mapaDinamico.asientosSeleccionados);
    }

    /**
     * Resetear selección
     */
    function resetearSeleccionDinamica() {
        document.querySelectorAll('.seat-dinamico.seleccionado').forEach(asiento => {
            asiento.classList.remove('seleccionado');
            asiento.classList.add('disponible');
        });

        mapaDinamico.asientosSeleccionados = [];
        actualizarResumenDinamico();
    }

    /**
     * Confirmar reserva
     */
    function confirmarReservaDinamica() {
        if (mapaDinamico.asientosSeleccionados.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin asientos',
                text: 'Debe seleccionar al menos un asiento'
            });
            return;
        }

        const cantidad = mapaDinamico.asientosSeleccionados.length;
        const total = cantidad * mapaDinamico.precioUnitario;
        const asientos = mapaDinamico.asientosSeleccionados.map(a => a.numero).join(', ');

        Swal.fire({
            title: 'Confirmar Reserva',
            html: `
            <div class="text-start">
                <p><strong>Asientos:</strong> ${asientos}</p>
                <p><strong>Cantidad:</strong> ${cantidad}</p>
                <p><strong>Total:</strong> Bs. ${total.toFixed(2)}</p>
            </div>
        `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0d6efd'
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('Asientos reservados:', mapaDinamico.asientosSeleccionados);

                Swal.fire({
                    icon: 'success',
                    title: 'Reserva Confirmada',
                    text: 'Sus asientos han sido reservados exitosamente'
                });
            }
        });
    }

    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        actualizarResumenDinamico();
    });
</script>