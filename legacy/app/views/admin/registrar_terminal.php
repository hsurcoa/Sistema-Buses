<!-- Contenedor Principal -->
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-12"> <!-- Full width as per image -->

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-secondary text-uppercase"><i class="bi bi-building me-2"></i>Registrar Sedes</h6>
                </div>
                <div class="card-body">

                    <!-- Formulario Superior -->
                    <form action="<?php echo URLROOT; ?>/admin/guardar_terminal" method="POST">
                        <input type="hidden" name="id" id="terminalId" value="">
                        <div class="row g-3 align-items-end mb-4">
                            <!-- Nombre de Sede -->
                            <div class="col-md-4">
                                <label for="nombreSede" class="form-label text-uppercase fw-bold text-secondary small">Nombre de Sede:</label>
                                <input type="text" class="form-control" id="nombreSede" name="nombre_sede" required>
                            </div>

                            <!-- Dirección -->
                            <div class="col-md-5">
                                <label for="direccionSede" class="form-label text-uppercase fw-bold text-secondary small">Dirección:</label>
                                <input type="text" class="form-control" id="direccionSede" name="direccion" required>
                            </div>

                            <!-- N° Oficina -->
                            <div class="col-md-2">
                                <label for="numOficina" class="form-label text-uppercase fw-bold text-secondary small">N° Oficina:</label>
                                <input type="text" class="form-control" id="numOficina" name="numero_oficina" required>
                            </div>

                            <!-- Boton Guardar -->
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary fw-bold w-100 p-2">
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </form>

                    <hr class="text-muted opacity-25">

                    <!-- Titulo Tabla -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-secondary text-uppercase"><i class="bi bi-list-ul me-2"></i> Lista Sedes</h6>
                    </div>

                    <!-- Buscador y Paginacion Top -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-secondary">Buscar:</span>
                                <input type="text" class="form-control border-start-0 ps-0" placeholder="">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex justify-content-end align-items-center">
                            <label class="me-2 text-secondary">Lista:</label>
                            <select class="form-select w-auto">
                                <option>10</option>
                                <option>25</option>
                                <option>50</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <!-- Teal Header matching the image -->
                            <thead class="text-white" style="background-color: #20c997;">
                                <tr class="text-center text-uppercase small fw-bold">
                                    <th class="py-3">Terminal <i class="bi bi-chevron-expand float-end"></i></th>
                                    <th class="py-3">Dirección <i class="bi bi-chevron-expand float-end"></i></th>
                                    <th class="py-3">N° Oficina <i class="bi bi-chevron-expand float-end"></i></th>
                                    <th class="py-3">Estado <i class="bi bi-chevron-expand float-end"></i></th>
                                    <th class="py-3"><i class="bi bi-gear-fill"></i></th>
                                </tr>
                            </thead>
                            <tbody class="text-center text-secondary small bg-white">
                                <?php if (!empty($data['terminales'])): ?>
                                    <?php foreach ($data['terminales'] as $terminal): ?>
                                        <tr>
                                            <td class="text-start ps-4"><?php echo $terminal->nombre_sede; ?></td>
                                            <td class="text-start ps-4"><?php echo $terminal->direccion; ?></td>
                                            <td><?php echo $terminal->numero_oficina; ?></td>
                                            <td>
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" role="switch" onchange="toggleEstado(<?php echo $terminal->id; ?>)" <?php echo ($terminal->estado == 1) ? 'checked' : ''; ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="javascript:void(0);" class="text-primary fs-5 me-2" onclick='editarTerminal(<?php echo json_encode($terminal); ?>)'>
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="javascript:void(0);" class="text-danger fs-5" onclick="confirmarEliminacion(<?php echo $terminal->id; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="py-4">No hay sedes registradas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginacion Bottom -->
                    <div class="row mt-3 align-items-center">
                        <div class="col-md-6 small text-secondary">
                            Mostrando del 1 al <?php echo count($data['terminales'] ?? []); ?> de un total de <?php echo count($data['terminales'] ?? []); ?> registros
                        </div>
                        <div class="col-md-6">
                            <nav>
                                <ul class="pagination justify-content-end mb-0">
                                    <li class="page-item disabled"><a class="page-link" href="#">&larr;</a></li>
                                    <li class="page-item active"><a class="page-link bg-secondary border-secondary" href="#">1</a></li>
                                    <!-- Using dark/secondary for active page to match image dark square -->
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
    function editarTerminal(terminal) {
        document.getElementById('terminalId').value = terminal.id;
        document.getElementById('nombreSede').value = terminal.nombre_sede;
        document.getElementById('direccionSede').value = terminal.direccion;
        document.getElementById('numOficina').value = terminal.numero_oficina;
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function toggleEstado(id) {
        if (confirm('¿Está seguro de cambiar el estado?')) {
            window.location.href = '<?php echo URLROOT; ?>/admin/cambiar_estado_terminal/' + id;
        }
    }

    function confirmarEliminacion(id) {
        Swal.fire({
            title: "¿Estás seguro?",
            text: "¡No podrás revertir esto! Se eliminará la sede permanentemente.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?php echo URLROOT; ?>/admin/eliminar_terminal/' + id;
            }
        });
    }
</script>