@extends('layouts.app')

@section('content')

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Gestión de Encomiendas</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Envíos Recientes</h3>
                    <div class="card-tools">
                        <a href="<?php echo URLROOT; ?>/encomiendas/crear" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Nuevo Envío
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Guía</th>
                                    <th>Fecha</th>
                                    <th>Ruta</th>
                                    <th>Remitente</th>
                                    <th>Destinatario</th>
                                    <th>Contenido</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data['encomiendas'])): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No hay encomiendas registradas</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data['encomiendas'] as $enc): ?>
                                        <tr>
                                            <td><span class="badge bg-dark"><?php echo $enc->codigo_guia; ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($enc->fecha_registro)); ?></td>
                                            <td><?php echo $enc->origen . ' - ' . $enc->destino; ?></td>
                                            <td><?php echo $enc->remitente_nombre; ?></td>
                                            <td><?php echo $enc->destinatario_nombre; ?></td>
                                            <td><?php echo $enc->contenido_breve; ?></td>
                                            <td>
                                                <?php
                                                $badge = 'bg-secondary';
                                                if ($enc->estado == 'REGISTRADO') $badge = 'bg-info';
                                                if ($enc->estado == 'EN_RUTA') $badge = 'bg-primary';
                                                if ($enc->estado == 'ENTREGADO') $badge = 'bg-success';
                                                ?>
                                                <span class="badge <?php echo $badge; ?>"><?php echo $enc->estado; ?></span>
                                            </td>
                                            <td>
                                                <a href="<?php echo URLROOT; ?>/encomiendas/recibo/<?php echo $enc->id; ?>" class="btn btn-sm btn-secondary" title="Imprimir Recibo">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                                <!-- Aquí se podrían agregar botones de cambio de estado -->
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@endsection
