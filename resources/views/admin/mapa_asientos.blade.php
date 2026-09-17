@extends('layouts.app')

@section('content')
<?php

/**
 * Vista: Mapa de Asientos
 * Descripción: Vista principal para mostrar el mapa de asientos de un bus
 * Nota: Esta vista ahora usa el componente dinámico que genera el layout automáticamente
 */

// Validar que existan los datos del bus
if (!isset($data['busData'])) {
    echo '<div class="alert alert-danger">Error: No se proporcionaron datos del bus</div>';
    return;
}

// Pasar los datos al componente dinámico
$busData = $data['busData'];
$asientos = $data['asientos'] ?? null;
?>

<!-- Contenedor Principal -->
<div class="app-content-wrapper">
    <div class="app-content">
        <div class="container-fluid">

            <!-- Breadcrumb -->
            <div class="row mb-3">
                <div class="col-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="<?php echo URLROOT; ?>/dashboard">Inicio</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="<?php echo URLROOT; ?>/admin/tipos_buses">Tipos de Buses</a>
                            </li>
                            <li class="breadcrumb-item active">Mapa de Asientos</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mb-0">
                            <i class="bi bi-diagram-3-fill me-2"></i>
                            Mapa de Asientos
                        </h1>
                        <div>
                            <a href="<?php echo URLROOT; ?>/admin/tipos_buses" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Componente Dinámico del Mapa -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <?php
                            // Incluir el componente dinámico
                            include __DIR__ . '/componentes/mapa_asientos_dinamico.php';
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Información del Bus
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Nombre:</th>
                                    <td><?php echo htmlspecialchars($busData['nombre']); ?></td>
                                </tr>
                                <tr>
                                    <th>Capacidad Total:</th>
                                    <td><?php echo $busData['capacidad']; ?> asientos</td>
                                </tr>
                                <tr>
                                    <th>Número de Pisos:</th>
                                    <td><?php echo $busData['pisos']; ?></td>
                                </tr>
                                <tr>
                                    <th>ID del Tipo:</th>
                                    <td>#<?php echo $busData['id']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <i class="bi bi-gear-fill me-2"></i>
                            Acciones
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" onclick="generarAsientosAutomaticamente()">
                                    <i class="bi bi-magic me-2"></i>
                                    Generar Asientos Automáticamente
                                </button>
                                <button class="btn btn-outline-info" onclick="verEstadisticas()">
                                    <i class="bi bi-bar-chart-fill me-2"></i>
                                    Ver Estadísticas
                                </button>
                                <button class="btn btn-outline-warning" onclick="exportarConfiguracion()">
                                    <i class="bi bi-download me-2"></i>
                                    Exportar Configuración
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    /**
     * Generar asientos automáticamente
     */
    function generarAsientosAutomaticamente() {
        const busId = <?php echo $busData['id']; ?>;

        Swal.fire({
            title: '¿Generar asientos?',
            text: 'Esto creará automáticamente los asientos para este tipo de bus',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0d6efd'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Generando...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Llamar al backend
                fetch(`<?php echo URLROOT; ?>/admin/generar_asientos/${busId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Asientos Generados',
                                text: `Se generaron ${data.total} asientos correctamente`,
                                confirmButtonText: 'Recargar'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al generar asientos: ' + error
                        });
                    });
            }
        });
    }

    /**
     * Ver estadísticas
     */
    function verEstadisticas() {
        const busId = <?php echo $busData['id']; ?>;

        fetch(`<?php echo URLROOT; ?>/admin/estadisticas_asientos/${busId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.estadisticas;
                    Swal.fire({
                        title: 'Estadísticas de Asientos',
                        html: `
                        <div class="text-start">
                            <p><strong>Total de asientos:</strong> ${stats.total_asientos || 0}</p>
                            <p><strong>Asientos Premium:</strong> ${stats.asientos_premium || 0}</p>
                            <p><strong>Asientos Normales:</strong> ${stats.asientos_normales || 0}</p>
                            <p><strong>Piso 1:</strong> ${stats.asientos_piso1 || 0}</p>
                            <p><strong>Piso 2:</strong> ${stats.asientos_piso2 || 0}</p>
                        </div>
                    `,
                        icon: 'info'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al obtener estadísticas'
                });
            });
    }

    /**
     * Exportar configuración
     */
    function exportarConfiguracion() {
        const config = {
            bus: <?php echo json_encode($busData); ?>,
            asientos: mapaDinamico.asientosSeleccionados
        };

        const dataStr = JSON.stringify(config, null, 2);
        const dataBlob = new Blob([dataStr], {
            type: 'application/json'
        });
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `configuracion_bus_${config.bus.id}.json`;
        link.click();

        Swal.fire({
            icon: 'success',
            title: 'Exportado',
            text: 'Configuración exportada correctamente',
            timer: 2000
        });
    }
</script>


<style>
    /* ========================================
   ESTILOS DEL MAPA DE ASIENTOS
   ======================================== */

    .seat-map-container {
        padding: 1.5rem;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 1rem;
    }

    /* Chasis del Bus */
    .bus-chassis {
        background: linear-gradient(180deg, #e0e0e0 0%, #f5f5f5 100%);
        border: 4px solid #5a5a5a;
        border-radius: 3rem;
        padding: 2rem 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        position: relative;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Cabina del Conductor */
    .bus-cabin {
        background: linear-gradient(180deg, #2c2c2c 0%, #1a1a1a 100%);
        border-radius: 2rem 2rem 0 0;
        padding: 1rem;
        margin: -2rem -1.5rem 1.5rem -1.5rem;
        display: grid;
        grid-template-columns: 1fr 2fr 1fr;
        gap: 0.5rem;
        align-items: center;
    }

    .steering-wheel {
        display: flex;
        justify-content: center;
        align-items: center;
        color: #888;
        font-size: 2rem;
    }

    .cabin-seats,
    .cabin-right {
        display: flex;
        justify-content: center;
    }

    /* Grid de Asientos */
    .seats-grid {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .seats-grid.piso2 {
        padding-top: 2rem;
    }

    .seat-row {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        align-items: center;
    }

    /* Pasillo */
    .aisle {
        width: 40px;
        height: 60px;
        background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.3) 50%, transparent 100%);
        border-left: 2px dashed rgba(0, 0, 0, 0.1);
        border-right: 2px dashed rgba(0, 0, 0, 0.1);
    }

    /* Asiento Individual */
    .seat-item {
        position: relative;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .seat-item:hover:not(.ocupado) {
        transform: scale(1.1);
    }

    /* Parte Principal del Asiento */
    .seat-main {
        width: 50px;
        height: 50px;
        border-radius: 0.75rem;
        position: relative;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    /* Apoyabrazos */
    .seat-armrest {
        position: absolute;
        width: 8px;
        height: 40px;
        background: #4a4a4a;
        border-radius: 4px;
        top: 5px;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .seat-armrest.left {
        left: -10px;
    }

    .seat-armrest.right {
        right: -10px;
    }

    /* Número del Asiento */
    .seat-number {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-weight: bold;
        font-size: 0.875rem;
        color: white;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        z-index: 10;
        pointer-events: none;
    }

    /* Estados de Asientos */
    .seat-item.disponible .seat-main {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: 2px solid #1d4ed8;
    }

    .seat-item.ocupado .seat-main {
        background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
        border: 2px solid #4b5563;
        cursor: not-allowed;
    }

    .seat-item.seleccionado .seat-main {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: 2px solid #047857;
        animation: pulse 1.5s ease-in-out infinite;
    }

    .seat-item.premium .seat-main {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border: 2px solid #b45309;
    }

    @keyframes pulse {

        0%,
        100% {
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
        }

        50% {
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.8);
        }
    }

    /* Sección Trasera del Bus */
    .bus-back-section {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 2px dashed rgba(0, 0, 0, 0.1);
    }

    .bathroom,
    .stairs,
    .bedroom {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        border: 2px solid #334155;
        border-radius: 0.75rem;
        padding: 1rem;
        text-align: center;
        color: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .bathroom i,
    .stairs i,
    .bedroom i {
        font-size: 1.5rem;
    }

    .bathroom span,
    .stairs span,
    .bedroom span {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* Leyenda */
    .seat-legend {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .seat-sample {
        width: 40px;
        height: 40px;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .seat-sample.disponible {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: 2px solid #1d4ed8;
    }

    .seat-sample.ocupado {
        background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
        border: 2px solid #4b5563;
    }

    .seat-sample.seleccionado {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: 2px solid #047857;
    }

    /* Tarjeta de Resumen */
    .summary-card {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 2px solid #0ea5e9;
        border-radius: 1rem;
        padding: 1.5rem;
    }

    .summary-content {
        font-size: 0.95rem;
    }

    /* Tabs de Pisos */
    #pisoTabs .btn {
        font-weight: 600;
        transition: all 0.3s ease;
    }

    #pisoTabs .btn.active {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border-color: #1d4ed8;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .bus-chassis {
            padding: 1.5rem 1rem;
            border-radius: 2rem;
        }

        .seat-main {
            width: 40px;
            height: 40px;
        }

        .seat-number {
            font-size: 0.75rem;
        }

        .aisle {
            width: 30px;
        }
    }
</style>

<script>
    // ========================================
    // JAVASCRIPT PARA INTERACTIVIDAD
    // ========================================

    // Variables globales
    let asientosSeleccionados = [];
    const precioPorAsiento = 50.00;

    /**
     * Cambiar entre pisos del mapa
     */
    function cambiarPisoMapa(numeroPiso) {
        // Ocultar todos los mapas
        document.getElementById('mapaPiso1').style.display = 'none';
        document.getElementById('mapaPiso2').style.display = 'none';

        // Mostrar el mapa seleccionado
        document.getElementById('mapaPiso' + numeroPiso).style.display = 'block';

        // Actualizar tabs
        document.getElementById('tabPiso1').classList.remove('active');
        document.getElementById('tabPiso2').classList.remove('active');
        document.getElementById('tabPiso' + numeroPiso).classList.add('active');
    }

    /**
     * Seleccionar/Deseleccionar asiento
     */
    function seleccionarAsiento(elemento) {
        // Si está ocupado, no hacer nada
        if (elemento.classList.contains('ocupado')) {
            Swal.fire({
                icon: 'warning',
                title: 'Asiento Ocupado',
                text: 'Este asiento ya está reservado',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        const numeroAsiento = elemento.getAttribute('data-seat');
        const piso = elemento.getAttribute('data-piso');

        // Toggle selección
        if (elemento.classList.contains('seleccionado')) {
            // Deseleccionar
            elemento.classList.remove('seleccionado');
            elemento.classList.add('disponible');

            // Remover del array
            asientosSeleccionados = asientosSeleccionados.filter(
                a => !(a.numero === numeroAsiento && a.piso === piso)
            );
        } else {
            // Seleccionar
            elemento.classList.remove('disponible');
            elemento.classList.add('seleccionado');

            // Agregar al array
            asientosSeleccionados.push({
                numero: numeroAsiento,
                piso: piso
            });
        }

        // Actualizar resumen
        actualizarResumen();
    }

    /**
     * Actualizar el resumen de compra
     */
    function actualizarResumen() {
        const cantidad = asientosSeleccionados.length;
        const total = cantidad * precioPorAsiento;

        document.getElementById('cantidadAsientos').textContent = cantidad;
        document.getElementById('totalPagar').textContent = total.toFixed(2);
    }

    /**
     * Inicializar mapa según número de pisos
     */
    function inicializarMapaAsientos(numeroPisos) {
        if (numeroPisos === 2) {
            document.getElementById('tabPiso2').style.display = 'block';
        } else {
            document.getElementById('tabPiso2').style.display = 'none';
        }

        // Resetear selección
        asientosSeleccionados = [];
        actualizarResumen();

        // Mostrar piso 1 por defecto
        cambiarPisoMapa(1);
    }

    /**
     * Obtener asientos seleccionados (para enviar al backend)
     */
    function obtenerAsientosSeleccionados() {
        return asientosSeleccionados;
    }

    // Inicializar al cargar
    document.addEventListener('DOMContentLoaded', function() {
        // Por defecto, inicializar con 1 piso
        inicializarMapaAsientos(1);
    });
</script>
@endsection
