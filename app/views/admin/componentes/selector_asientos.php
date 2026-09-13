<!-- 
    Componente: Selector de Asientos Dinámico
    Uso: Incluir este archivo y pasar los datos del bus
    Ejemplo: include 'componentes/selector_asientos.php';
-->

<?php
// Datos que deben ser pasados al componente:
// $busData = [
//     'id' => 1,
//     'nombre' => 'Bus de 49 asientos',
//     'capacidad' => 49,
//     'pisos' => 2,
//     'configuracion_asientos' => '...' // JSON con la configuración
// ];

// Si no se pasan datos, usar valores por defecto
if (!isset($busData)) {
    $busData = [
        'id' => 0,
        'nombre' => 'Bus Estándar',
        'capacidad' => 35,
        'pisos' => 1,
        'configuracion_asientos' => null
    ];
}

// Decodificar configuración si existe
$configuracion = null;
if ($busData['configuracion_asientos']) {
    $configuracion = json_decode($busData['configuracion_asientos'], true);
}
?>

<div class="selector-asientos-wrapper" id="selectorAsientos">
    <!-- Header con información del bus -->
    <div class="bus-info-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-1">
                    <i class="bi bi-bus-front-fill text-primary me-2"></i>
                    <?php echo htmlspecialchars($busData['nombre']); ?>
                </h5>
                <p class="text-muted mb-0">
                    <i class="bi bi-people-fill me-1"></i><?php echo $busData['capacidad']; ?> asientos
                    <span class="mx-2">|</span>
                    <i class="bi bi-layers-fill me-1"></i><?php echo $busData['pisos']; ?> piso(s)
                </p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-sm btn-outline-secondary" onclick="resetearSeleccion()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Limpiar Selección
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs para Pisos (solo si hay 2 pisos) -->
    <?php if ($busData['pisos'] == 2): ?>
        <div class="mb-4" id="pisoTabsSelector">
            <div class="btn-group w-100" role="group">
                <button type="button" class="btn btn-lg btn-outline-primary active" id="tabSelectorPiso1" onclick="cambiarPisoSelector(1)">
                    <i class="bi bi-1-circle-fill me-2"></i>Piso 1
                </button>
                <button type="button" class="btn btn-lg btn-outline-primary" id="tabSelectorPiso2" onclick="cambiarPisoSelector(2)">
                    <i class="bi bi-2-circle-fill me-2"></i>Piso 2
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contenedor Principal del Bus -->
    <div class="bus-container-main">
        <!-- PISO 1 -->
        <div id="selectorMapaPiso1" class="bus-floor-map">
            <?php include __DIR__ . '/piso_bus_layout.php';
            $pisoActual = 1;
            renderPisoBus($pisoActual, $busData, $configuracion);
            ?>
        </div>

        <!-- PISO 2 (si existe) -->
        <?php if ($busData['pisos'] == 2): ?>
            <div id="selectorMapaPiso2" class="bus-floor-map" style="display: none;">
                <?php
                $pisoActual = 2;
                renderPisoBus($pisoActual, $busData, $configuracion);
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Panel de Resumen y Leyenda -->
    <div class="row mt-4">
        <!-- Leyenda -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="bi bi-info-circle-fill text-info me-2"></i>Leyenda
                    </h6>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="legend-item-selector">
                            <div class="seat-preview disponible"></div>
                            <span class="ms-2">Disponible</span>
                        </div>
                        <div class="legend-item-selector">
                            <div class="seat-preview ocupado"></div>
                            <span class="ms-2">Ocupado</span>
                        </div>
                        <div class="legend-item-selector">
                            <div class="seat-preview seleccionado"></div>
                            <span class="ms-2">Seleccionado</span>
                        </div>
                        <div class="legend-item-selector">
                            <div class="seat-preview premium"></div>
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
                    <h6 class="card-title mb-3">
                        <i class="bi bi-cart-check-fill text-primary me-2"></i>Resumen de Compra
                    </h6>
                    <div class="summary-details">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Asientos seleccionados:</span>
                            <strong class="text-dark" id="resumenCantidad">0</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Asientos:</span>
                            <strong class="text-dark" id="resumenAsientos">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Precio unitario:</span>
                            <strong class="text-dark">Bs. <span id="resumenPrecioUnitario">50.00</span></strong>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h6 mb-0">Total a Pagar:</span>
                            <strong class="h4 mb-0 text-primary">Bs. <span id="resumenTotal">0.00</span></strong>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 mt-3" onclick="confirmarReserva()" id="btnConfirmarReserva" disabled>
                        <i class="bi bi-check-circle-fill me-2"></i>Confirmar Reserva
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Campo oculto para almacenar los asientos seleccionados -->
<input type="hidden" id="asientosSeleccionadosInput" name="asientos_seleccionados" value="">

<style>
    /* ========================================
   ESTILOS DEL SELECTOR DE ASIENTOS
   ======================================== */

    .selector-asientos-wrapper {
        padding: 1.5rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 1rem;
    }

    .bus-info-header {
        background: white;
        padding: 1.5rem;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .bus-container-main {
        background: white;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .bus-floor-map {
        max-width: 700px;
        margin: 0 auto;
    }

    /* Tabs de Pisos */
    #pisoTabsSelector .btn {
        font-weight: 600;
        font-size: 1.1rem;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
    }

    #pisoTabsSelector .btn.active {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
        border-color: #0a58ca;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }

    /* Leyenda */
    .legend-item-selector {
        display: flex;
        align-items: center;
    }

    .seat-preview {
        width: 35px;
        height: 35px;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    .seat-preview.disponible {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: 2px solid #1d4ed8;
    }

    .seat-preview.ocupado {
        background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
        border: 2px solid #4b5563;
    }

    .seat-preview.seleccionado {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: 2px solid #047857;
    }

    .seat-preview.premium {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border: 2px solid #b45309;
    }

    /* Resumen */
    .summary-details {
        font-size: 1rem;
    }

    /* Animaciones */
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .bus-floor-map {
        animation: slideIn 0.4s ease-out;
    }
</style>

<script>
    // ========================================
    // JAVASCRIPT DEL SELECTOR DE ASIENTOS
    // ========================================

    // Estado global del selector
    const selectorState = {
        asientosSeleccionados: [],
        precioUnitario: 50.00,
        pisoActual: 1
    };

    /**
     * Cambiar entre pisos
     */
    function cambiarPisoSelector(numeroPiso) {
        // Ocultar todos los mapas
        document.getElementById('selectorMapaPiso1').style.display = 'none';
        const piso2 = document.getElementById('selectorMapaPiso2');
        if (piso2) {
            piso2.style.display = 'none';
        }

        // Mostrar el piso seleccionado
        document.getElementById('selectorMapaPiso' + numeroPiso).style.display = 'block';

        // Actualizar tabs
        document.getElementById('tabSelectorPiso1').classList.remove('active');
        const tab2 = document.getElementById('tabSelectorPiso2');
        if (tab2) {
            tab2.classList.remove('active');
        }
        document.getElementById('tabSelectorPiso' + numeroPiso).classList.add('active');

        selectorState.pisoActual = numeroPiso;
    }

    /**
     * Seleccionar/Deseleccionar asiento
     */
    function toggleAsientoSelector(elemento) {
        // Verificar si está ocupado
        if (elemento.classList.contains('ocupado')) {
            Swal.fire({
                icon: 'error',
                title: 'Asiento No Disponible',
                text: 'Este asiento ya está reservado por otro pasajero',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
            return;
        }

        const numeroAsiento = elemento.getAttribute('data-asiento');
        const pisoAsiento = elemento.getAttribute('data-piso');
        const asientoKey = `${pisoAsiento}-${numeroAsiento}`;

        // Toggle selección
        if (elemento.classList.contains('seleccionado')) {
            // Deseleccionar
            elemento.classList.remove('seleccionado');
            elemento.classList.add('disponible');

            // Remover del array
            selectorState.asientosSeleccionados = selectorState.asientosSeleccionados.filter(
                a => a.key !== asientoKey
            );
        } else {
            // Seleccionar
            elemento.classList.remove('disponible');
            elemento.classList.add('seleccionado');

            // Agregar al array
            selectorState.asientosSeleccionados.push({
                key: asientoKey,
                numero: numeroAsiento,
                piso: pisoAsiento
            });
        }

        // Actualizar UI
        actualizarResumenSelector();
    }

    /**
     * Actualizar resumen de compra
     */
    function actualizarResumenSelector() {
        const cantidad = selectorState.asientosSeleccionados.length;
        const total = cantidad * selectorState.precioUnitario;

        // Actualizar cantidad
        document.getElementById('resumenCantidad').textContent = cantidad;

        // Actualizar lista de asientos
        const asientosTexto = cantidad > 0 ?
            selectorState.asientosSeleccionados.map(a => a.numero).join(', ') :
            '-';
        document.getElementById('resumenAsientos').textContent = asientosTexto;

        // Actualizar total
        document.getElementById('resumenTotal').textContent = total.toFixed(2);

        // Habilitar/deshabilitar botón de confirmar
        const btnConfirmar = document.getElementById('btnConfirmarReserva');
        if (cantidad > 0) {
            btnConfirmar.disabled = false;
            btnConfirmar.classList.add('pulse-animation');
        } else {
            btnConfirmar.disabled = true;
            btnConfirmar.classList.remove('pulse-animation');
        }

        // Actualizar campo oculto
        document.getElementById('asientosSeleccionadosInput').value = JSON.stringify(selectorState.asientosSeleccionados);
    }

    /**
     * Resetear selección
     */
    function resetearSeleccion() {
        // Confirmar con el usuario
        Swal.fire({
            title: '¿Limpiar selección?',
            text: 'Se deseleccionarán todos los asientos',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                // Deseleccionar todos los asientos
                document.querySelectorAll('.seat-item-selector.seleccionado').forEach(asiento => {
                    asiento.classList.remove('seleccionado');
                    asiento.classList.add('disponible');
                });

                // Limpiar array
                selectorState.asientosSeleccionados = [];

                // Actualizar UI
                actualizarResumenSelector();

                Swal.fire({
                    icon: 'success',
                    title: 'Selección limpiada',
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        });
    }

    /**
     * Confirmar reserva
     */
    function confirmarReserva() {
        if (selectorState.asientosSeleccionados.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin asientos seleccionados',
                text: 'Por favor seleccione al menos un asiento',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        const cantidad = selectorState.asientosSeleccionados.length;
        const total = cantidad * selectorState.precioUnitario;
        const asientos = selectorState.asientosSeleccionados.map(a => a.numero).join(', ');

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
                // Aquí iría la lógica para enviar al backend
                console.log('Asientos reservados:', selectorState.asientosSeleccionados);

                Swal.fire({
                    icon: 'success',
                    title: 'Reserva Confirmada',
                    text: 'Sus asientos han sido reservados exitosamente',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    }

    // Inicializar al cargar
    document.addEventListener('DOMContentLoaded', function() {
        actualizarResumenSelector();
    });
</script>

<?php
/**
 * Función para renderizar el layout de un piso
 */
function renderPisoBus($piso, $busData, $configuracion)
{
    // Si hay configuración personalizada, usarla
    if ($configuracion && isset($configuracion['piso' . $piso])) {
        renderPisoPersonalizado($piso, $configuracion['piso' . $piso]);
    } else {
        // Usar layout por defecto
        renderPisoDefecto($piso, $busData);
    }
}

/**
 * Renderizar piso con configuración por defecto
 */
function renderPisoDefecto($piso, $busData)
{
    // Configuración por defecto según el piso
    if ($piso == 1) {
        include __DIR__ . '/layouts/piso1_default.php';
    } else {
        include __DIR__ . '/layouts/piso2_default.php';
    }
}

/**
 * Renderizar piso con configuración personalizada
 */
function renderPisoPersonalizado($piso, $configPiso)
{
    // Implementar lógica para renderizar según configuración JSON
    echo "<!-- Piso $piso con configuración personalizada -->";
    // TODO: Implementar renderizado dinámico
}
?>