<!-- Layout por defecto para Piso 2 -->
<div class="bus-chassis-layout piso2">
    <!-- Grid Principal de Asientos Piso 2 -->
    <div class="seats-grid-layout piso2-grid">
        <?php
        // Configuración del grid Piso 2: 8 filas x 4 columnas (2-pasillo-2)
        $filas = 8;
        $asientoInicio = 31; // Continúa desde el piso 1
        $asientoActual = $asientoInicio;

        // Estados de ejemplo
        $estadosEjemplo = ['disponible', 'disponible', 'disponible', 'ocupado', 'disponible'];

        for ($fila = 0; $fila < $filas; $fila++):
        ?>
            <div class="seat-row-layout">
                <!-- Columna Izquierda 1 -->
                <div class="seat-item-selector <?php echo $estadosEjemplo[array_rand($estadosEjemplo)]; ?>"
                    data-asiento="<?php echo $asientoActual; ?>"
                    data-piso="2"
                    onclick="toggleAsientoSelector(this)">
                    <div class="seat-body"></div>
                    <div class="seat-arm left"></div>
                    <div class="seat-arm right"></div>
                    <span class="seat-label"><?php echo $asientoActual; ?></span>
                </div>
                <?php $asientoActual++; ?>

                <!-- Columna Izquierda 2 -->
                <div class="seat-item-selector <?php echo $estadosEjemplo[array_rand($estadosEjemplo)]; ?>"
                    data-asiento="<?php echo $asientoActual; ?>"
                    data-piso="2"
                    onclick="toggleAsientoSelector(this)">
                    <div class="seat-body"></div>
                    <div class="seat-arm left"></div>
                    <div class="seat-arm right"></div>
                    <span class="seat-label"><?php echo $asientoActual; ?></span>
                </div>
                <?php $asientoActual++; ?>

                <!-- Pasillo Central -->
                <div class="aisle-space"></div>

                <!-- Columna Derecha 1 -->
                <div class="seat-item-selector <?php echo $estadosEjemplo[array_rand($estadosEjemplo)]; ?>"
                    data-asiento="<?php echo $asientoActual; ?>"
                    data-piso="2"
                    onclick="toggleAsientoSelector(this)">
                    <div class="seat-body"></div>
                    <div class="seat-arm left"></div>
                    <div class="seat-arm right"></div>
                    <span class="seat-label"><?php echo $asientoActual; ?></span>
                </div>
                <?php $asientoActual++; ?>

                <!-- Columna Derecha 2 -->
                <div class="seat-item-selector <?php echo $estadosEjemplo[array_rand($estadosEjemplo)]; ?>"
                    data-asiento="<?php echo $asientoActual; ?>"
                    data-piso="2"
                    onclick="toggleAsientoSelector(this)">
                    <div class="seat-body"></div>
                    <div class="seat-arm left"></div>
                    <div class="seat-arm right"></div>
                    <span class="seat-label"><?php echo $asientoActual; ?></span>
                </div>
                <?php $asientoActual++; ?>
            </div>
        <?php endfor; ?>
    </div>

    <!-- Indicador de Piso 2 -->
    <div class="floor-indicator">
        <i class="bi bi-2-circle-fill"></i>
        <span>PISO 2</span>
    </div>
</div>

<style>
    /* ========================================
   ESTILOS LAYOUT PISO 2
   ======================================== */

    .bus-chassis-layout.piso2 {
        background: linear-gradient(180deg, #e0e7ff 0%, #f5f5f5 100%);
        border: 5px solid #5a5a5a;
        border-radius: 3rem;
        padding: 2.5rem 1.5rem;
        box-shadow:
            0 10px 30px rgba(0, 0, 0, 0.2),
            inset 0 2px 4px rgba(255, 255, 255, 0.5);
        position: relative;
    }

    /* Grid de Asientos Piso 2 */
    .piso2-grid {
        padding: 1rem 0.5rem;
    }

    /* Indicador de Piso */
    .floor-indicator {
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

    .floor-indicator i {
        font-size: 1.5rem;
    }

    /* Variación de colores para Piso 2 */
    .piso2 .seat-item-selector.disponible .seat-body {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        border: 3px solid #0e7490;
    }

    .piso2 .seat-item-selector.disponible:hover .seat-body {
        background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
        box-shadow:
            0 6px 16px rgba(6, 182, 212, 0.4),
            inset 0 2px 4px rgba(255, 255, 255, 0.3);
    }

    .piso2 .seat-item-selector.seleccionado .seat-body {
        background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
        border: 3px solid #0f766e;
        animation: pulseGlowPiso2 1.5s ease-in-out infinite;
    }

    @keyframes pulseGlowPiso2 {

        0%,
        100% {
            box-shadow:
                0 4px 8px rgba(20, 184, 166, 0.4),
                0 0 0 0 rgba(20, 184, 166, 0.7);
        }

        50% {
            box-shadow:
                0 4px 20px rgba(20, 184, 166, 0.6),
                0 0 0 8px rgba(20, 184, 166, 0);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .bus-chassis-layout.piso2 {
            padding: 2rem 1rem;
        }

        .floor-indicator {
            top: 0.5rem;
            right: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .floor-indicator i {
            font-size: 1.2rem;
        }
    }
</style>