@extends('layouts.app')

@section('content')
<!-- Content Wrapper -->
<div class="app-content-wrapper">
    <div class="app-content">
        <div class="container-fluid">
            <!-- Breadcrumb -->
            <div class="row mb-3">
                <div class="col-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo URLROOT; ?>/dashboard">Inicio</a></li>
                            <li class="breadcrumb-item">Procesos</li>
                            <li class="breadcrumb-item active">Tipos de Buses</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mb-0"><i class="bi bi-sliders me-2"></i>Tipos de Buses</h1>
                    </div>
                </div>
            </div>

            <!-- Cards de Tipos de Buses -->
            <div class="row g-4">
                <?php if (!empty($data['tiposBuses'])): ?>
                    <?php foreach ($data['tiposBuses'] as $tipo): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 shadow-sm hover-shadow" style="cursor: pointer;"
                                onclick="abrirModalTipoBus(<?php echo $tipo->id; ?>, '<?php echo htmlspecialchars($tipo->nombre); ?>', <?php echo $tipo->capacidad; ?>, <?php echo $tipo->pisos; ?>, '<?php echo htmlspecialchars($tipo->configuracion_asientos); ?>')">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <span class="tipo-bus-icono"><i class="bi bi-bus-front"></i></span>
                                    </div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($tipo->nombre); ?></h5>
                                    <p class="text-muted mb-2">
                                        <i class="bi bi-people-fill"></i> <?php echo $tipo->capacidad; ?> asientos
                                    </p>
                                    <p class="text-muted mb-0">
                                        <i class="bi bi-layers"></i> <?php echo $tipo->pisos; ?> piso(s)
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge <?php echo $tipo->estado ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $tipo->estado ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary me-1"
                                                onclick="event.stopPropagation(); editarTipoBus(<?php echo $tipo->id; ?>)"
                                                title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                onclick="event.stopPropagation(); eliminarTipoBus(<?php echo $tipo->id; ?>, '<?php echo htmlspecialchars($tipo->nombre); ?>')"
                                                title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Card para agregar nuevo tipo -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 shadow-sm border-dashed tarjeta-agregar-tipo" style="cursor: pointer;"
                        onclick="abrirModalNuevoTipoBus()">
                        <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                            <i class="bi bi-plus-circle"></i>
                            <h5 class="mt-3">Agregar Nuevo Tipo</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Tipo de Bus -->
<div class="modal fade" id="modalTipoBus" tabindex="-1" aria-labelledby="modalTipoBusLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTipoBusLabel">
                    <i class="bi bi-bus-front me-2"></i>Configurar Tipo de Bus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formTipoBus" action="<?php echo URLROOT; ?>/admin/guardar_tipo_bus" method="POST">
                    <input type="hidden" name="id" id="tipoBusId">
                    <input type="hidden" name="configuracion_asientos" id="configuracionAsientos">

                    <div class="row">
                        <!-- Columna Izquierda: Formulario -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="nombreTipoBus" class="form-label">Nombre del Tipo</label>
                                <input type="text" class="form-control" id="nombreTipoBus" name="nombre" required>
                            </div>

                            <div class="mb-3">
                                <label for="capacidadTipoBus" class="form-label">Capacidad Total</label>
                                <input type="number" class="form-control" id="capacidadTipoBus" name="capacidad" required>
                            </div>

                            <div class="mb-3">
                                <label for="pisosTipoBus" class="form-label">Número de Pisos</label>
                                <select class="form-select" id="pisosTipoBus" name="pisos" onchange="cambiarPiso()">
                                    <option value="1">1 Piso</option>
                                    <option value="2">2 Pisos</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Piso Actual</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary" id="btnPiso1" onclick="mostrarPiso(1)">Piso 1</button>
                                    <button type="button" class="btn btn-outline-primary" id="btnPiso2" onclick="mostrarPiso(2)" disabled>Piso 2</button>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <small>
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Instrucciones:</strong><br>
                                    - Seleccione el número de pisos del bus<br>
                                    - Use los botones para ver el diagrama de cada piso<br>
                                    - Ingrese la capacidad total de asientos
                                </small>
                            </div>
                        </div>

                        <!-- Columna Derecha: Diagrama de Bus DINÁMICO con Canvas -->
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-diagram-3-fill me-2"></i>Diseño de Asientos (Vista Previa)
                                </label>

                                <!-- Tabs de pisos (se muestran cuando hay 2 pisos) -->
                                <div id="floorPreviewTabs" style="display: none;">
                                    <div class="floor-preview-tabs">
                                        <div class="floor-preview-tab active" data-floor="1" onclick="cambiarPisoPreview(1)">
                                            <i class="bi bi-1-circle-fill me-1"></i> Piso 1
                                        </div>
                                        <div class="floor-preview-tab" data-floor="2" onclick="cambiarPisoPreview(2)">
                                            <i class="bi bi-2-circle-fill me-1"></i> Piso 2
                                        </div>
                                    </div>
                                </div>

                                <!-- Contenedor del Canvas -->
                                <div id="contenedorDiagramaDinamico" class="border rounded p-3 bg-light" style="min-height: 500px; max-height: 600px; overflow-y: auto;">
                                    <canvas id="canvasBusPreview"></canvas>
                                    <div id="mensajeInicial" class="text-center py-5">
                                        <i class="bi bi-bus-front" style="font-size: 4rem; color: #ccc;"></i>
                                        <p class="text-muted mt-3">Ingrese la capacidad y número de pisos para ver el diagrama</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarTipoBus()">
                    <i class="bi bi-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- BusRenderer ya lo carga layouts/header.php (cargarlo dos veces lanzaba
     "Identifier 'BusRenderer' has already been declared") -->

<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }

    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    .border-dashed {
        border-style: dashed !important;
    }

    /* Icono de cada tarjeta de tipo de bus: circulo con degradado de marca
       en vez del icono plano azul de Bootstrap, para que combine con el
       resto del sistema (sidebar, KPIs del dashboard, etc.) */
    .tipo-bus-icono {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent), var(--accent-dark));
        color: #fff;
        font-size: 2rem;
        box-shadow: 0 8px 18px rgba(99, 102, 241, 0.3);
    }

    /* Tarjeta "Agregar Nuevo Tipo" */
    .tarjeta-agregar-tipo {
        border: 2px dashed var(--accent) !important;
        background: var(--accent-light);
        color: var(--accent-dark);
    }
    .tarjeta-agregar-tipo:hover { background: var(--accent-light); border-color: var(--accent-dark) !important; }
    .tarjeta-agregar-tipo i { font-size: 3.5rem; color: var(--accent); }
    .tarjeta-agregar-tipo h5 { color: var(--accent-dark); }
    body.dark-mode .tarjeta-agregar-tipo { background: #1b1f3d; }

    /* Canvas container styling */
    #contenedorDiagramaDinamico {
        background: linear-gradient(135deg, var(--accent-light) 0%, var(--color-bg) 100%);
        border-color: var(--color-border) !important;
        display: flex;
        justify-content: center;
        /* flex-start: con center, un bus mas alto que el recuadro se cortaba
           arriba y no se podia desplazar hasta la primera fila */
        align-items: flex-start;
    }
    body.dark-mode #contenedorDiagramaDinamico { background: linear-gradient(135deg, #1b1f3d 0%, var(--color-bg) 100%); }
    #mensajeInicial i { color: var(--color-text-muted) !important; }

    #canvasBusPreview {
        display: block;
        width: 100%;
        margin: 0 auto;
    }

    /* Floor tabs styling */
    .floor-preview-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }

    .floor-preview-tab {
        flex: 1;
        padding: 10px 15px;
        border: 2px solid var(--color-border);
        border-radius: 8px;
        background: var(--color-card-bg);
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--color-text-muted);
    }

    .floor-preview-tab:hover {
        border-color: var(--accent);
        color: var(--accent);
    }

    .floor-preview-tab.active {
        background: var(--accent);
        color: white;
        border-color: var(--accent);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
</style>

<script>
    // ============================================
    // CONFIGURACIÓN DE TIPOS DE BUSES CON CANVAS
    // Usando BusRenderer para diseño idéntico
    // ============================================

    let busRenderer = null;
    let currentPreviewFloor = 1;
    let currentBusConfig = null;

    /**
     * Cambiar piso en preview
     */
    function cambiarPisoPreview(floor) {
        currentPreviewFloor = floor;

        // Actualizar UI de tabs
        document.querySelectorAll('.floor-preview-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`.floor-preview-tab[data-floor="${floor}"]`).classList.add('active');

        // Re-renderizar bus con el nuevo piso
        if (currentBusConfig) {
            renderizarBusPreview(currentBusConfig);
        }
    }

    /**
     * Cambiar número de pisos y regenerar diagrama
     */
    function cambiarPiso() {
        const pisos = parseInt(document.getElementById('pisosTipoBus').value);
        const capacidad = parseInt(document.getElementById('capacidadTipoBus').value) || 0;

        document.getElementById('btnPiso2').disabled = (pisos == 1);

        // Regenerar diagrama si hay capacidad
        if (capacidad > 0) {
            generarDiagramaDinamico();
        }
    }

    /**
     * Generar diagrama dinámico basado en los datos del formulario
     */
    function generarDiagramaDinamico() {
        const nombre = document.getElementById('nombreTipoBus').value || 'Bus';
        const capacidad = parseInt(document.getElementById('capacidadTipoBus').value) || 0;
        const pisos = parseInt(document.getElementById('pisosTipoBus').value) || 1;

        if (capacidad === 0) {
            // Mostrar mensaje inicial
            document.getElementById('canvasBusPreview').style.display = 'none';
            document.getElementById('mensajeInicial').style.display = 'block';
            document.getElementById('floorPreviewTabs').style.display = 'none';
            return;
        }

        // Ocultar mensaje inicial
        document.getElementById('mensajeInicial').style.display = 'none';
        document.getElementById('canvasBusPreview').style.display = 'block';

        // Mostrar/ocultar tabs de pisos
        if (pisos > 1) {
            document.getElementById('floorPreviewTabs').style.display = 'block';
        } else {
            document.getElementById('floorPreviewTabs').style.display = 'none';
            currentPreviewFloor = 1;
        }

        // Calcular distribución
        const distribucion = calcularDistribucion(capacidad, pisos);

        // Crear configuración del bus
        currentBusConfig = {
            capacidad: capacidad,
            asientos_total: capacidad,
            pisos: pisos,
            layout_config: {
                pisos: pisos,
                columnas: 4,
                posicion_pasillo: 2,
                distribucion: distribucion
            },
            asientos_ocupados: [] // Preview sin asientos ocupados
        };

        // Renderizar
        renderizarBusPreview(currentBusConfig);
    }

    /**
     * Calcular distribución de asientos
     */
    function calcularDistribucion(capacidad, pisos) {
        if (pisos === 1) {
            return {
                piso1: {
                    premium: 2,
                    normales: capacidad - 2,
                    filas: Math.ceil((capacidad - 2) / 4)
                }
            };
        } else {
            const asientosPiso1 = Math.ceil(capacidad * 0.6);
            const asientosPiso2 = capacidad - asientosPiso1;

            return {
                piso1: {
                    premium: 2,
                    normales: asientosPiso1 - 2,
                    filas: Math.ceil((asientosPiso1 - 2) / 4)
                },
                piso2: {
                    premium: 0,
                    normales: asientosPiso2,
                    filas: Math.ceil(asientosPiso2 / 4)
                }
            };
        }
    }

    /**
     * Renderizar bus usando BusRenderer
     */
    function renderizarBusPreview(config) {
        console.log("🚌 Renderizando preview de bus:", config);

        // Obtener contenedor
        const contenedor = document.getElementById('contenedorDiagramaDinamico');
        const canvasWidth = Math.max(contenedor.clientWidth - 40, 400);

        // Inicializar renderer si no existe
        if (!busRenderer) {
            busRenderer = new BusRenderer('canvasBusPreview', {
                readOnly: true // Preview sin interacción
            });
        }

        // Inicializar canvas
        busRenderer.initCanvas(canvasWidth, 800);

        // Renderizar el piso actual
        busRenderer.renderBus(config, currentPreviewFloor);
    }

    /**
     * Abrir modal para nuevo tipo de bus
     */
    function abrirModalNuevoTipoBus() {
        // Resetear formulario
        document.getElementById('formTipoBus').reset();
        document.getElementById('tipoBusId').value = '';
        document.getElementById('configuracionAsientos').value = '';

        // Resetear preview
        currentBusConfig = null;
        currentPreviewFloor = 1;
        document.getElementById('canvasBusPreview').style.display = 'none';
        document.getElementById('mensajeInicial').style.display = 'block';
        document.getElementById('floorPreviewTabs').style.display = 'none';

        // Abrir modal
        const modal = new bootstrap.Modal(document.getElementById('modalTipoBus'));
        modal.show();
    }

    /**
     * Abrir modal para ver/editar tipo de bus existente
     */
    function abrirModalTipoBus(id, nombre, capacidad, pisos, config) {
        // Llenar formulario
        document.getElementById('tipoBusId').value = id;
        document.getElementById('nombreTipoBus').value = nombre;
        document.getElementById('capacidadTipoBus').value = capacidad;
        document.getElementById('pisosTipoBus').value = pisos;
        document.getElementById('configuracionAsientos').value = config || '';

        // Habilitar/deshabilitar botón piso 2
        document.getElementById('btnPiso2').disabled = (pisos == 1);

        // Resetear piso actual
        currentPreviewFloor = 1;

        // Abrir modal
        const modal = new bootstrap.Modal(document.getElementById('modalTipoBus'));
        modal.show();

        // Generar diagrama después de que el modal se muestre
        setTimeout(() => {
            generarDiagramaDinamico();
        }, 300);
    }

    /**
     * Guardar tipo de bus
     */
    function guardarTipoBus() {
        // Validar nombre
        const nombre = document.getElementById('nombreTipoBus').value.trim();
        if (!nombre) {
            alert('Por favor ingrese el nombre del tipo de bus');
            return;
        }

        // Validar capacidad
        const capacidad = parseInt(document.getElementById('capacidadTipoBus').value);
        if (!capacidad || capacidad <= 0) {
            alert('Por favor ingrese una capacidad válida');
            return;
        }

        // Guardar la configuración del diagrama como JSON
        const pisos = parseInt(document.getElementById('pisosTipoBus').value);
        const configuracion = {
            capacidad: capacidad,
            pisos: pisos,
            layout: '2-2',
            columnas: 4,
            posicion_pasillo: 2,
            distribucion: calcularDistribucion(capacidad, pisos),
            fecha_creacion: new Date().toISOString()
        };

        // Guardar en el campo oculto
        document.getElementById('configuracionAsientos').value = JSON.stringify(configuracion);

        // Enviar formulario
        document.getElementById('formTipoBus').submit();
    }

    /**
     * Limpiar al cerrar el modal
     */
    document.getElementById('modalTipoBus').addEventListener('hidden.bs.modal', function() {
        // Resetear preview
        currentBusConfig = null;
        currentPreviewFloor = 1;
        document.getElementById('canvasBusPreview').style.display = 'none';
        document.getElementById('mensajeInicial').style.display = 'block';
        document.getElementById('floorPreviewTabs').style.display = 'none';

        // Limpiar canvas
        if (busRenderer) {
            busRenderer.dispose();
            busRenderer = null;
        }
    });

    /**
     * Event listeners para generar diagrama automáticamente
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Cuando cambia la capacidad
        const capacidadInput = document.getElementById('capacidadTipoBus');
        if (capacidadInput) {
            capacidadInput.addEventListener('change', generarDiagramaDinamico);
            capacidadInput.addEventListener('blur', generarDiagramaDinamico);
        }

        // Cuando cambia el número de pisos
        const pisosSelect = document.getElementById('pisosTipoBus');
        if (pisosSelect) {
            pisosSelect.addEventListener('change', cambiarPiso);
        }
    });

    /**
     * ⭐ NUEVA FUNCIÓN: Eliminar Tipo de Bus con confirmación
     */
    function eliminarTipoBus(id, nombre) {
        // Confirmación con SweetAlert2
        Swal.fire({
            title: '¿Estás seguro?',
            html: `
                <p>Estás a punto de eliminar el tipo de bus:</p>
                <p class="fw-bold text-danger">${nombre}</p>
                <p class="small text-muted">Esta acción cambiará el estado del tipo de bus a inactivo.</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash me-2"></i>Sí, eliminar',
            cancelButtonText: '<i class="bi bi-x-lg me-2"></i>Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Eliminando...',
                    html: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar petición AJAX
                fetch('<?php echo URLROOT; ?>/admin/eliminar_tipo_bus', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'id=' + id
                    })
                    .then(response => {
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            throw new Error('La respuesta del servidor no es JSON válido');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            // Éxito
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: data.message || 'El tipo de bus ha sido eliminado correctamente.',
                                confirmButtonColor: '#3085d6',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Recargar la página para actualizar la lista
                                window.location.reload();
                            });
                        } else {
                            // Error del servidor
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al Eliminar',
                                text: data.message || 'No se pudo eliminar el tipo de bus. Intente nuevamente.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Conexión',
                            text: 'No se pudo conectar con el servidor. Verifique su conexión a internet.',
                            confirmButtonColor: '#d33'
                        });
                    });
            }
        });
    }
</script>
@endsection
