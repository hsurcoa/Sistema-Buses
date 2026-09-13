<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Rutas y Paradas</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="<?php echo URLROOT; ?>/dashboard">Inicio</a></li>
                        <li class="breadcrumb-item">Registros</li>
                        <li class="breadcrumb-item active" aria-current="page">Rutas y Paradas</li>
                    </ol>
                </div>
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content Header-->
    <!--begin::App Content-->
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row justify-content-center">
                <div class="col-md-11 col-lg-12">
                    <div class="card shadow-lg mb-4">
                        <div class="card-header p-0 pt-1 border-bottom-0">
                            <!-- Sistema de Pestañas -->
                            <ul class="nav nav-tabs" id="rutasTabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active fw-bold text-uppercase" id="registrar-tab" data-bs-toggle="tab" data-bs-target="#registrar-pane" type="button" role="tab" aria-controls="registrar-pane" aria-selected="true">
                                        <i class="bi bi-signpost-2 me-2"></i>Registrar Rutas
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link fw-bold text-uppercase" id="paradas-tab" data-bs-toggle="tab" data-bs-target="#paradas-pane" type="button" role="tab" aria-controls="paradas-pane" aria-selected="false">
                                        <i class="bi bi-geo-alt me-2"></i>Registrar Paradas
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <!-- Contenido de las Pestañas -->
                            <div class="tab-content" id="rutasTabsContent">

                                <!-- PESTAÑA 1: REGISTRAR RUTAS -->
                                <div class="tab-pane fade show active p-2" id="registrar-pane" role="tabpanel" aria-labelledby="registrar-tab">

                                    <!-- Formulario de Registro (Premium Format) -->
                                    <div class="card bg-light border-0 mb-5 p-3 rounded-4">
                                        <div class="card-body">
                                            <form id="formRegistroRuta" action="<?php echo URLROOT; ?>/admin/guardar_ruta" method="POST" class="needs-validation" novalidate>
                                                <input type="hidden" id="rutaId" name="id">

                                                <h6 class="text-uppercase text-secondary fw-bold mb-3 small tracking-wide"><i class="bi bi-pin-map-fill me-2"></i>Nueva Ruta</h6>

                                                <div class="row g-3 align-items-center">
                                                    <div class="col-md-5">
                                                        <div class="form-floating">
                                                            <input type="text" class="form-control" id="origenRuta" name="origen" placeholder="Ej: Huacho" required>
                                                            <label for="origenRuta">Inicio Ruta</label>
                                                            <div class="invalid-feedback">Por favor ingrese el origen.</div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-1 text-center d-none d-md-block text-muted">
                                                        <i class="bi bi-arrow-right fs-4"></i>
                                                    </div>

                                                    <div class="col-md-5">
                                                        <div class="form-floating">
                                                            <input type="text" class="form-control" id="destinoRuta" name="destino" placeholder="Ej: Lima" required>
                                                            <label for="destinoRuta">Fin Ruta</label>
                                                            <div class="invalid-feedback">Por favor ingrese el destino.</div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 text-end mt-4">
                                                        <button type="submit" class="btn btn-primary px-5 py-2 shadow-sm" id="btnGuardarRuta">
                                                            <i class="bi bi-check2-circle me-2"></i>Guardar Ruta
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Lista de Rutas header -->
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="fw-bold text-dark mb-0">Rutas Disponibles</h5>
                                        <div class="input-group w-auto shadow-sm" style="border-radius: 12px; overflow: hidden;">
                                            <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                                            <input type="text" class="form-control border-0 ps-1" id="buscarRuta" placeholder="Buscar ruta..." style="min-width: 250px;">
                                        </div>
                                    </div>

                                    <!-- Table -->
                                    <div class="table-responsive rounded-4 shadow-sm bg-white">
                                        <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
                                            <thead class="bg-light">
                                                <tr class="text-uppercase text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                                    <th class="ps-4 py-3">Ruta (Origen - Destino)</th>
                                                    <th class="text-center py-3">Estado</th>
                                                    <th class="text-end pe-4 py-3" style="width: 150px;">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody class="border-top-0">
                                                <?php if (!empty($data['rutas'])): ?>
                                                    <?php foreach ($data['rutas'] as $ruta): ?>
                                                        <tr>
                                                            <td class="ps-4 py-3">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3 text-primary">
                                                                        <i class="bi bi-bus-front"></i>
                                                                    </div>
                                                                    <div>
                                                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($ruta->origen); ?> <i class="bi bi-arrow-right mx-1 text-muted small"></i> <?php echo htmlspecialchars($ruta->destino); ?></div>
                                                                        <small class="text-muted">ID: #<?php echo $ruta->id; ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="form-check form-switch d-inline-block">
                                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                                        <?php echo ($ruta->estado == 1) ? 'checked' : ''; ?>
                                                                        onchange="cambiarEstadoRuta(<?php echo $ruta->id; ?>)">
                                                                </div>
                                                            </td>
                                                            <td class="text-end pe-4">
                                                                <button onclick="editarRuta(<?php echo $ruta->id; ?>, '<?php echo htmlspecialchars($ruta->origen); ?>', '<?php echo htmlspecialchars($ruta->destino); ?>')"
                                                                    class="btn btn-icon btn-ghost-primary rounded-circle" data-bs-toggle="tooltip" title="Editar">
                                                                    <i class="bi bi-pencil-fill"></i>
                                                                </button>
                                                                <button onclick="confirmarEliminarRuta(<?php echo $ruta->id; ?>)"
                                                                    class="btn btn-icon btn-ghost-danger rounded-circle ms-1" data-bs-toggle="tooltip" title="Eliminar">
                                                                    <i class="bi bi-trash-fill"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center py-5 text-muted">
                                                            <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                                            No hay rutas registradas aún.
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination (Simplified) -->
                                    <div class="d-flex justify-content-between align-items-center mt-4 px-2">
                                        <small class="text-muted fw-bold">Total: <?php echo count($data['rutas']); ?> rutas</small>
                                        <nav>
                                            <ul class="pagination pagination-sm mb-0">
                                                <li class="page-item disabled"><a class="page-link border-0 bg-transparent" href="#"><i class="bi bi-chevron-left"></i></a></li>
                                                <li class="page-item active"><a class="page-link rounded-circle border-0 fw-bold" href="#">1</a></li>
                                                <li class="page-item disabled"><a class="page-link border-0 bg-transparent" href="#"><i class="bi bi-chevron-right"></i></a></li>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>

                                <!-- PESTAÑA 2: REGISTRAR PARADAS (PREMIUM TIMELINE) -->
                                <div class="tab-pane fade" id="paradas-pane" role="tabpanel" aria-labelledby="paradas-tab">

                                    <!-- Selector de Ruta -->
                                    <div class="row justify-content-center mb-4">
                                        <div class="col-md-6 text-center">
                                            <label class="form-label fw-bold text-muted small text-uppercase">Seleccione Ruta para Configurar:</label>
                                            <select class="form-select form-select-lg shadow-sm border-0 text-center fw-bold" id="selectRutaEscalas" style="background-color: #f8f9fa; border-radius: 12px;">
                                                <option selected disabled value="">-- Seleccione una Ruta --</option>
                                                <?php if (!empty($data['rutas'])): ?>
                                                    <?php foreach ($data['rutas'] as $ruta): ?>
                                                        <option value="<?php echo $ruta->id; ?>" data-origen="<?php echo $ruta->origen; ?>" data-destino="<?php echo $ruta->destino; ?>">
                                                            <?php echo $ruta->origen . ' ➝ ' . $ruta->destino; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Timeline Container -->
                                    <div class="timeline-wrapper position-relative p-4 p-md-5" style="border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #f3f4f6 100%); border: 1px solid rgba(0,0,0,0.03);">

                                        <!-- Vertical Guide Line -->
                                        <div class="timeline-guide"></div>

                                        <!-- START NODE -->
                                        <div class="timeline-node start-node mb-4">
                                            <div class="node-badge bg-primary shadow-primary-glow">
                                                <i class="bi bi-geo-alt-fill text-white"></i>
                                            </div>
                                            <div class="node-content text-center">
                                                <small class="text-uppercase fw-bold text-primary tracking-wide">Inicio de Ruta</small>
                                                <h4 class="fw-bolder text-dark mb-0 mt-1" id="timelineOrigen">...</h4>
                                            </div>
                                        </div>

                                        <!-- DYNAMIC STOPS CONTAINER -->
                                        <div id="containerParadas" class="py-2">
                                            <!-- Las paradas se inyectarán aquí por JS -->
                                        </div>

                                        <!-- ADD STOP BUTTON (Floating) -->
                                        <div class="text-center my-4 position-relative" style="z-index: 5;">
                                            <button type="button" class="btn btn-add-stop shadow-lg" onclick="agregarParada()">
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                            <div class="text-muted small fw-bold mt-2">Agregar Escala</div>
                                        </div>

                                        <!-- END NODE -->
                                        <div class="timeline-node end-node mt-4">
                                            <div class="node-badge bg-dark shadow-dark-glow">
                                                <i class="bi bi-flag-fill text-white"></i>
                                            </div>
                                            <div class="node-content text-center">
                                                <small class="text-uppercase fw-bold text-secondary tracking-wide">Fin de Ruta</small>
                                                <h4 class="fw-bolder text-dark mb-0 mt-1" id="timelineDestino">...</h4>
                                            </div>
                                        </div>

                                        <!-- ACTIONS -->
                                        <div class="row mt-5">
                                            <div class="col-12 text-end">
                                                <button type="button" class="btn btn-primary btn-lg rounded-pill px-5 shadow-lg fw-bold" onclick="guardarEscalas()">
                                                    Guardar Configuración <i class="bi bi-check-lg ms-2"></i>
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content-->
</main>
<!--end::App Main-->

<!-- SweetAlert2 CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Variables Globales
    let contadorParadas = 0;

    // Inicialización
    document.addEventListener('DOMContentLoaded', function() {

        // Listener para cambio de ruta
        const selectRuta = document.getElementById('selectRutaEscalas');
        if (selectRuta) {
            selectRuta.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const origen = selectedOption.dataset.origen || '...';
                const destino = selectedOption.dataset.destino || '...';

                // Actualizar Nodos Visuales
                document.getElementById('timelineOrigen').textContent = origen;
                document.getElementById('timelineOrigen').classList.add('animate__animated', 'animate__fadeInDown');

                document.getElementById('timelineDestino').textContent = destino;
                document.getElementById('timelineDestino').classList.add('animate__animated', 'animate__fadeInUp');

                // TODO: Aquí se debería hacer un FETCH para cargar las escalas existentes de la BD
                // Por ahora limpiamos para demo
                // document.getElementById('containerParadas').innerHTML = '';
            });
        }
    });

    /**
     * Agrega una nueva tarjeta de escala al timeline
     */
    function agregarParada() {
        const container = document.getElementById('containerParadas');
        const index = contadorParadas++;

        const cardHTML = `
            <div class="timeline-card-wrapper animate__animated animate__fadeInDown mb-4" id="parada-${index}">
                <div class="d-flex align-items-center position-relative">
                    
                    <!-- Dot en la línea -->
                    <div class="timeline-dot"></div>
                    
                    <!-- Conector horizontal -->
                    <div class="timeline-connector"></div>

                    <!-- Tarjeta Glassmophism -->
                    <div class="card border-0 shadow-sm w-100 timeline-glass-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-0">
                                <h6 class="text-primary fw-bold mb-0 text-uppercase small">Escala #${index + 1}</h6>
                                <button type="button" class="btn btn-link text-danger p-0" onclick="eliminarParada(${index})" title="Eliminar Escala">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                            
                            <div class="row g-2 mt-2 align-items-center">
                                
                                <!-- Ciudad/Lugar -->
                                <div class="col-md-5">
                                    <div class="form-floating">
                                        <input type="text" class="form-control form-control-sm border-0 bg-light" 
                                            name="paradas[${index}][nombre]" placeholder="Ciudad" required>
                                        <label class="text-muted"><i class="bi bi-geo me-1"></i>Lugar / Ciudad</label>
                                    </div>
                                </div>

                                <!-- Hora Llegada -->
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="time" class="form-control form-control-sm border-0 bg-light" 
                                            name="paradas[${index}][hora]" required>
                                        <label class="text-muted"><i class="bi bi-clock me-1"></i>Hora Aprox.</label>
                                    </div>
                                </div>

                                <!-- Costo Adicional (Opcional) -->
                                <div class="col-md-4">
                                    <div class="input-group input-group-sm shadow-none">
                                        <span class="input-group-text border-0 bg-white text-secondary pe-1"><small>Extra:</small></span>
                                        <span class="input-group-text border-0 bg-light rounded-start ps-2">Bs.</span>
                                        <input type="number" step="0.50" class="form-control border-0 bg-light" 
                                            name="paradas[${index}][precio]" placeholder="0.00">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insertar antes del final (append)
        container.insertAdjacentHTML('beforeend', cardHTML);
    }

    /**
     * Elimina una parada con animación
     */
    function eliminarParada(index) {
        const element = document.getElementById(`parada-${index}`);
        if (element) {
            // Animación de salida
            element.classList.remove('animate__fadeInDown');
            element.classList.add('animate__fadeOutRight');

            setTimeout(() => {
                element.remove();
            }, 500); // Esperar a que termine la animación css (0.5s usualmente)
        }
    }

    function guardarEscalas() {
        // Lógica de guardado (Mock)
        Swal.fire({
            icon: 'success',
            title: 'Configuración Guardada',
            text: 'Las escalas se han actualizado correctamente para la ruta.',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Validación de Bootstrap 5 (Existente)
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()

    // Funciones existentes de Rutas...
    function editarRuta(id, origen, destino) {
        document.getElementById('rutaId').value = id;
        document.getElementById('origenRuta').value = origen;
        document.getElementById('destinoRuta').value = destino;
        document.getElementById('btnGuardarRuta').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Actualizar';
        document.getElementById('formRegistroRuta').scrollIntoView({
            behavior: 'smooth'
        });
    }

    function confirmarEliminarRuta(id) {
        Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción eliminará la ruta permanentemente",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?php echo URLROOT; ?>/admin/eliminar_ruta/' + id;
            }
        });
    }

    function cambiarEstadoRuta(id) {
        window.location.href = '<?php echo URLROOT; ?>/admin/cambiar_estado_ruta/' + id;
    }

    document.getElementById('buscarRuta').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('#registrar-pane tbody tr');
        tableRows.forEach(row => {
            const origen = row.cells[1]?.textContent.toLowerCase() || '';
            const destino = row.cells[2]?.textContent.toLowerCase() || '';
            row.style.display = (origen.includes(searchTerm) || destino.includes(searchTerm)) ? '' : 'none';
        });
    });
</script>

<style>
    /* =========================================
       TIMELINE DESIGN SYSTEM (PREMIUM SAAS)
       ========================================= */

    /* Importar fuente clean si es necesario, asumimos Bootstrap standard */

    .timeline-wrapper {
        min-height: 400px;
        overflow: hidden;
        /* Contener floats */
    }

    /* Línea Guía Vertical */
    .timeline-guide {
        position: absolute;
        top: 80px;
        bottom: 80px;
        left: 50%;
        width: 2px;
        background: repeating-linear-gradient(to bottom, #e5e7eb 0, #e5e7eb 6px, transparent 6px, transparent 12px);
        transform: translateX(-50%);
        z-index: 0;
    }

    /* Nodos Fijos (Inicio/Fin) */
    .timeline-node {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .node-badge {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .timeline-node:hover .node-badge {
        transform: scale(1.15);
    }

    .shadow-primary-glow {
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
    }

    .shadow-dark-glow {
        box-shadow: 0 0 0 4px rgba(33, 37, 41, 0.1);
    }

    .tracking-wide {
        letter-spacing: 2px;
    }

    /* Tarjetas de Escala (Dynamic) */
    .timeline-card-wrapper {
        position: relative;
        width: 100%;
        max-width: 600px;
        margin: 0 auto;
        padding-left: 20px;
        /* Espacio para el dot vertical si estuviera alineado a la izquierda, aquí centrado ajuste distinto */
    }

    /* Ajuste para centrar visualmente con la línea */
    /* En desktop, queremos que alterne o se centre. Para simplificar UX Admin form, usaremos un diseño Stacked Centered */

    .timeline-card-wrapper .timeline-dot {
        position: absolute;
        left: 50%;
        /* Centrado relativo a la línea padre si wrapper es full width. Ajustemos lógica. */
        top: 50%;
        width: 12px;
        height: 12px;
        background: #fff;
        border: 3px solid #6366f1;
        /* Indigo Premium */
        border-radius: 50%;
        z-index: 10;
        transform: translate(-50%, -50%);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    /* Ocultamos el dot central en este layout "Card Overlay" y usamos un conector lateral si fuera necesario.
       Para este diseño "Formulario Vertical", haremos que la tarjeta flote ENCIMA de la línea con un fondo blanco opaco */

    .timeline-glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 16px;
        transition: all 0.3s ease;
    }

    .timeline-glass-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08) !important;
        border-color: rgba(13, 110, 253, 0.3);
    }

    /* Botón Flotante Agregar */
    .btn-add-stop {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: white;
        border: none;
        color: #0d6efd;
        font-size: 1.5rem;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .btn-add-stop:hover {
        background: #0d6efd;
        color: white;
        transform: rotate(90deg) scale(1.1);
    }

    /* Inputs Minimalistas */
    .form-floating>.form-control:focus~label,
    .form-floating>.form-control:not(:placeholder-shown)~label {
        color: #0d6efd;
        font-weight: bold;
    }

    .timeline-node {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .node-badge {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .timeline-node:hover .node-badge {
        transform: scale(1.15);
    }

    .shadow-primary-glow {
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
    }

    .shadow-dark-glow {
        box-shadow: 0 0 0 4px rgba(33, 37, 41, 0.1);
    }

    .tracking-wide {
        letter-spacing: 2px;
    }

    .timeline-card-wrapper {
        position: relative;
        width: 100%;
        max-width: 600px;
        margin: 0 auto;
        padding-left: 20px;
    }

    .timeline-card-wrapper .timeline-dot {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 12px;
        height: 12px;
        background: #fff;
        border: 3px solid #6366f1;
        border-radius: 50%;
        z-index: 10;
        transform: translate(-50%, -50%);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    .timeline-glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 16px;
        transition: all 0.3s ease;
    }

    .timeline-glass-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08) !important;
        border-color: rgba(13, 110, 253, 0.3);
    }

    .btn-add-stop {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: white;
        border: none;
        color: #0d6efd;
        font-size: 1.5rem;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .btn-add-stop:hover {
        background: #0d6efd;
        color: white;
        transform: rotate(90deg) scale(1.1);
    }
</style>
<!-- Animate.css para transiciones suaves -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />