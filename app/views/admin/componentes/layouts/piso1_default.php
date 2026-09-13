<!-- Layout por defecto para Piso 1 -->
<div class="bus-chassis-layout piso1">
    <!-- Cabina del Conductor -->
    <div class="bus-cabin-section">
        <div class="steering-area">
            <div class="steering-wheel-icon">
                <i class="bi bi-circle-fill"></i>
            </div>
        </div>
        <div class="cabin-premium-seats">
            <!-- Asiento Premium 1 -->
            <div class="seat-item-selector premium ocupado" data-asiento="1" data-piso="1">
                <div class="seat-body"></div>
                <div class="seat-arm left"></div>
                <div class="seat-arm right"></div>
                <span class="seat-label">1</span>
            </div>
        </div>
        <div class="cabin-premium-seats-right">
            <!-- Asiento Premium 2 -->
            <div class="seat-item-selector premium ocupado" data-asiento="2" data-piso="1">
                <div class="seat-body"></div>
                <div class="seat-arm left"></div>
                <div class="seat-arm right"></div>
                <span class="seat-label">2</span>
            </div>
        </div>
    </div>

    <!-- Grid Principal de Asientos -->
    <div class="seats-grid-layout">
        <?php
        // Configuración del grid: 7 filas x 5 columnas (2-pasillo-2)
        $filas = 7;
        $asientoInicio = 3;
        $asientoActual = $asientoInicio;

        // Estados de ejemplo (en producción vendrían de la BD)
        $estadosEjemplo = ['disponible', 'disponible', 'disponible', 'ocupado', 'disponible'];

        for ($fila = 0; $fila < $filas; $fila++):
        ?>
            <div class="seat-row-layout">
                <!-- Columna Izquierda 1 -->
                <div class="seat-item-selector <?php echo $estadosEjemplo[array_rand($estadosEjemplo)]; ?>"
                    data-asiento="<?php echo $asientoActual; ?>"
                    data-piso="1"
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
                    data-piso="1"
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
                    data-piso="1"
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
                    data-piso="1"
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

    <!-- Sección Trasera: Servicios -->
    <div class="bus-services-section">
        <div class="service-item bathroom">
            <i class="bi bi-door-closed-fill"></i>
            <span>BAÑO</span>
        </div>
        <div class="service-item stairs">
            <i class="bi bi-arrow-up-circle-fill"></i>
            <span>ESCALERA</span>
        </div>
        <div class="service-item bedroom">
            <i class="bi bi-moon-stars-fill"></i>
            <span>DORMITORIO</span>
        </div>
    </div>
</div>

<style>
    /* ========================================
   ESTILOS LAYOUT PISO 1
   ======================================== */

    .bus-chassis-layout {
        background: linear-gradient(180deg, #e8e8e8 0%, #f5f5f5 100%);
        border: 5px solid #5a5a5a;
        border-radius: 3rem;
        padding: 2rem 1.5rem;
        box-shadow:
            0 10px 30px rgba(0, 0, 0, 0.2),
            inset 0 2px 4px rgba(255, 255, 255, 0.5);
        position: relative;
    }

    /* Cabina del Conductor */
    .bus-cabin-section {
        background: linear-gradient(180deg, #2c2c2c 0%, #1a1a1a 100%);
        border-radius: 2.5rem 2.5rem 0 0;
        padding: 1.25rem;
        margin: -2rem -1.5rem 2rem -1.5rem;
        display: grid;
        grid-template-columns: 80px 1fr 1fr;
        gap: 1rem;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }

    .steering-area {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .steering-wheel-icon {
        width: 50px;
        height: 50px;
        border: 4px solid #888;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #666;
        font-size: 1.5rem;
        background: radial-gradient(circle, #444 0%, #222 100%);
    }

    .cabin-premium-seats,
    .cabin-premium-seats-right {
        display: flex;
        justify-content: center;
    }

    /* Grid de Asientos */
    .seats-grid-layout {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 0 0.5rem;
    }

    .seat-row-layout {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.75rem;
    }

    /* Pasillo */
    .aisle-space {
        width: 50px;
        height: 65px;
        background: linear-gradient(90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.2) 20%,
                rgba(255, 255, 255, 0.4) 50%,
                rgba(255, 255, 255, 0.2) 80%,
                transparent 100%);
        border-left: 2px dashed rgba(0, 0, 0, 0.15);
        border-right: 2px dashed rgba(0, 0, 0, 0.15);
        position: relative;
    }

    .aisle-space::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 2px;
        height: 80%;
        background: rgba(0, 0, 0, 0.1);
    }

    /* Asiento Individual */
    .seat-item-selector {
        position: relative;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
        user-select: none;
    }

    .seat-item-selector:hover:not(.ocupado) {
        transform: scale(1.15) translateY(-3px);
    }

    .seat-item-selector:active:not(.ocupado) {
        transform: scale(1.05);
    }

    /* Cuerpo del Asiento */
    .seat-body {
        width: 55px;
        height: 55px;
        border-radius: 0.875rem;
        position: relative;
        box-shadow:
            0 4px 8px rgba(0, 0, 0, 0.25),
            inset 0 2px 4px rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }

    /* Apoyabrazos */
    .seat-arm {
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

    .seat-arm.left {
        left: -12px;
    }

    .seat-arm.right {
        right: -12px;
    }

    /* Número del Asiento */
    .seat-label {
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

    /* Estados de Asientos */
    .seat-item-selector.disponible .seat-body {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: 3px solid #1d4ed8;
    }

    .seat-item-selector.disponible:hover .seat-body {
        background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        box-shadow:
            0 6px 16px rgba(59, 130, 246, 0.4),
            inset 0 2px 4px rgba(255, 255, 255, 0.3);
    }

    .seat-item-selector.ocupado .seat-body {
        background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
        border: 3px solid #4b5563;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .seat-item-selector.seleccionado .seat-body {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: 3px solid #047857;
        animation: pulseGlow 1.5s ease-in-out infinite;
    }

    .seat-item-selector.premium .seat-body {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border: 3px solid #b45309;
    }

    @keyframes pulseGlow {

        0%,
        100% {
            box-shadow:
                0 4px 8px rgba(16, 185, 129, 0.4),
                0 0 0 0 rgba(16, 185, 129, 0.7);
        }

        50% {
            box-shadow:
                0 4px 20px rgba(16, 185, 129, 0.6),
                0 0 0 8px rgba(16, 185, 129, 0);
        }
    }

    /* Sección de Servicios */
    .bus-services-section {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 3px dashed rgba(0, 0, 0, 0.15);
    }

    .service-item {
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
        transition: all 0.3s ease;
    }

    .service-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    }

    .service-item i {
        font-size: 2rem;
    }

    .service-item span {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .service-item.bathroom {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        border-color: #0e7490;
    }

    .service-item.stairs {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        border-color: #6d28d9;
    }

    .service-item.bedroom {
        background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
        border-color: #be185d;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .bus-chassis-layout {
            padding: 1.5rem 1rem;
            border-radius: 2rem;
        }

        .seat-body {
            width: 45px;
            height: 45px;
        }

        .seat-label {
            font-size: 0.8rem;
        }

        .aisle-space {
            width: 35px;
        }

        .bus-services-section {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
    }
</style>