@extends('layouts.app')

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="<?php echo URLROOT; ?>/dashboard">Inicio</a></li>
                        <li class="breadcrumb-item">Registros</li>
                        <li class="breadcrumb-item active" aria-current="page">Series Boletos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="app-content">
        <div class="container-fluid">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <!-- Header with Icon -->
                    <div class="d-flex align-items-center mb-4">
                        <i class="bi bi-ticket-perforated-fill me-2 fs-4 text-secondary"></i>
                        <h5 class="mb-0 fw-bold text-uppercase text-secondary">REGISTRAR SERIES DE BOLETOS</h5>
                    </div>

                    <!-- Form -->
                    <!-- Updated action to point to Series controller -->
                    <form action="<?php echo URLROOT; ?>/series/guardar" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="id" id="id">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label for="vendedor_id" class="form-label text-uppercase fw-semibold" style="font-size: 0.85rem; color: #555;">VENDEDOR:</label>
                                <select class="form-select" name="vendedor_id" id="vendedor_id" required>
                                    <option value="">Seleccione</option>
                                    <!-- Foreach para Vendedores -->
                                    <?php if (isset($data['vendedores'])): ?>
                                        <?php foreach ($data['vendedores'] as $vendedor): ?>
                                            <option value="<?php echo $vendedor->id; ?>"><?php echo $vendedor->nombre_completo; ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="sede_id" class="form-label text-uppercase fw-semibold" style="font-size: 0.85rem; color: #555;">SEDE:</label>
                                <select class="form-select" name="sede_id" id="sede_id" required>
                                    <option value="">Seleccione</option>
                                    <!-- Foreach para Sedes -->
                                    <?php if (isset($data['sedes'])): ?>
                                        <?php foreach ($data['sedes'] as $sede): ?>
                                            <option value="<?php echo $sede->id; ?>"><?php echo $sede->nombre_sede; ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="nro_serie" class="form-label text-uppercase fw-semibold" style="font-size: 0.85rem; color: #555;">N° SERIE:</label>
                                <input type="text" class="form-control" name="nro_serie" id="nro_serie" required placeholder="">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary px-4 bg-primary border-0" style="background-color: #0d6efd;">Guardar</button>
                        </div>
                    </form>

                    <hr class="my-5 opacity-25">

                    <!-- List Section -->
                    <div class="d-flex align-items-center mb-4">
                        <i class="bi bi-list-ul me-2 fs-4"></i>
                        <h6 class="mb-0 fw-bold text-uppercase">LISTA DE SERIES POR SUCURSAL</h6>
                    </div>

                    <!-- Search Filter (Layout only) -->
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6 d-flex align-items-center">
                            <label class="me-2 text-muted">Buscar:</label>
                            <input type="search" class="form-control form-control-sm w-50" id="searchInput">
                        </div>
                        <div class="col-md-6 d-flex align-items-center justify-content-end">
                            <label class="me-2 text-muted">Lista:</label>
                            <select class="form-select form-select-sm w-auto" id="lengthSelect">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="text-white" style="background-color: #1abc9c;">
                                <tr>
                                    <th scope="col" class="text-center py-3">SEDE <i class="bi bi-chevron-expand float-end" style="font-size: 0.7rem;"></i></th>
                                    <th scope="col" class="text-center py-3">VENDEDOR <i class="bi bi-chevron-expand float-end" style="font-size: 0.7rem;"></i></th>
                                    <th scope="col" class="text-center py-3">N° DE SERIE <i class="bi bi-chevron-expand float-end" style="font-size: 0.7rem;"></i></th>
                                    <th scope="col" class="text-center py-3">ESTADO <i class="bi bi-chevron-expand float-end" style="font-size: 0.7rem;"></i></th>
                                    <th scope="col" class="text-center py-3"><i class="bi bi-gear-fill"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Foreach lista_series -->
                                <?php if (isset($data['lista_series']) && count($data['lista_series']) > 0): ?>
                                    <?php foreach ($data['lista_series'] as $serie): ?>
                                        <tr>
                                            <!-- Imprimir nombres reales -->
                                            <td class="text-center py-3"><?php echo $serie->nombre_sede; ?></td>
                                            <td class="text-center py-3"><?php echo $serie->vendedor_nombre; ?></td>
                                            <td class="text-center py-3"><?php echo $serie->numero_serie; ?></td>
                                            <td class="text-center py-3">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <a href="<?php echo URLROOT; ?>/series/cambiar_estado/<?php echo $serie->id; ?>">
                                                        <input class="form-check-input" type="checkbox" role="switch" <?php echo $serie->estado ? 'checked' : ''; ?> style="cursor: pointer;">
                                                    </a>
                                                </div>
                                            </td>
                                            <td class="text-center py-3">
                                                <button type="button" class="btn btn-link p-0 text-primary btn-editar"
                                                    data-id="<?php echo $serie->id; ?>"
                                                    data-vendedor="<?php echo $serie->usuario_id; ?>"
                                                    data-sede="<?php echo $serie->sede_id; ?>"
                                                    data-serie="<?php echo $serie->numero_serie; ?>">
                                                    <i class="bi bi-pencil-square fs-5"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No se encontraron registros</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            Mostrando del 1 al <?php echo isset($data['lista_series']) ? count($data['lista_series']) : 0; ?> de un total de <?php echo isset($data['lista_series']) ? count($data['lista_series']) : 0; ?> registros
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item disabled">
                                    <a class="page-link border-0 text-secondary bg-transparent" href="#">&larr;</a>
                                </li>
                                <li class="page-item active bg-secondary border-0">
                                    <a class="page-link bg-dark border-0" href="#">1</a>
                                </li>
                                <li class="page-item disabled">
                                    <a class="page-link border-0 text-secondary bg-transparent" href="#">&rarr;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.btn-editar');
        const form = document.querySelector('form');
        const idInput = document.getElementById('id');
        const vendedorInput = document.getElementById('vendedor_id');
        const sedeInput = document.getElementById('sede_id');
        const serieInput = document.getElementById('nro_serie');

        editButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const vendedor = this.getAttribute('data-vendedor');
                const sede = this.getAttribute('data-sede');
                const serie = this.getAttribute('data-serie');

                idInput.value = id;
                vendedorInput.value = vendedor;
                sedeInput.value = sede;
                serieInput.value = serie;

                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                form.classList.add('bg-light');
                setTimeout(() => form.classList.remove('bg-light'), 300);
            });
        });
    });
</script>
@endsection
