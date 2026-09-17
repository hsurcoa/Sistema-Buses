@extends('layouts.app')

@section('content')
<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">
                        <i class="fas fa-key text-warning me-2"></i>
                        Roles y Permisos
                    </h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="<?php echo URLROOT; ?>/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Roles y Permisos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!--end::App Content Header-->

    <!--begin::App Content-->
    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!--begin::Card-->
                    <div class="card shadow-sm">
                        <!--begin::Card Header-->
                        <div class="card-header bg-white border-bottom">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold mb-2">
                                        <i class="fas fa-user-tag me-2"></i>PERFILES:
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <select class="form-select form-select-lg" id="selectRol" style="max-width: 300px;">
                                            <option value="">Seleccione...</option>
                                            <?php foreach ($data['roles'] as $rol): ?>
                                                <option value="<?php echo $rol->id; ?>">
                                                    <?php echo htmlspecialchars($rol->nombre); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-primary btn-lg" id="btnNuevoRol" title="Agregar nuevo rol">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button class="btn btn-success btn-lg" id="btnGuardarPermisos" disabled>
                                        <i class="fas fa-save me-2"></i>Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!--end::Card Header-->

                        <!--begin::Card Body-->
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered mb-0" id="tablaPermisos">
                                    <!--begin::Table Head-->
                                    <thead>
                                        <tr style="background: linear-gradient(135deg, #1e7e34 0%, #28a745 100%); color: white;">
                                            <th class="text-center fw-bold" style="width: 20%;">GRUPO</th>
                                            <th class="text-center fw-bold" style="width: 60%;">VISTAS</th>
                                            <th class="text-center fw-bold" style="width: 20%;">PERMISOS</th>
                                        </tr>
                                    </thead>
                                    <!--end::Table Head-->

                                    <!--begin::Table Body-->
                                    <tbody>
                                        <?php if (!empty($data['permisosAgrupados'])): ?>
                                            <?php foreach ($data['permisosAgrupados'] as $grupo => $permisos): ?>
                                                <!-- Fila de Grupo -->
                                                <tr class="table-secondary">
                                                    <td colspan="3" class="fw-bold text-uppercase py-2 px-3">
                                                        <i class="fas fa-folder-open me-2"></i>
                                                        <?php echo htmlspecialchars($grupo); ?>
                                                    </td>
                                                </tr>

                                                <!-- Filas de Permisos -->
                                                <?php foreach ($permisos as $permiso): ?>
                                                    <tr class="permiso-row">
                                                        <td class="text-center align-middle text-muted">
                                                            <!-- Columna vacía para alineación -->
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <?php echo htmlspecialchars($permiso->vista); ?>
                                                            <?php if (!empty($permiso->descripcion)): ?>
                                                                <br>
                                                                <small class="text-muted">
                                                                    <?php echo htmlspecialchars($permiso->descripcion); ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <!-- Toggle Switch -->
                                                            <div class="form-check form-switch d-flex justify-content-center">
                                                                <input
                                                                    class="form-check-input permiso-switch"
                                                                    type="checkbox"
                                                                    role="switch"
                                                                    id="permiso_<?php echo $permiso->id; ?>"
                                                                    data-permiso-id="<?php echo $permiso->id; ?>"
                                                                    style="width: 3rem; height: 1.5rem; cursor: pointer;"
                                                                    disabled>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-5 text-muted">
                                                    <i class="fas fa-info-circle fa-3x mb-3 d-block"></i>
                                                    No hay permisos configurados en el sistema
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <!--end::Table Body-->
                                </table>
                            </div>
                        </div>
                        <!--end::Card Body-->

                        <!--begin::Card Footer-->
                        <div class="card-footer bg-light text-muted">
                            <div class="d-flex justify-content-between align-items-center">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Seleccione un perfil para gestionar sus permisos
                                </small>
                                <small id="permisoCount">
                                    Total de permisos: <strong><?php echo count($data['permisosAgrupados'] ?? []); ?></strong>
                                </small>
                            </div>
                        </div>
                        <!--end::Card Footer-->
                    </div>
                    <!--end::Card-->
                </div>
            </div>
        </div>
    </div>
    <!--end::App Content-->
</main>
<!--end::App Main-->

<!-- Modal: Nuevo Rol -->
<div class="modal fade" id="modalNuevoRol" tabindex="-1" aria-labelledby="modalNuevoRolLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNuevoRolLabel">
                    <i class="fas fa-plus-circle me-2"></i>Crear Nuevo Rol
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevoRol">
                    <div class="mb-3">
                        <label for="nombreRol" class="form-label fw-bold">
                            Nombre del Rol <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="nombreRol"
                            name="nombre"
                            placeholder="Ej: Supervisor, Contador, etc."
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcionRol" class="form-label fw-bold">
                            Descripción
                        </label>
                        <textarea
                            class="form-control"
                            id="descripcionRol"
                            name="descripcion"
                            rows="3"
                            placeholder="Descripción opcional del rol..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnConfirmarNuevoRol">
                    <i class="fas fa-check me-2"></i>Crear Rol
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSS Personalizado para Toggle Switches -->
<style>
    /* Toggle Switch Personalizado */
    .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
    }

    .form-check-input:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
    }

    /* Hover en filas de permisos */
    .permiso-row:hover {
        background-color: #f8f9fa;
    }

    /* Animación para switches */
    .permiso-switch {
        transition: all 0.3s ease;
    }

    .permiso-switch:hover:not(:disabled) {
        transform: scale(1.1);
    }

    /* Estilo para tabla */
    #tablaPermisos thead th {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }

        .permiso-switch {
            width: 2.5rem !important;
            height: 1.25rem !important;
        }
    }
</style>

<!-- JavaScript para Gestión de Permisos -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectRol = document.getElementById('selectRol');
        const btnGuardarPermisos = document.getElementById('btnGuardarPermisos');
        const btnNuevoRol = document.getElementById('btnNuevoRol');
        const switches = document.querySelectorAll('.permiso-switch');
        let rolActual = null;
        let permisosOriginales = [];

        // ========================================
        // EVENTO: Cambio de Rol
        // ========================================
        selectRol.addEventListener('change', function() {
            const rolId = this.value;

            if (!rolId) {
                deshabilitarSwitches();
                btnGuardarPermisos.disabled = true;
                return;
            }

            rolActual = rolId;
            cargarPermisosRol(rolId);
        });

        // ========================================
        // FUNCIÓN: Cargar Permisos del Rol
        // ========================================
        function cargarPermisosRol(rolId) {
            // Mostrar loading
            Swal.fire({
                title: 'Cargando permisos...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`<?php echo URLROOT; ?>/admin/get_permisos_rol/${rolId}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();

                    if (data.success) {
                        permisosOriginales = [...data.permisos];
                        actualizarSwitches(data.permisos);
                        habilitarSwitches();
                        btnGuardarPermisos.disabled = false;
                    } else {
                        Swal.fire('Error', 'No se pudieron cargar los permisos', 'error');
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error de conexión al servidor', 'error');
                });
        }

        // ========================================
        // FUNCIÓN: Actualizar Switches
        // ========================================
        function actualizarSwitches(permisosActivos) {
            switches.forEach(sw => {
                const permisoId = parseInt(sw.dataset.permisoId);
                sw.checked = permisosActivos.includes(permisoId);
            });
        }

        // ========================================
        // FUNCIÓN: Habilitar/Deshabilitar Switches
        // ========================================
        function habilitarSwitches() {
            switches.forEach(sw => sw.disabled = false);
        }

        function deshabilitarSwitches() {
            switches.forEach(sw => {
                sw.disabled = true;
                sw.checked = false;
            });
        }

        // ========================================
        // EVENTO: Guardar Permisos
        // ========================================
        btnGuardarPermisos.addEventListener('click', function() {
            if (!rolActual) {
                Swal.fire('Advertencia', 'Debe seleccionar un rol', 'warning');
                return;
            }

            // Obtener permisos seleccionados
            const permisosSeleccionados = [];
            switches.forEach(sw => {
                if (sw.checked) {
                    permisosSeleccionados.push(parseInt(sw.dataset.permisoId));
                }
            });

            // Confirmar cambios
            Swal.fire({
                title: '¿Guardar cambios?',
                text: `Se actualizarán los permisos del rol seleccionado`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check me-2"></i>Sí, guardar',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    guardarPermisos(rolActual, permisosSeleccionados);
                }
            });
        });

        // ========================================
        // FUNCIÓN: Guardar Permisos (AJAX)
        // ========================================
        function guardarPermisos(rolId, permisos) {
            Swal.fire({
                title: 'Guardando...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`<?php echo URLROOT; ?>/admin/guardar_permisos`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        rol_id: rolId,
                        permisos: permisos,
                        csrf_token: '<?php echo csrf_token(); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        permisosOriginales = [...permisos];
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error al guardar los permisos', 'error');
                });
        }

        // ========================================
        // EVENTO: Nuevo Rol
        // ========================================
        btnNuevoRol.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalNuevoRol'));
            document.getElementById('formNuevoRol').reset();
            modal.show();
        });

        // ========================================
        // EVENTO: Confirmar Nuevo Rol
        // ========================================
        document.getElementById('btnConfirmarNuevoRol').addEventListener('click', function() {
            const nombre = document.getElementById('nombreRol').value.trim();
            const descripcion = document.getElementById('descripcionRol').value.trim();

            if (!nombre) {
                Swal.fire('Advertencia', 'El nombre del rol es obligatorio', 'warning');
                return;
            }

            Swal.fire({
                title: 'Creando rol...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`<?php echo URLROOT; ?>/admin/crear_rol`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        nombre: nombre,
                        descripcion: descripcion,
                        csrf_token: '<?php echo csrf_token(); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Rol creado!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Recargar página para mostrar el nuevo rol
                            location.reload();
                        });

                        // Cerrar modal
                        bootstrap.Modal.getInstance(document.getElementById('modalNuevoRol')).hide();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error al crear el rol', 'error');
                });
        });
    });
</script>
@endsection
