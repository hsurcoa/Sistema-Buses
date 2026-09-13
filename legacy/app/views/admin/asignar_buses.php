<!-- Contenedor Principal (Simulando Modal o Panel) -->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">

            <!-- Breadcrumb -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-muted small">
                    <i class="bi bi-house-door-fill"></i> Inicio / Registros / Asignar Buses
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-secondary"><i class="bi bi-ticket-perferated-fill me-2"></i>ASIGNAR BUSES</h6>
                </div>
                <div class="card-body">

                    <!-- Formulario Superior -->
                    <form id="formAsignacion" action="<?php echo URLROOT; ?>/admin/guardar_asignacion" method="POST">
                        <div class="row g-3 mb-4">
                            <!-- Select Chofer -->
                            <div class="col-md-4">
                                <label for="selectChofer" class="form-label text-uppercase fw-bold text-secondary small">CHOFER:</label>
                                <select class="form-select bg-light" id="selectChofer" name="chofer_id" required>
                                    <option value="" selected disabled>Seleccione</option>
                                    <?php if (!empty($data['choferes'])): ?>
                                        <?php foreach ($data['choferes'] as $chofer): ?>
                                            <option value="<?php echo $chofer->id; ?>">
                                                <?php echo $chofer->nombres . ' ' . $chofer->apellidos; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Select Bus -->
                            <div class="col-md-4">
                                <label for="selectBus" class="form-label text-uppercase fw-bold text-secondary small">BUS:</label>
                                <select class="form-select bg-light" id="selectBus" name="bus_id" required>
                                    <option value="" selected disabled>Seleccione</option>
                                    <?php if (!empty($data['buses'])): ?>
                                        <?php foreach ($data['buses'] as $bus): ?>
                                            <option value="<?php echo $bus->id; ?>">
                                                <?php echo $bus->placa . ' - ' . $bus->marca; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Select Copiloto -->
                            <div class="col-md-4">
                                <label for="selectCopiloto" class="form-label text-uppercase fw-bold text-secondary small">COPILOTO (Opcional):</label>
                                <select class="form-select bg-light" id="selectCopiloto" name="copiloto_id">
                                    <option value="" selected>-- Sin Copiloto --</option>
                                    <?php if (!empty($data['copilotos'])): ?>
                                        <?php foreach ($data['copilotos'] as $copiloto): ?>
                                            <option value="<?php echo $copiloto->id; ?>">
                                                <?php echo $copiloto->nombres . ' ' . $copiloto->apellidos; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Boton Guardar -->
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary px-4 fw-bold">
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </form>

                    <hr class="text-muted opacity-25">

                    <!-- Titulo Tabla -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-secondary"><i class="bi bi-list-ul me-2"></i> RELACION DE CHOFERES - BUSES</h6>
                    </div>

                    <!-- Buscador y Paginacion Top -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><label class="me-2">Buscar:</label></span>
                                <input type="text" class="form-control border-start-0 ps-0" placeholder="">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex justify-content-end align-items-center">
                            <label class="me-2 text-secondary">Lista:</label>
                            <select class="form-select w-auto">
                                <option>10</option>
                                <option>25</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-teal text-white" style="background-color: #17a2b8; color: white;">
                                <tr class="text-uppercase text-center small fw-bold">
                                    <th class="py-3">NOMBRE CHOFER <i class="bi bi-chevron-expand float-end"></i></th>
                                    <th class="py-3">NOMBRE COPILOTO <i class="bi bi-chevron-expand float-end"></i></th>
                                    <th class="py-3"># PLACA BUS <i class="bi bi-chevron-expand float-end"></i></th>
                                    <th class="py-3"># PASAJEROS <i class="bi bi-chevron-expand float-end"></i></th>
                                    <th class="py-3">ESTADO <i class="bi bi-chevron-expand float-end"></i></th>
                                    <th class="py-3"><i class="bi bi-gear-fill"></i></th>
                                </tr>
                            </thead>
                            <tbody class="text-center text-secondary small bg-white">
                                <?php if (!empty($data['asignaciones'])): ?>
                                    <?php foreach ($data['asignaciones'] as $asignacion): ?>
                                        <tr>
                                            <td class="text-start ps-4"><?php echo $asignacion->nombre_chofer; ?></td>
                                            <td><?php echo $asignacion->nombre_copiloto; ?></td>
                                            <td><?php echo $asignacion->placa_bus; ?></td>
                                            <td><?php echo $asignacion->total_pasajeros; ?></td>
                                            <td>
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" role="switch" <?php echo ($asignacion->estado == 1) ? 'checked' : ''; ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="#" class="text-primary fs-5"><i class="bi bi-pencil-square"></i></a>
                                                <a href="#" class="text-danger fs-5 ms-2" onclick="eliminarAsignacion(<?php echo $asignacion->id; ?>); return false;">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No hay asignaciones registradas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginacion Bottom -->
                    <div class="row mt-3 align-items-center">
                        <div class="col-md-6 small text-secondary">
                            Mostrando del 1 al <?php echo count($data['asignaciones'] ?? []); ?> de un total de <?php echo count($data['asignaciones'] ?? []); ?> registros
                        </div>
                        <div class="col-md-6">
                            <nav>
                                <ul class="pagination justify-content-end mb-0">
                                    <li class="page-item disabled"><a class="page-link" href="#">&larr;</a></li>
                                    <li class="page-item active bg-dark"><a class="page-link bg-dark border-dark" href="#">1</a></li>
                                    <li class="page-item disabled"><a class="page-link" href="#">&rarr;</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ==========================================
    // 🚀 IMPLEMENTACIÓN AJAX CON SWEETALERT2
    // ==========================================

    const formAsignacion = document.getElementById('formAsignacion');

    formAsignacion.addEventListener('submit', function(e) {
        // 1. Prevenir la recarga de la página
        e.preventDefault();

        // 2. Obtener el botón de submit y deshabilitarlo
        const btnSubmit = this.querySelector('[type="submit"]');
        const textoOriginal = btnSubmit.innerHTML;
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

        // 3. Recopilar los datos del formulario
        const formData = new FormData(this);

        // 4. Enviar la petición AJAX usando Fetch API
        fetch('<?php echo URLROOT; ?>/admin/guardar_asignacion', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Verificar que la respuesta sea JSON válido
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('La respuesta del servidor no es JSON válido');
                }
                return response.json();
            })
            .then(data => {
                // 5. Procesar la respuesta del servidor
                if (data.status === 'success') {
                    // ✅ ÉXITO - Mostrar SweetAlert2 con diseño moderno
                    Swal.fire({
                        icon: 'success',
                        title: '¡Asignación Exitosa!',
                        text: data.message,
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Aceptar',
                        allowOutsideClick: false,
                        customClass: {
                            popup: 'animated fadeInDown'
                        }
                    }).then((result) => {
                        // 6. Redirigir al hacer clic en "Aceptar"
                        if (result.isConfirmed) {
                            window.location.href = '<?php echo URLROOT; ?>/admin/asignar_buses';
                        }
                    });
                } else {
                    // ❌ ERROR - Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al Asignar',
                        text: data.message || 'Ocurrió un error inesperado. Por favor, intente nuevamente.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Entendido'
                    });

                    // Restaurar el botón
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = textoOriginal;
                }
            })
            .catch(error => {
                // ⚠️ ERROR DE RED O PARSING
                console.error('Error:', error);

                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor. Verifique su conexión a internet.',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Entendido'
                });

                // Restaurar el botón
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = textoOriginal;
            });
    });

    // ==========================================
    // 🗑️ FUNCIÓN ELIMINAR CON SWEETALERT2
    // ==========================================

    function eliminarAsignacion(id) {
        Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción eliminará la asignación definitivamente",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?php echo URLROOT; ?>/admin/eliminar_asignacion/' + id;
            }
        });
    }

    // ==========================================
    // ✏️ FUNCIONALIDAD DE EDICIÓN DE ASIGNACIONES
    // ==========================================

    /**
     * Detectar clic en botones de editar
     */
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-editar')) {
            const button = e.target.closest('.btn-editar');
            const asignacionId = button.getAttribute('data-id');

            if (!asignacionId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo identificar la asignación a editar.',
                    confirmButtonColor: '#d33'
                });
                return;
            }

            abrirModalEditarAsignacion(asignacionId);
        }
    });

    /**
     * Función para abrir el modal y cargar los datos de la asignación
     */
    function abrirModalEditarAsignacion(asignacionId) {
        // Mostrar indicador de carga
        Swal.fire({
            title: 'Cargando...',
            text: 'Obteniendo información de la asignación',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Realizar petición AJAX para obtener los datos
        fetch('<?php echo URLROOT; ?>/admin/obtener_asignacion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + asignacionId
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
                    // Cerrar el loading
                    Swal.close();

                    // Llenar el formulario con los datos recibidos
                    document.getElementById('editAsignacionId').value = asignacionId;
                    document.getElementById('editSelectChofer').value = data.id_chofer || '';
                    document.getElementById('editSelectBus').value = data.id_bus || '';
                    document.getElementById('editSelectCopiloto').value = data.id_copiloto || '';

                    // Abrir el modal
                    const modal = new bootstrap.Modal(document.getElementById('modalEditarAsignacion'));
                    modal.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo cargar la información de la asignación.',
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

    /**
     * Manejador del evento para guardar los cambios
     */
    document.getElementById('btnGuardarEdicionAsignacion').addEventListener('click', function() {
        const form = document.getElementById('formEditarAsignacion');

        // Validar el formulario
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Deshabilitar el botón y mostrar indicador de carga
        const btnGuardar = this;
        const btnOriginalText = btnGuardar.innerHTML;
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

        // Recopilar los datos del formulario
        const formData = new FormData(form);

        // Enviar la petición AJAX
        fetch('<?php echo URLROOT; ?>/admin/editar_asignacion', {
                method: 'POST',
                body: formData
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
                    // Cerrar el modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarAsignacion'));
                    modal.hide();

                    // Mostrar SweetAlert2 de éxito
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado',
                        text: data.message || 'La asignación ha sido actualizada correctamente.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Recargar la página para mostrar los cambios
                            window.location.reload();
                        }
                    });
                } else {
                    // Mostrar error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al Actualizar',
                        text: data.message || 'Ocurrió un error inesperado. Por favor, intente nuevamente.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Entendido'
                    });

                    // Restaurar el botón
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = btnOriginalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor. Verifique su conexión a internet.',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Entendido'
                });

                // Restaurar el botón
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = btnOriginalText;
            });
    });
</script>