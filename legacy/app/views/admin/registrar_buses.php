<?php
// Opciones de tipo de bus (define asientos y distribucion). Se usan en el alta y en la edicion.
$opcionesTipoBus = '';
foreach ($data['tipos_buses'] ?? [] as $tipo) {
    $opcionesTipoBus .= sprintf(
        '<option value="%d" data-capacidad="%d" data-pisos="%d">%s (%d asientos, %d piso%s)%s</option>',
        $tipo->id, $tipo->capacidad, $tipo->pisos, htmlspecialchars($tipo->nombre), $tipo->capacidad, $tipo->pisos,
        $tipo->pisos > 1 ? 's' : '', $tipo->estado ? '' : ' · tipo inactivo'
    );
}
if ($opcionesTipoBus === '') {
    $opcionesTipoBus = '<option value="" disabled>No hay tipos de buses registrados</option>';
}

// Revision de flota: datos que impiden que la venta muestre el bus real
$revisionFlota = [];
foreach ($data['vehiculos'] ?? [] as $bus) {
    if (empty($bus->tipo_bus_id)) {
        $revisionFlota[] = "<strong>{$bus->placa}</strong>: sin tipo de bus ({$bus->asientos} asientos registrados). Edítelo y elija el tipo; si no existe uno de {$bus->asientos} asientos, créelo en Tipos de Buses.";
    } elseif ((int) $bus->tipo_capacidad !== (int) $bus->asientos) {
        $revisionFlota[] = "<strong>{$bus->placa}</strong>: el tipo {$bus->tipo_nombre} tiene {$bus->tipo_capacidad} asientos pero el bus figura con {$bus->asientos}.";
    }
    if (preg_match('/^(19|20)\d{2}$/', trim((string) $bus->modelo))) {
        $revisionFlota[] = "<strong>{$bus->placa}</strong>: el modelo \"{$bus->modelo}\" es un año; corrija el modelo (p. ej. Paradiso 1800).";
    }
}
?>
<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Gestión de Flota</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="<?php echo URLROOT; ?>/dashboard">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Buses</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!--end::App Content Header-->

    <!--begin::App Content-->
    <div class="app-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-secondary fw-bold"><i class="bi bi-bus-front-fill me-2 text-primary"></i>Lista de Buses</h5>
                            <div>
                                <a href="<?php echo URLROOT; ?>/vehiculos/generarPDF" target="_blank" class="btn btn-outline-danger rounded-pill px-3 shadow-sm me-2">
                                    <i class="bi bi-file-earmark-pdf-fill me-2"></i>Reporte PDF
                                </a>
                                <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="abrirModalWizard()">
                                    <i class="bi bi-plus-lg me-2"></i>Registrar Nuevo Bus
                                </button>
                            </div>
                        </div>
                        <div class="card-body">

                            <!-- Alertas -->
                            <?php if (isset($_GET['msg'])): ?>
                                <div class="alert alert-<?php echo ($_GET['msg'] == 'exito') ? 'success' : 'warning'; ?> alert-dismissible fade show shadow-sm" role="alert">
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?php echo ($_GET['msg'] == 'exito') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> fs-4 me-3"></i>
                                        <div>
                                            <?php
                                            if ($_GET['msg'] == 'exito') echo '<strong>¡Excelente!</strong> El vehículo ha sido registrado correctamente systema.';
                                            elseif ($_GET['msg'] == 'error_duplicado') echo '<strong>¡Atención!</strong> El número de placa ya se encuentra registrado.';
                                            else echo '<strong>Error:</strong> Verifique los datos e intente nuevamente.';
                                            ?>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <script>
                                    if (history.replaceState) history.replaceState(null, null, location.pathname);
                                </script>
                            <?php endif; ?>

                            <?php if ($revisionFlota): ?>
                                <div class="alert alert-warning">
                                    <div class="fw-bold mb-1"><i class="bi bi-clipboard-check me-1"></i> Revisión de flota (<?php echo count($revisionFlota); ?>)</div>
                                    <ul class="mb-0 small">
                                        <?php foreach ($revisionFlota as $item): ?><li><?php echo $item; ?></li><?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <!-- Filtros y Búsqueda -->
                            <div class="row mb-4 g-3">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0 bg-light" placeholder="Buscar por placa, marca o propietario...">
                                    </div>
                                </div>
                                <div class="col-md-2 ms-auto">
                                    <select class="form-select text-secondary">
                                        <option value="10">Mostrar 10</option>
                                        <option value="25">Mostrar 25</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Tabla moderna -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light text-uppercase text-secondary small">
                                        <tr>
                                            <th>Estado</th>
                                            <th>Unidad</th>
                                            <th>Placa</th>
                                            <th>Asientos / tipo</th>
                                            <th>Propietario</th>
                                            <th>Detalles Técnicos</th>
                                            <th>Caracteristicas</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($data['vehiculos'])): ?>
                                            <?php foreach ($data['vehiculos'] as $bus): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge rounded-pill <?php echo ($bus->estado == 1) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                                            <?php echo ($bus->estado == 1) ? 'Activo' : 'Inactivo'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="rounded-circle bg-light p-2 me-3 text-primary">
                                                                <i class="bi bi-bus-front fs-5"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold text-dark">Interno #<?php echo $bus->id; ?></div>
                                                                <div class="small text-muted"><?php echo $bus->marca . ' ' . $bus->modelo; ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="badge bg-dark text-white font-monospace border border-secondary px-2 py-1">
                                                            <?php echo $bus->placa; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo (int) $bus->asientos; ?></strong> asientos
                                                        <?php if (!empty($bus->tipo_nombre)): ?>
                                                            <div class="small <?php echo ((int) $bus->tipo_capacidad === (int) $bus->asientos) ? 'text-muted' : 'text-danger'; ?>">
                                                                <?php echo htmlspecialchars($bus->tipo_nombre); ?> · <?php echo (int) $bus->tipo_pisos; ?> piso<?php echo $bus->tipo_pisos > 1 ? 's' : ''; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="small text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Sin tipo de bus</div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-placeholder me-2" style="width: 30px; height: 30px; background: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #6c757d;">
                                                                <?php echo substr($bus->propietario_nombres, 0, 1); ?>
                                                            </div>
                                                            <div class="small">
                                                                <div><?php echo $bus->propietario_nombres; ?></div>
                                                                <div class="text-muted" style="font-size: 0.75rem;"><?php echo $bus->propietario_apellidos; ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="small text-muted">
                                                        <div><i class="bi bi-calendar me-1"></i> <?php echo $bus->anio; ?></div>
                                                        <div><i class="bi bi-fuel-pump me-1"></i> <?php echo $bus->tipo_combustible; ?></div>
                                                    </td>
                                                    <td class="small">
                                                        <span class="d-inline-block rounded-circle border" style="width: 12px; height: 12px; background-color: <?php echo strtolower($bus->color); ?>; margin-right: 5px;"></span>
                                                        <?php echo $bus->color; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-light text-primary"
                                                            data-id="<?php echo $bus->id; ?>"
                                                            onclick="abrirModalEditar(this)"
                                                            title="Editar Bus">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-light text-danger"
                                                            data-id="<?php echo $bus->id; ?>"
                                                            onclick="eliminarBus(this)"
                                                            title="Eliminar Bus">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5 text-muted">No hay buses registrados.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- WIZARD MODAL FULL -->
<div class="modal fade" id="modalWizardBus" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="modal-header border-0 pb-0 position-relative" style="z-index: 10;">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-4 bg-white opacity-100 rounded-circle shadow-sm p-2" data-bs-dismiss="modal" style="z-index: 20;"></button>
            </div>

            <!-- Body with Wizard -->
            <div class="modal-body p-0">
                <div class="row g-0 h-100">
                    <!-- Sidebar Visual -->
                    <div class="col-lg-3 d-none d-lg-block bg-primary text-white p-5 position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); opacity: 0.9;"></div>
                        <i class="bi bi-bus-front position-absolute" style="font-size: 15rem; bottom: -50px; right: -50px; opacity: 0.1; transform: rotate(-15deg);"></i>

                        <div class="position-relative z-2 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <h3 class="fw-bold mb-4">Registro de Nueva Unidad</h3>
                                <p class="opacity-75">Complete todos los datos requeridos para la ficha técnica del vehículo.</p>
                            </div>

                            <!-- Steps Vertical Indicator on Sidebar -->
                            <div class="steps-vertical">
                                <div class="step-item active" data-step="1">
                                    <div class="step-icon"><i class="bi bi-shield-lock"></i></div>
                                    <div class="step-label">Legal & Propietario</div>
                                </div>
                                <div class="step-item" data-step="2">
                                    <div class="step-icon"><i class="bi bi-tools"></i></div>
                                    <div class="step-label">Ficha Técnica</div>
                                    <div class="step-line"></div>
                                </div>
                                <div class="step-item" data-step="3">
                                    <div class="step-icon"><i class="bi bi-palette"></i></div>
                                    <div class="step-label">Servicio & Visual</div>
                                    <div class="step-line"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Content -->
                    <div class="col-lg-9 bg-white p-5">
                        <form id="wizardForm" action="<?php echo URLROOT; ?>/vehiculos/guardar" method="POST">

                            <!-- Step 1: Identidad -->
                            <div class="wizard-step active" id="step1">
                                <h4 class="fw-bold text-dark mb-4">Identidad y Legalidad</h4>

                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold text-secondary text-uppercase small">Datos del Propietario</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-primary"></i></span>
                                            <input type="text" class="form-control bg-light border-start-0" name="propietario_nombres" placeholder="Nombres" required>
                                            <input type="text" class="form-control bg-light border-start-0" name="propietario_apellidos" placeholder="Apellidos" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary text-uppercase small">Placa del Vehículo <span class="text-danger">*</span></label>
                                        <div class="plate-input-container">
                                            <div class="plate-flag">BOL</div>
                                            <input type="text" class="form-control plate-input" name="placa" placeholder="ABC-1234" maxlength="8" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-secondary text-uppercase small">Tarjeta de Circulación <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-card-text"></i></span>
                                            <input type="text" class="form-control bg-light border-start-0" name="tarjeta_circulacion" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Ficha Técnica Completa -->
                            <div class="wizard-step" id="step2">
                                <h4 class="fw-bold text-dark mb-4">Ficha Técnica Detallada</h4>

                                <!-- Básicos -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small fw-bold">Marca</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-tag"></i></span>
                                            <input type="text" class="form-control" name="marca" list="marcasList" placeholder="Ej. Volvo">
                                            <datalist id="marcasList">
                                                <option value="Mercedes-Benz">
                                                <option value="Volvo">
                                                <option value="Scania">
                                                <option value="Toyota">
                                                <option value="Marcopolo">
                                            </datalist>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small fw-bold">Modelo</label>
                                        <input type="text" class="form-control" name="modelo" placeholder="Modelo">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small fw-bold">Año Fab.</label>
                                        <input type="number" class="form-control" name="anio" min="1990" max="2026" value="2024">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small fw-bold">Clase</label>
                                        <select class="form-select" name="clase">
                                            <option value="Bus">Bus</option>
                                            <option value="Minivan">Minivan</option>
                                            <option value="Vagoneta">Vagoneta</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-secondary small fw-bold">Carrocería</label>
                                        <input type="text" class="form-control" name="carroceria" placeholder="Tipo">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-secondary small fw-bold">Ejes</label>
                                        <input type="number" class="form-control" name="ejes" value="2">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-secondary small fw-bold">Ruedas</label>
                                        <input type="number" class="form-control" name="ruedas" value="6">
                                    </div>
                                </div>

                                <!-- Detalles Mecánicos Collapsible -->
                                <div class="accordion mb-3" id="accordionTecnico">
                                    <div class="accordion-item border-0 shadow-sm rounded-3">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed fw-bold text-primary bg-light rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMecanico">
                                                <i class="bi bi-gear-wide-connected me-2"></i> Motor, Dimensiones y Pesos (Opcional)
                                            </button>
                                        </h2>
                                        <div id="collapseMecanico" class="accordion-collapse collapse" data-bs-parent="#accordionTecnico">
                                            <div class="accordion-body bg-light rounded-bottom-3 mt-1">
                                                <!-- Motor -->
                                                <h6 class="text-uppercase small text-muted fw-bold mb-2">Motor y Serie</h6>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-6">
                                                        <input type="text" class="form-control form-control-sm" name="nro_motor" placeholder="Número de Motor">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control form-control-sm" name="nro_serie" placeholder="Número de Serie / VIN">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="number" class="form-control form-control-sm" name="cilindros" placeholder="Cilindros">
                                                    </div>
                                                </div>
                                                <!-- Dimensiones -->
                                                <h6 class="text-uppercase small text-muted fw-bold mb-2">Dimensiones (metros) y Pesos (kg)</h6>
                                                <div class="row g-2">
                                                    <div class="col-md-2">
                                                        <input type="number" step="0.01" class="form-control form-control-sm" name="longitud" placeholder="Largo">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="number" step="0.01" class="form-control form-control-sm" name="ancho" placeholder="Ancho">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="number" step="0.01" class="form-control form-control-sm" name="altura" placeholder="Alto">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <input type="number" step="0.01" class="form-control form-control-sm" name="peso_seco" placeholder="Peso Seco">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <input type="number" step="0.01" class="form-control form-control-sm" name="peso_bruto" placeholder="Peso Bruto">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Visual & Servicio -->
                            <div class="wizard-step" id="step3">
                                <h4 class="fw-bold text-dark mb-4">Servicio y Características</h4>

                                <!-- Capacidad y Servicio (Crucial) -->
                                <div class="bg-light p-3 rounded-3 mb-4">
                                    <h6 class="fw-bold text-secondary text-uppercase small mb-3"><i class="bi bi-people-fill me-2"></i>Capacidad y Tipo de Servicio</h6>
                                    <div class="row g-3">
                                        <!-- ⭐ NUEVO: Tipo de Bus (CRÍTICO) -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" id="tipoBusSelect" name="tipo_bus_id" required>
                                                    <option value="">Seleccione el tipo de bus...</option>
                                                    <?php echo $opcionesTipoBus; ?>
                                                </select>
                                                <label for="tipoBusSelect">
                                                    <i class="bi bi-bus-front me-1"></i>Tipo de Bus
                                                    <span class="text-danger">*</span>
                                                </label>
                                            </div>
                                            <small class="text-muted mt-1 d-block">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Define si es bus de 1 piso, 2 pisos, ejecutivo, etc.
                                            </small>
                                        </div>

                                        <!-- Clase de Servicio -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" id="servicioInput" name="tipo_servicio">
                                                    <option value="Normal">Normal</option>
                                                    <option value="Semicama">Semicama</option>
                                                    <option value="Leito">Leito / Cama</option>
                                                    <option value="Suite">Suite</option>
                                                </select>
                                                <label for="servicioInput">Clase de Servicio</label>
                                            </div>
                                        </div>

                                        <!-- Total Asientos (Auto-llenado) -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="number" class="form-control" id="asientosInput" name="asientos" placeholder="40" required readonly>
                                                <label for="asientosInput">Total Asientos</label>
                                            </div>
                                            <small class="text-muted mt-1 d-block">
                                                <i class="bi bi-lightbulb me-1"></i>
                                                Sale del tipo de bus (no se edita a mano)
                                            </small>
                                        </div>

                                        <!-- Máx. Pasajeros -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="number" class="form-control" id="pasajerosInput" name="pasajeros" placeholder="40">
                                                <label for="pasajerosInput">Máx. Pasajeros</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6 class="fw-bold text-secondary text-uppercase small mb-3"><i class="bi bi-fuel-pump me-2"></i>Energía</h6>
                                    <div class="row g-3">
                                        <div class="col-md-3 col-6">
                                            <label class="fuel-card">
                                                <input type="radio" name="tipo_combustible" value="Diesel" checked>
                                                <div class="fuel-content">
                                                    <i class="bi bi-fuel-pump-diesel-fill text-dark"></i>
                                                    <span>Diesel</span>
                                                </div>
                                            </label>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <label class="fuel-card">
                                                <input type="radio" name="tipo_combustible" value="Gasolina">
                                                <div class="fuel-content">
                                                    <i class="bi bi-fuel-pump-fill text-warning"></i>
                                                    <span>Gasolina</span>
                                                </div>
                                            </label>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <label class="fuel-card">
                                                <input type="radio" name="tipo_combustible" value="GNV">
                                                <div class="fuel-content">
                                                    <i class="bi bi-fire text-primary"></i>
                                                    <span>GNV</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h6 class="fw-bold text-secondary text-uppercase small mb-3"><i class="bi bi-palette me-2"></i>Apariencia</h6>
                                    <div class="d-flex gap-3">
                                        <label class="color-option">
                                            <input type="radio" name="color" value="Blanco" checked>
                                            <span class="color-circle shadow-sm" style="background: #ffffff; border: 1px solid #ddd;"></span>
                                            <span class="color-name">Blanco</span>
                                        </label>
                                        <label class="color-option">
                                            <input type="radio" name="color" value="Rojo">
                                            <span class="color-circle shadow-sm" style="background: #dc3545;"></span>
                                            <span class="color-name">Rojo</span>
                                        </label>
                                        <label class="color-option">
                                            <input type="radio" name="color" value="Azul">
                                            <span class="color-circle shadow-sm" style="background: #0d6efd;"></span>
                                            <span class="color-name">Azul</span>
                                        </label>
                                        <label class="color-option">
                                            <input type="radio" name="color" value="Negro">
                                            <span class="color-circle shadow-sm" style="background: #212529;"></span>
                                            <span class="color-name">Negro</span>
                                        </label>
                                        <label class="color-option">
                                            <input type="radio" name="color" value="Plata">
                                            <span class="color-circle shadow-sm" style="background: #ced4da;"></span>
                                            <span class="color-name">Plata</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Preview Card -->
                                <div class="alert alert-light border shadow-sm rounded-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-success fs-2 me-3"></i>
                                        <div>
                                            <h6 class="fw-bold mb-0">¡Todo Listo!</h6>
                                            <p class="mb-0 text-muted small">Al finalizar se registrará la unidad en la flota activa.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                                <button type="button" class="btn btn-light text-muted px-4" id="btnPrev" disabled>
                                    <i class="bi bi-arrow-left me-2"></i>Atrás
                                </button>
                                <button type="button" class="btn btn-primary px-5 rounded-pill shadow" id="btnNext">
                                    Siguiente <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                                <button type="submit" class="btn btn-success px-5 rounded-pill shadow d-none" id="btnFinish">
                                    Finalizar Registro <i class="bi bi-check-lg ms-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE EDICIÓN DE BUS -->
<div class="modal fade" id="modalEditarBus" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <!-- Header -->
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Editar Información del Bus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-4">
                <form id="formEditarBus">
                    <!-- Campo oculto para el ID -->
                    <input type="hidden" id="editBusId" name="id">

                    <!-- Sección 1: Identidad y Legalidad -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-secondary text-uppercase small mb-3 pb-2 border-bottom">
                            <i class="bi bi-shield-lock me-2"></i>Identidad y Legalidad
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small">Nombres del Propietario</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="editPropietarioNombres" name="propietario_nombres" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small">Apellidos del Propietario</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="editPropietarioApellidos" name="propietario_apellidos" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small">Placa del Vehículo <span class="text-danger">*</span></label>
                                <div class="plate-input-container">
                                    <div class="plate-flag">BOL</div>
                                    <input type="text" class="form-control plate-input" id="editPlaca" name="placa" maxlength="8" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small">Tarjeta de Circulación <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-card-text"></i></span>
                                    <input type="text" class="form-control" id="editTarjetaCirculacion" name="tarjeta_circulacion" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Ficha Técnica -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-secondary text-uppercase small mb-3 pb-2 border-bottom">
                            <i class="bi bi-tools me-2"></i>Ficha Técnica
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Marca</label>
                                <input type="text" class="form-control" id="editMarca" name="marca" list="marcasList">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Modelo</label>
                                <input type="text" class="form-control" id="editModelo" name="modelo">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Año Fabricación</label>
                                <input type="number" class="form-control" id="editAnio" name="anio" min="1990" max="2026">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Clase</label>
                                <select class="form-select" id="editClase" name="clase">
                                    <option value="Bus">Bus</option>
                                    <option value="Minivan">Minivan</option>
                                    <option value="Vagoneta">Vagoneta</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Carrocería</label>
                                <input type="text" class="form-control" id="editCarroceria" name="carroceria">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-bold">Ejes</label>
                                <input type="number" class="form-control" id="editEjes" name="ejes">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-bold">Ruedas</label>
                                <input type="number" class="form-control" id="editRuedas" name="ruedas">
                            </div>
                        </div>
                    </div>

                    <!-- Sección 3: Detalles Mecánicos (Accordion) -->
                    <div class="mb-4">
                        <div class="accordion" id="accordionEditTecnico">
                            <div class="accordion-item border-0 shadow-sm rounded-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold text-primary bg-light rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditMecanico">
                                        <i class="bi bi-gear-wide-connected me-2"></i> Motor, Dimensiones y Pesos (Opcional)
                                    </button>
                                </h2>
                                <div id="collapseEditMecanico" class="accordion-collapse collapse" data-bs-parent="#accordionEditTecnico">
                                    <div class="accordion-body bg-light rounded-bottom-3 mt-1">
                                        <h6 class="text-uppercase small text-muted fw-bold mb-2">Motor y Serie</h6>
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <input type="text" class="form-control form-control-sm" id="editNroMotor" name="nro_motor" placeholder="Número de Motor">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control form-control-sm" id="editNroSerie" name="nro_serie" placeholder="Número de Serie / VIN">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" class="form-control form-control-sm" id="editCilindros" name="cilindros" placeholder="Cilindros">
                                            </div>
                                        </div>
                                        <h6 class="text-uppercase small text-muted fw-bold mb-2">Dimensiones (metros) y Pesos (kg)</h6>
                                        <div class="row g-2">
                                            <div class="col-md-2">
                                                <input type="number" step="0.01" class="form-control form-control-sm" id="editLongitud" name="longitud" placeholder="Largo">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" step="0.01" class="form-control form-control-sm" id="editAncho" name="ancho" placeholder="Ancho">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" step="0.01" class="form-control form-control-sm" id="editAltura" name="altura" placeholder="Alto">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" step="0.01" class="form-control form-control-sm" id="editPesoSeco" name="peso_seco" placeholder="Peso Seco">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="number" step="0.01" class="form-control form-control-sm" id="editPesoBruto" name="peso_bruto" placeholder="Peso Bruto">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 4: Servicio y Características -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-secondary text-uppercase small mb-3 pb-2 border-bottom">
                            <i class="bi bi-palette me-2"></i>Servicio y Características
                        </h6>

                        <!-- Capacidad y Servicio -->
                        <div class="bg-light p-3 rounded-3 mb-3">
                            <h6 class="fw-bold text-secondary text-uppercase small mb-3">
                                <i class="bi bi-people-fill me-2"></i>Capacidad y Tipo de Servicio
                            </h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small" for="editTipoBus">Tipo de bus <span class="text-danger">*</span></label>
                                    <select class="form-select" id="editTipoBus" name="tipo_bus_id" required>
                                        <option value="">Seleccione el tipo de bus…</option>
                                        <?php echo $opcionesTipoBus; ?>
                                    </select>
                                    <div class="form-text">Define la cantidad y distribución de asientos que se venden.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Total Asientos</label>
                                    <input type="number" class="form-control" id="editAsientos" name="asientos" required readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Máx. Pasajeros</label>
                                    <input type="number" class="form-control" id="editPasajeros" name="pasajeros">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Clase de Servicio</label>
                                    <select class="form-select" id="editTipoServicio" name="tipo_servicio">
                                        <option value="Normal">Normal</option>
                                        <option value="Semicama">Semicama</option>
                                        <option value="Leito">Leito / Cama</option>
                                        <option value="Suite">Suite</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tipo de Combustible -->
                        <div class="mb-3">
                            <h6 class="fw-bold text-secondary text-uppercase small mb-3">
                                <i class="bi bi-fuel-pump me-2"></i>Energía
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-3 col-6">
                                    <label class="fuel-card">
                                        <input type="radio" id="editCombustibleDiesel" name="tipo_combustible" value="Diesel">
                                        <div class="fuel-content">
                                            <i class="bi bi-fuel-pump-diesel-fill text-dark"></i>
                                            <span>Diesel</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-3 col-6">
                                    <label class="fuel-card">
                                        <input type="radio" id="editCombustibleGasolina" name="tipo_combustible" value="Gasolina">
                                        <div class="fuel-content">
                                            <i class="bi bi-fuel-pump-fill text-warning"></i>
                                            <span>Gasolina</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-3 col-6">
                                    <label class="fuel-card">
                                        <input type="radio" id="editCombustibleGNV" name="tipo_combustible" value="GNV">
                                        <div class="fuel-content">
                                            <i class="bi bi-fire text-primary"></i>
                                            <span>GNV</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Color -->
                        <div>
                            <h6 class="fw-bold text-secondary text-uppercase small mb-3">
                                <i class="bi bi-palette me-2"></i>Apariencia
                            </h6>
                            <div class="d-flex gap-3">
                                <label class="color-option">
                                    <input type="radio" id="editColorBlanco" name="color" value="Blanco">
                                    <span class="color-circle shadow-sm" style="background: #ffffff; border: 1px solid #ddd;"></span>
                                    <span class="color-name">Blanco</span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" id="editColorRojo" name="color" value="Rojo">
                                    <span class="color-circle shadow-sm" style="background: #dc3545;"></span>
                                    <span class="color-name">Rojo</span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" id="editColorAzul" name="color" value="Azul">
                                    <span class="color-circle shadow-sm" style="background: #0d6efd;"></span>
                                    <span class="color-name">Azul</span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" id="editColorNegro" name="color" value="Negro">
                                    <span class="color-circle shadow-sm" style="background: #212529;"></span>
                                    <span class="color-name">Negro</span>
                                </label>
                                <label class="color-option">
                                    <input type="radio" id="editColorPlata" name="color" value="Plata">
                                    <span class="color-circle shadow-sm" style="background: #ced4da;"></span>
                                    <span class="color-name">Plata</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary px-5 rounded-pill shadow" id="btnGuardarEdicion">
                    <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos Custom Wizard */
    .steps-vertical {
        margin-top: 3rem;
        position: relative;
        padding-left: 1rem;
    }

    .step-item {
        position: relative;
        padding-bottom: 3rem;
        padding-left: 3rem;
        opacity: 0.5;
        transition: all 0.3s ease;
    }

    .step-item.active {
        opacity: 1;
    }

    .step-icon {
        position: absolute;
        left: 0;
        top: 0;
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.5);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .step-item.active .step-icon {
        background: #fff;
        color: #0d6efd;
        border-color: #fff;
        box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
    }

    .step-label {
        font-weight: 600;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
    }

    .step-line {
        position: absolute;
        left: 19px;
        top: 40px;
        bottom: -10px;
        width: 2px;
        background: rgba(255, 255, 255, 0.2);
    }

    .step-item:last-child .step-line {
        display: none;
    }

    /* Wizard Content Animation */
    .wizard-step {
        display: none;
        animation: fadeIn 0.4s ease-in-out;
    }

    .wizard-step.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Plate Input Style */
    .plate-input-container {
        display: flex;
        border: 2px solid #ced4da;
        border-radius: 8px;
        overflow: hidden;
        transition: border-color 0.2s;
    }

    .plate-input-container:focus-within {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, .25);
    }

    .plate-flag {
        background: #003399;
        color: white;
        padding: 0 15px;
        display: flex;
        align-items: center;
        font-weight: bold;
        font-size: 0.9rem;
        border-right: 1px solid #ced4da;
    }

    .plate-input {
        border: none;
        font-family: 'Courier New', monospace;
        font-weight: 900;
        font-size: 1.5rem;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #333;
        padding: 10px;
        background: #f8f9fa;
        /* Fondo grisáceo estilo placa */
    }

    .plate-input:focus {
        box-shadow: none;
        background: white;
    }

    /* Selection Cards */
    .fuel-card {
        cursor: pointer;
        display: block;
        position: relative;
    }

    .fuel-card input {
        position: absolute;
        opacity: 0;
    }

    .fuel-content {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.2s;
    }

    .fuel-content i {
        font-size: 2rem;
        display: block;
        margin-bottom: 0.5rem;
    }

    .fuel-card input:checked+.fuel-content {
        border-color: #0d6efd;
        background-color: #f0f7ff;
        color: #0d6efd;
    }

    /* Color Circles */
    .color-option {
        cursor: pointer;
        text-align: center;
    }

    .color-option input {
        display: none;
    }

    .color-circle {
        display: block;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        margin: 0 auto 5px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .color-option input:checked+.color-circle {
        transform: scale(1.1);
        box-shadow: 0 0 0 3px #0d6efd !important;
        /* Ring effect */
    }

    .color-name {
        font-size: 0.8rem;
        color: #6c757d;
    }
</style>

<script>
    let currentStep = 1;
    const totalSteps = 3;

    function abrirModalWizard() {
        const modal = new bootstrap.Modal(document.getElementById('modalWizardBus'));
        modal.show();
    }

    // Navegación
    document.getElementById('btnNext').addEventListener('click', () => {
        // Validación simple (requiere HTML5 valid en inputs)
        const currentStepEl = document.getElementById('step' + currentStep);
        const inputs = currentStepEl.querySelectorAll('input, select');
        let valid = true;
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                input.reportValidity();
                valid = false;
            }
        });

        if (valid && currentStep < totalSteps) {
            currentStep++;
            updateWizard();
        }
    });

    document.getElementById('btnPrev').addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateWizard();
        }
    });

    function updateWizard() {
        // Show/Hide Steps
        document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
        document.getElementById('step' + currentStep).classList.add('active');

        // Update Buttons
        document.getElementById('btnPrev').disabled = (currentStep === 1);

        if (currentStep === totalSteps) {
            document.getElementById('btnNext').classList.add('d-none');
            document.getElementById('btnFinish').classList.remove('d-none');
        } else {
            document.getElementById('btnNext').classList.remove('d-none');
            document.getElementById('btnFinish').classList.add('d-none');
        }

        // Update Sidebar Indicators
        document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));
        // Activate current and previous
        for (let i = 1; i <= currentStep; i++) {
            const item = document.querySelector(`.step-item[data-step="${i}"]`);
            if (item) item.classList.add('active');
        }
    }

    // ==========================================
    // 🚀 IMPLEMENTACIÓN AJAX CON SWEETALERT2
    // ==========================================

    const wizardForm = document.getElementById('wizardForm');

    wizardForm.addEventListener('submit', function(e) {
        // 1. Prevenir la recarga de la página
        e.preventDefault();

        // 2. Obtener el botón de submit y deshabilitarlo
        const btnFinish = document.getElementById('btnFinish');
        const btnOriginalText = btnFinish.innerHTML;
        btnFinish.disabled = true;
        btnFinish.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

        // 3. Recopilar los datos del formulario
        const formData = new FormData(this);

        // 4. Enviar la petición AJAX usando Fetch API
        fetch('<?php echo URLROOT; ?>/vehiculos/guardar', {
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
                        title: '¡Registro Exitoso!',
                        text: data.message,
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        customClass: {
                            popup: 'animated fadeInDown'
                        }
                    }).then((result) => {
                        // 6. Redirigir a la lista de buses al hacer clic en OK
                        if (result.isConfirmed) {
                            window.location.href = '<?php echo URLROOT; ?>/admin/registrar_buses';
                        }
                    });
                } else {
                    // ❌ ERROR - Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al Registrar',
                        text: data.message || 'Ocurrió un error inesperado. Por favor, intente nuevamente.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Entendido'
                    });

                    // Restaurar el botón
                    btnFinish.disabled = false;
                    btnFinish.innerHTML = btnOriginalText;
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
                btnFinish.disabled = false;
                btnFinish.innerHTML = btnOriginalText;
            });
    });

    // ==========================================
    // 🔧 FUNCIONALIDAD DE EDICIÓN DE BUSES
    // ==========================================

    /**
     * Función para abrir el modal de edición y cargar los datos del bus
     * @param {HTMLElement} button - El botón que fue clickeado
     */
    function abrirModalEditar(button) {
        const busId = button.getAttribute('data-id');

        if (!busId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo identificar el bus a editar.',
                confirmButtonColor: '#d33'
            });
            return;
        }

        // Mostrar indicador de carga
        Swal.fire({
            title: 'Cargando...',
            text: 'Obteniendo información del bus',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Realizar petición AJAX para obtener los datos del bus
        fetch('<?php echo URLROOT; ?>/vehiculos/obtener_bus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + busId
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
                    document.getElementById('editBusId').value = data.bus.id;
                    document.getElementById('editPropietarioNombres').value = data.bus.propietario_nombres || '';
                    document.getElementById('editPropietarioApellidos').value = data.bus.propietario_apellidos || '';
                    document.getElementById('editPlaca').value = data.bus.placa || '';
                    document.getElementById('editTarjetaCirculacion').value = data.bus.tarjeta_circulacion || '';
                    document.getElementById('editMarca').value = data.bus.marca || '';
                    document.getElementById('editModelo').value = data.bus.modelo || '';
                    document.getElementById('editAnio').value = data.bus.anio || '';
                    document.getElementById('editClase').value = data.bus.clase || 'Bus';
                    document.getElementById('editCarroceria').value = data.bus.carroceria || '';
                    document.getElementById('editEjes').value = data.bus.ejes || '';
                    document.getElementById('editRuedas').value = data.bus.ruedas || '';
                    document.getElementById('editNroMotor').value = data.bus.nro_motor || '';
                    document.getElementById('editNroSerie').value = data.bus.nro_serie || '';
                    document.getElementById('editCilindros').value = data.bus.cilindros || '';
                    document.getElementById('editLongitud').value = data.bus.longitud || '';
                    document.getElementById('editAncho').value = data.bus.ancho || '';
                    document.getElementById('editAltura').value = data.bus.altura || '';
                    document.getElementById('editPesoSeco').value = data.bus.peso_seco || '';
                    document.getElementById('editPesoBruto').value = data.bus.peso_bruto || '';
                    document.getElementById('editTipoBus').value = data.bus.tipo_bus_id || '';
                    document.getElementById('editAsientos').value = data.bus.asientos || '';
                    document.getElementById('editPasajeros').value = data.bus.pasajeros || '';
                    document.getElementById('editTipoServicio').value = data.bus.tipo_servicio || 'Normal';

                    // Seleccionar el tipo de combustible correcto
                    const combustible = data.bus.tipo_combustible || 'Diesel';
                    if (combustible === 'Diesel') {
                        document.getElementById('editCombustibleDiesel').checked = true;
                    } else if (combustible === 'Gasolina') {
                        document.getElementById('editCombustibleGasolina').checked = true;
                    } else if (combustible === 'GNV') {
                        document.getElementById('editCombustibleGNV').checked = true;
                    }

                    // Seleccionar el color correcto
                    const color = data.bus.color || 'Blanco';
                    if (color === 'Blanco') {
                        document.getElementById('editColorBlanco').checked = true;
                    } else if (color === 'Rojo') {
                        document.getElementById('editColorRojo').checked = true;
                    } else if (color === 'Azul') {
                        document.getElementById('editColorAzul').checked = true;
                    } else if (color === 'Negro') {
                        document.getElementById('editColorNegro').checked = true;
                    } else if (color === 'Plata') {
                        document.getElementById('editColorPlata').checked = true;
                    }

                    // Abrir el modal
                    const modal = new bootstrap.Modal(document.getElementById('modalEditarBus'));
                    modal.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo cargar la información del bus.',
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
     * Manejador del evento submit para guardar los cambios
     */
    document.getElementById('btnGuardarEdicion').addEventListener('click', function() {
        const form = document.getElementById('formEditarBus');

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
        fetch('<?php echo URLROOT; ?>/vehiculos/editar_bus', {
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarBus'));
                    modal.hide();

                    // Mostrar SweetAlert2 de éxito
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: data.message,
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

    /**
     * ⭐ NUEVA FUNCIÓN: Eliminar Bus con confirmación
     */
    function eliminarBus(button) {
        const busId = button.getAttribute('data-id');
        const row = button.closest('tr');
        const placaElement = row.querySelector('.badge.bg-dark');
        const placa = placaElement ? placaElement.textContent.trim() : 'este bus';

        // Confirmación con SweetAlert2
        Swal.fire({
            title: '¿Estás seguro?',
            html: `
                <p>Estás a punto de eliminar el bus:</p>
                <p class="fw-bold text-danger">${placa}</p>
                <p class="small text-muted">Esta acción cambiará el estado del bus a inactivo.</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash me-2"></i>Sí, eliminar',
            cancelButtonText: '<i class="bi bi-x-lg me-2"></i>Cancelar',
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-danger px-4',
                cancelButton: 'btn btn-secondary px-4'
            },
            buttonsStyling: false
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
                fetch('<?php echo URLROOT; ?>/vehiculos/eliminar_bus', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'id=' + busId
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
                                text: data.message || 'El bus ha sido eliminado correctamente.',
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
                                text: data.message || 'No se pudo eliminar el bus. Intente nuevamente.',
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

    /**
     * ⭐ NUEVO: Auto-completar asientos según el tipo de bus seleccionado
     */
    document.getElementById('editTipoBus').addEventListener('change', function() {
        const capacidad = this.selectedOptions[0]?.getAttribute('data-capacidad');
        if (capacidad) {
            document.getElementById('editAsientos').value = capacidad;
            const pasajeros = document.getElementById('editPasajeros');
            if (!pasajeros.value || parseInt(pasajeros.value) < parseInt(capacidad)) pasajeros.value = capacidad;
        }
    });

    // Placa siempre en mayusculas y sin espacios
    document.querySelectorAll('input[name="placa"]').forEach(input => {
        input.addEventListener('input', () => { input.value = input.value.toUpperCase().replace(/\s+/g, ''); });
    });

    document.getElementById('tipoBusSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const capacidad = selectedOption.getAttribute('data-capacidad');
        const pisos = selectedOption.getAttribute('data-pisos');
        const nombreTipo = selectedOption.text;

        if (capacidad) {
            // Auto-llenar los campos de asientos y pasajeros
            document.getElementById('asientosInput').value = capacidad;
            document.getElementById('pasajerosInput').value = capacidad;

            // Feedback visual con SweetAlert
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: 'info',
                title: `Bus de ${pisos} piso${pisos > 1 ? 's' : ''}`,
                text: `Capacidad: ${capacidad} asientos`
            });

            // Log para debugging
            console.log('✅ Tipo de bus seleccionado:', {
                tipo: nombreTipo,
                capacidad: capacidad,
                pisos: pisos
            });
        }
    });
</script>