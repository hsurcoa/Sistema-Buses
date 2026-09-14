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
                            <li class="breadcrumb-item">Ventas</li>
                            <li class="breadcrumb-item active">Crear Rutas</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="mb-0">
                            <i class="bi bi-plus-circle-fill text-primary me-2"></i>
                            Crear Rutas de Viaje
                        </h1>
                        <button class="btn btn-primary" onclick="abrirModalNuevaRuta()">
                            <i class="bi bi-plus-lg me-2"></i>Nueva Ruta
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de Resumen -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Rutas Activas</h6>
                                    <h2 class="mb-0">
                                        <?php echo count($data['rutas'] ?? []); ?>
                                    </h2>
                                </div>
                                <i class="bi bi-map" style="font-size: 3rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Tipos de Buses</h6>
                                    <h2 class="mb-0">
                                        <?php echo count($data['tiposBuses'] ?? []); ?>
                                    </h2>
                                </div>
                                <i class="bi bi-bus-front" style="font-size: 3rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Terminales</h6>
                                    <h2 class="mb-0">
                                        <?php echo count($data['terminales'] ?? []); ?>
                                    </h2>
                                </div>
                                <i class="bi bi-building" style="font-size: 3rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Viajes Hoy</h6>
                                    <h2 class="mb-0">0</h2>
                                </div>
                                <i class="bi bi-calendar-check" style="font-size: 3rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Rutas Programadas -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-list-ul me-2"></i>
                                Rutas Programadas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Venta</th>
                                            <th>Ruta</th>
                                            <th>Fecha/Hora Salida</th>
                                            <th>Hora Llegada</th>
                                            <th>Bus</th>
                                            <th>Precio</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- DEBUG: Descomentar para ver datos crudos -->
                                        <!-- <?php // var_dump($data['viajesProgramados']); 
                                                ?> -->

                                        <?php if (!empty($data['viajesProgramados'])): ?>
                                            <?php foreach ($data['viajesProgramados'] as $viaje): ?>
                                                <tr>
                                                    <td class="align-middle text-center">
                                                        <button class="btn btn-info text-white btn-sm rounded-circle shadow hover-bounce p-2" onclick="abrirVenta(<?php echo $viaje->id; ?>)" title="Venta Rápida">
                                                            <i class="bi bi-bus-front-fill fs-5"></i>
                                                        </button>
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold fs-6"><?php echo htmlspecialchars($viaje->origen); ?> <i class="bi bi-arrow-right small text-muted"></i> <?php echo htmlspecialchars($viaje->destino); ?></span>
                                                            <small class="text-muted">
                                                                <i class="bi bi-geo-alt-fill text-danger small"></i> <?php echo htmlspecialchars($viaje->terminal_origen ?? 'Sin Terminal'); ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold text-primary"><i class="bi bi-calendar-event me-1"></i><?php echo date('d/m/Y', strtotime($viaje->fecha_salida)); ?></span>
                                                            <span class="small text-muted"><i class="bi bi-clock me-1"></i><?php echo substr($viaje->hora_salida, 0, 5); ?> hrs</span>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="fw-semibold text-secondary"><i class="bi bi-flag me-1"></i><?php echo $viaje->hora_llegada ? substr($viaje->hora_llegada, 0, 5) : '--:--'; ?></span>
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold"><?php echo htmlspecialchars($viaje->tipo_bus ?? 'Estándar'); ?></span>
                                                            <?php if (isset($viaje->bus_placa)): ?>
                                                                <small class="badge bg-warning text-dark mt-1" style="width: fit-content;">Bus #<?php echo $viaje->bus_numero ?? '?'; ?> (<?php echo $viaje->bus_placa; ?>)</small>
                                                            <?php else: ?>
                                                                <small class="text-muted fst-italic">Sin unidad asignada</small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="d-flex flex-column align-items-end">
                                                            <span class="fw-bold text-success fs-6">Bs. <?php echo number_format($viaje->precio_base, 2); ?></span>
                                                            <span class="badge bg-light text-secondary border"><?php echo htmlspecialchars($viaje->tipo_servicio ?? 'Normal'); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle text-center">
                                                        <?php
                                                        $estadoClass = match (strtolower($viaje->estado)) {
                                                            'activo', 'en_ruta' => 'bg-success',
                                                            'programado' => 'bg-info text-dark',
                                                            'cancelado', 'inactivo' => 'bg-danger',
                                                            'finalizado' => 'bg-secondary',
                                                            default => 'bg-secondary'
                                                        };
                                                        ?>
                                                        <span class="badge <?php echo $estadoClass; ?> rounded-pill px-3 py-2">
                                                            <?php echo ucfirst($viaje->estado); ?>
                                                        </span>
                                                    </td>
                                                    <td class="align-middle text-center">
                                                        <div class="d-inline-flex gap-2">
                                                            <button type="button" class="btn btn-sm btn-white text-secondary border shadow-sm rounded-3 btn-editar-ruta" data-id="<?php echo $viaje->id; ?>" title="Editar Ruta">
                                                                <i class="bi bi-pencil-square text-warning fs-6"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-white text-secondary border shadow-sm rounded-3 btn-despachar-ruta" data-id="<?php echo $viaje->id; ?>" title="Despachar Bus (Finalizar Viaje)">
                                                                <i class="bi bi-check-circle-fill text-success fs-6"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-white text-secondary border shadow-sm rounded-3 btn-eliminar-ruta" data-id="<?php echo $viaje->id; ?>" title="Eliminar Ruta">
                                                                <i class="bi bi-trash3 text-danger fs-6"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <div class="d-flex flex-column align-items-center justify-content-center opacity-75">
                                                        <div class="bg-light rounded-circle p-4 mb-3">
                                                            <i class="bi bi-calendar-x text-secondary" style="font-size: 3rem;"></i>
                                                        </div>
                                                        <h5 class="text-muted fw-bold">No hay viajes programados</h5>
                                                        <p class="text-muted small mb-4" style="max-width: 400px;">
                                                            Actualmente no tienes viajes registrados. Comienza programando un viaje usando una de tus <?php echo count($data['rutas'] ?? []); ?> rutas activas.
                                                        </p>
                                                        <button class="btn btn-primary px-4 py-2 shadow-sm rounded-pill" onclick="abrirModalNuevaRuta()">
                                                            <i class="bi bi-plus-lg me-2"></i>Programar Nuevo Viaje
                                                        </button>
                                                    </div>
                                                </td>
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
</div>

<!-- Modal para Nueva Ruta - Diseño Mejorado -->
<div class="modal fade" id="modalNuevaRuta" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header minimalista moderno -->
            <div class="modal-header border-0 pb-0 pt-4 px-4 bg-white rounded-top-5">
                <div>
                    <h4 class="modal-title fw-bold text-dark" style="font-family: 'Inter', sans-serif; letter-spacing: -0.5px;">
                        <i class="bi bi-geo-alt-fill text-primary me-2"></i>Nueva Ruta
                    </h4>
                    <p class="text-muted small mb-0 mt-1">Configure los detalles del viaje programado</p>
                </div>
                <button type="button" class="btn-close rounded-circle bg-light p-2 shadow-sm" data-bs-dismiss="modal" style="opacity: 1;"></button>
            </div>

            <div class="modal-body p-4" style="background: #f8f9fa;">
                <form id="formNuevaRuta" action="<?php echo URLROOT; ?>/ventas/guardar_ruta_viaje" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="id" id="rutaViajeId">

                    <!-- Tabs de navegación -->
                    <ul class="nav nav-pills mb-4 bg-white rounded-3 p-2 shadow-sm" id="rutaTabs" role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link active w-100 rounded-3" id="info-basica-tab" data-bs-toggle="pill" data-bs-target="#info-basica" type="button">
                                <i class="bi bi-info-circle me-2"></i>Información Básica
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 rounded-3" id="horarios-tab" data-bs-toggle="pill" data-bs-target="#horarios" type="button">
                                <i class="bi bi-clock me-2"></i>Horarios y Terminales
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100 rounded-3" id="servicios-tab" data-bs-toggle="pill" data-bs-target="#servicios" type="button">
                                <i class="bi bi-star me-2"></i>Servicios y Precio
                            </button>
                        </li>
                    </ul>

                    <!-- Contenido de los tabs -->
                    <div class="tab-content" id="rutaTabsContent">
                        <!-- Tab 1: Información Básica -->
                        <div class="tab-pane fade show active" id="info-basica" role="tabpanel">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4 text-primary">
                                        <i class="bi bi-map me-2"></i>Detalles de la Ruta
                                    </h5>

                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="form-floating mb-1">
                                                <select class="form-select border-0 bg-light fw-bold text-dark" id="rutaSelect" name="ruta_id" required style="border-radius: 12px;">
                                                    <option value="">Seleccione ruta...</option>
                                                    <?php if (!empty($data['rutas'])): ?>
                                                        <?php foreach ($data['rutas'] as $ruta): ?>
                                                            <option value="<?php echo $ruta->id; ?>" data-origen="<?php echo htmlspecialchars($ruta->origen); ?>" data-destino="<?php echo htmlspecialchars($ruta->destino); ?>">
                                                                <?php echo htmlspecialchars($ruta->origen . ' → ' . $ruta->destino); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                                <label for="rutaSelect" class="text-muted"><i class="bi bi-signpost-2 me-1"></i>Ruta de Viaje</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating mb-1">
                                                <select class="form-select border-0 bg-light fw-bold text-dark" id="tipoBusSelect" name="tipo_bus_id" required style="border-radius: 12px;">
                                                    <option value="">Seleccione bus...</option>
                                                    <?php if (!empty($data['tiposBuses'])): ?>
                                                        <?php foreach ($data['tiposBuses'] as $tipo): ?>
                                                            <option value="<?php echo $tipo->id; ?>" data-capacidad="<?php echo $tipo->capacidad; ?>">
                                                                <?php echo htmlspecialchars($tipo->nombre); ?> (<?php echo $tipo->capacidad; ?> as.)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                                <label for="tipoBusSelect" class="text-muted"><i class="bi bi-bus-front me-1"></i>Tipo de Bus</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating mb-1">
                                                <input type="date" class="form-control border-0 bg-light fw-bold" id="fechaSalida" name="fecha_salida" required style="border-radius: 12px;">
                                                <label for="fechaSalida" class="text-muted"><i class="bi bi-calendar-event me-1"></i>Fecha de Salida</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating mb-1">
                                                <select class="form-select border-0 bg-light fw-bold" id="estadoRuta" name="estado" style="border-radius: 12px;">
                                                    <option value="Activo" selected>Activo</option>
                                                    <option value="Programado">Programado</option>
                                                    <option value="Inactivo">Inactivo</option>
                                                </select>
                                                <label for="estadoRuta" class="text-muted"><i class="bi bi-toggle-on me-1"></i>Estado</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ✅ NUEVO BLOQUE: Asignación de Recursos -->
                                <div class="col-12">
                                    <div class="card bg-white border shadow-sm" style="border-radius: 12px; border-left: 4px solid #4f46e5 !important;">
                                        <div class="card-body p-3">
                                            <h6 class="text-primary fw-bold mb-3 small text-uppercase ls-1">
                                                <i class="bi bi-person-badge-fill me-2"></i>Asignación de Recursos (Operaciones)
                                            </h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <select class="form-select border bg-light" id="selectBus" name="bus_id" onchange="cargarTripulacion(this.value)">
                                                            <option value="">Seleccione primero el Tipo de Bus...</option>
                                                        </select>
                                                        <label for="selectBus" class="text-muted">Unidad / Bus</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-floating">
                                                        <!-- Se carga dinámicamente con AJAX pero permitimos edición si no es readonly -->
                                                        <select class="form-select border bg-light" id="selectChofer" name="chofer_id">
                                                            <option value="">Esperando selección de bus...</option>
                                                            <!-- Opcional: Cargar lista completa de choferes si se desea permitir cambio manual -->
                                                            <?php if (!empty($data['choferes'])): // Asumiendo que pasamos choferes a la vista 
                                                            ?>
                                                                <?php foreach ($data['choferes'] as $chofer): ?>
                                                                    <option value="<?php echo $chofer->id; ?>"><?php echo $chofer->nombres . ' ' . $chofer->apellidos; ?></option>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </select>
                                                        <label for="selectChofer" class="text-muted">Conductor Principal</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información visual de la ruta -->
                                <div class="col-12">
                                    <div class="p-3 bg-light rounded-3 border" id="rutaPreview" style="display: none;">
                                        <div class="row align-items-center">
                                            <div class="col-md-5 text-center">
                                                <div class="badge bg-primary mb-2">Origen</div>
                                                <h5 class="mb-0" id="origenText">-</h5>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <i class="bi bi-arrow-right-circle-fill text-primary" style="font-size: 2rem;"></i>
                                            </div>
                                            <div class="col-md-5 text-center">
                                                <div class="badge bg-success mb-2">Destino</div>
                                                <h5 class="mb-0" id="destinoText">-</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Horarios y Terminales -->
                        <div class="tab-pane fade" id="horarios" role="tabpanel">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4 text-primary">
                                        <i class="bi bi-clock-history me-2"></i>Programación de Horarios
                                    </h5>

                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="form-floating mb-1">
                                                <select class="form-select border shadow-sm fw-bold" id="terminalOrigen" name="terminal_origen_id" required style="border-radius: 12px; background-color: #f8f9fa;">
                                                    <option value="">Terminal Origen...</option>
                                                    <?php if (!empty($data['terminales'])): ?>
                                                        <?php foreach ($data['terminales'] as $terminal): ?>
                                                            <option value="<?php echo $terminal->id; ?>"><?php echo htmlspecialchars($terminal->nombre_sede); ?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                                <label for="terminalOrigen" class="text-muted"><i class="bi bi-building me-1"></i>Origen</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating mb-1">
                                                <select class="form-select border shadow-sm fw-bold" id="terminalDestino" name="terminal_destino_id" required style="border-radius: 12px; background-color: #f8f9fa;">
                                                    <option value="">Terminal Destino...</option>
                                                    <?php if (!empty($data['terminales'])): ?>
                                                        <?php foreach ($data['terminales'] as $terminal): ?>
                                                            <option value="<?php echo $terminal->id; ?>"><?php echo htmlspecialchars($terminal->nombre_sede); ?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                                <label for="terminalDestino" class="text-muted"><i class="bi bi-building-check me-1"></i>Destino</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating mb-1">
                                                <input type="time" class="form-control border shadow-sm fw-bold" id="horaSalida" name="hora_salida" required style="border-radius: 12px; background-color: #f8f9fa;">
                                                <label for="horaSalida" class="text-muted"><i class="bi bi-clock me-1"></i>Salida</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating mb-1">
                                                <input type="time" class="form-control border shadow-sm fw-bold" id="horaLlegada" name="hora_llegada" required style="border-radius: 12px; background-color: #f8f9fa;">
                                                <label for="horaLlegada" class="text-muted"><i class="bi bi-clock-history me-1"></i>Llegada</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-floating mb-1">
                                                <input type="text" class="form-control border shadow-sm fw-bold text-muted" id="duracionViaje" readonly placeholder="Automático" style="border-radius: 12px; background-color: #e9ecef;">
                                                <label for="duracionViaje" class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Duración</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Timeline visual -->
                                    <div class="mt-4 p-4 bg-gradient rounded-3" style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 text-center">
                                                <div class="mb-2">
                                                    <i class="bi bi-play-circle-fill text-success" style="font-size: 2.5rem;"></i>
                                                </div>
                                                <h6 class="fw-bold">Salida</h6>
                                                <p class="mb-0 text-muted" id="salidaInfo">--:--</p>
                                            </div>
                                            <div class="col-md-4 text-center">
                                                <div class="position-relative">
                                                    <div class="border-top border-3 border-primary"></div>
                                                    <i class="bi bi-bus-front-fill text-primary position-absolute top-50 start-50 translate-middle bg-white px-2" style="font-size: 1.5rem;"></i>
                                                </div>
                                                <p class="mt-3 mb-0 text-muted small" id="duracionInfo">Duración: --</p>
                                            </div>
                                            <div class="col-md-4 text-center">
                                                <div class="mb-2">
                                                    <i class="bi bi-flag-fill text-danger" style="font-size: 2.5rem;"></i>
                                                </div>
                                                <h6 class="fw-bold">Llegada</h6>
                                                <p class="mb-0 text-muted" id="llegadaInfo">--:--</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Servicios y Precio -->
                        <div class="tab-pane fade" id="servicios" role="tabpanel">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4 text-primary">
                                        <i class="bi bi-currency-dollar me-2"></i>Tarifas y Servicios
                                    </h5>

                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="form-floating mb-1">
                                                <input type="number" class="form-control border shadow-sm fw-bold text-success" id="precioBase" name="precio_base" step="0.01" min="0" placeholder="0.00" required style="border-radius: 12px; font-size: 1.2rem; background-color: #f8f9fa;">
                                                <label for="precioBase" class="text-muted"><i class="bi bi-cash-stack me-1"></i>Precio Base (Bs.)</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating mb-1">
                                                <select class="form-select border shadow-sm fw-bold" id="tipoServicio" name="tipo_servicio" style="border-radius: 12px; background-color: #f8f9fa;">
                                                    <option value="Ejecutivo" selected>Ejecutivo</option>
                                                    <option value="Platino">Platino - Premium</option>
                                                    <option value="Especial">Especial - VIP</option>
                                                    <option value="Economico">Económico</option>
                                                </select>
                                                <label for="tipoServicio" class="text-muted"><i class="bi bi-star me-1"></i>Tipo de Servicio</label>
                                            </div>
                                        </div>

                                        <!-- Servicios incluidos -->
                                        <div class="col-12 mt-4">
                                            <label class="form-label fw-bold text-muted small text-uppercase mb-3 ps-1">Servicios Incluidos</label>
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <div class="form-check form-switch p-3 bg-light rounded-4 border-0 d-flex align-items-center gap-2">
                                                        <input class="form-check-input mt-0" type="checkbox" id="servicioWifi" name="servicios[]" value="wifi" checked>
                                                        <label class="form-check-label small fw-semibold cursor-pointer mb-0" for="servicioWifi">WiFi</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check form-switch p-3 bg-light rounded-4 border-0 d-flex align-items-center gap-2">
                                                        <input class="form-check-input mt-0" type="checkbox" id="servicioAC" name="servicios[]" value="ac" checked>
                                                        <label class="form-check-label small fw-semibold cursor-pointer mb-0" for="servicioAC">A/C</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check form-switch p-3 bg-light rounded-4 border-0 d-flex align-items-center gap-2">
                                                        <input class="form-check-input mt-0" type="checkbox" id="servicioTV" name="servicios[]" value="tv">
                                                        <label class="form-check-label small fw-semibold cursor-pointer mb-0" for="servicioTV">TV</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check form-switch p-3 bg-light rounded-4 border-0 d-flex align-items-center gap-2">
                                                        <input class="form-check-input mt-0" type="checkbox" id="servicioSnack" name="servicios[]" value="snack">
                                                        <label class="form-check-label small fw-semibold cursor-pointer mb-0" for="servicioSnack">Snack</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check form-switch p-3 bg-light rounded-4 border-0 d-flex align-items-center gap-2">
                                                        <input class="form-check-input mt-0" type="checkbox" id="servicioBano" name="servicios[]" value="bano" checked>
                                                        <label class="form-check-label small fw-semibold cursor-pointer mb-0" for="servicioBano">Baño</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check form-switch p-3 bg-light rounded-4 border-0 d-flex align-items-center gap-2">
                                                        <input class="form-check-input mt-0" type="checkbox" id="servicioUSB" name="servicios[]" value="usb">
                                                        <label class="form-check-label small fw-semibold cursor-pointer mb-0" for="servicioUSB">USB</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check form-switch p-3 bg-light rounded-4 border-0 d-flex align-items-center gap-2">
                                                        <input class="form-check-input mt-0" type="checkbox" id="servicioManta" name="servicios[]" value="manta">
                                                        <label class="form-check-label small fw-semibold cursor-pointer mb-0" for="servicioManta">Manta</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-check form-switch p-3 bg-light rounded-4 border-0 d-flex align-items-center gap-2">
                                                        <input class="form-check-input mt-0" type="checkbox" id="servicioSeguro" name="servicios[]" value="seguro" checked>
                                                        <label class="form-check-label small fw-semibold cursor-pointer mb-0" for="servicioSeguro">Seguro</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Resumen de precio -->
                                        <div class="col-12 mt-4">
                                            <div class="card bg-dark text-white border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                                                <div class="position-absolute top-0 end-0 opacity-10 p-3">
                                                    <i class="bi bi-cash-stack" style="font-size: 5rem;"></i>
                                                </div>
                                                <div class="card-body p-4 position-relative z-1">
                                                    <h6 class="text-white-50 text-uppercase small fw-bold ls-1">Precio Final por Asiento</h6>
                                                    <div class="d-flex align-items-baseline gap-2">
                                                        <span class="fs-1 fw-bold" id="precioFinal">0.00</span>
                                                        <span class="fs-4 text-white-50">Bs.</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Notas adicionales -->
                                        <div class="col-12 mt-3">
                                            <div class="form-floating">
                                                <textarea class="form-control border shadow-sm" id="notasAdicionales" name="notas" placeholder="Info extra" style="height: 100px; border-radius: 12px; background-color: #f8f9fa;"></textarea>
                                                <label for="notasAdicionales" class="text-muted">Notas Adicionales</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alerta informativa -->
                    <div class="alert alert-primary border-0 shadow-sm mt-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-lightbulb-fill me-3" style="font-size: 2rem;"></i>
                            <div>
                                <h6 class="alert-heading mb-1">💡 Información Importante</h6>
                                <p class="mb-0 small">Una vez creada la ruta de viaje, podrá gestionar los asientos, realizar ventas y modificar la información desde el módulo de gestión de rutas.</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>



            <!-- Footer con botones mejorados -->
            <div class="modal-footer border-0 bg-white p-4 rounded-bottom-5">
                <!-- Botón Eliminar (solo visible en modo edición) -->
                <button type="button" class="btn btn-danger rounded-pill px-4 me-auto" id="btnEliminarRutaModal" onclick="eliminarRutaDesdeModal()" style="display: none;">
                    <i class="bi bi-trash me-2"></i><b>Eliminar Ruta</b>
                </button>

                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                    <b>Cancelar</b>
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnGuardarRuta" onclick="guardarRutaViaje()" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                    <b>Guardar Cambios</b> <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos Premium para Ventas */
    :root {
        --premium-backdrop: rgba(15, 23, 42, 0.6);
        --premium-blur: 12px;
    }

    .modal-backdrop.show {
        backdrop-filter: blur(var(--premium-blur));
        -webkit-backdrop-filter: blur(var(--premium-blur));
        background-color: var(--premium-backdrop);
    }

    .hover-bounce {
        transition: transform 0.2s;
    }

    .hover-bounce:hover {
        transform: translateY(-3px);
    }

    /* Glassmorphism subtle applied to cards mostly, but clean white for modal is better for forms */
    .modal-content {
        border-radius: 24px !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        overflow: hidden;
    }

    .form-floating>.form-control,
    .form-floating>.form-select {
        border: 1px solid transparent;
        transition: all 0.3s ease;
    }

    .form-floating>.form-control:focus,
    .form-floating>.form-select:focus {
        background-color: #fff !important;
        border-color: #6366f1;
        /* Indigo-500 */
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .nav-pills .nav-link {
        color: #64748b;
        font-weight: 600;
        transition: all 0.2s;
    }

    .nav-pills .nav-link.active {
        background-color: #eff6ff;
        color: #4f46e5;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .ls-1 {
        letter-spacing: 1px;
    }

    /* Custom Animation for Modal */
    .modal.fade .modal-dialog {
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        transform: scale(0.95) translateY(20px);
    }

    .modal.show .modal-dialog {
        transform: scale(1) translateY(0);
    }
</style>

<script>
    // Token CSRF Global
    const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';

    /**
     * Abrir modal para nueva ruta
     */
    function abrirModalNuevaRuta() {
        document.getElementById('formNuevaRuta').reset();
        document.getElementById('rutaViajeId').value = '';

        // Establecer fecha mínima como hoy
        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('fechaSalida').min = hoy;
        document.getElementById('fechaSalida').value = hoy;

        // Resetear vista previa
        document.getElementById('rutaPreview').style.display = 'none';
        document.getElementById('precioFinal').textContent = '0.00';
        document.getElementById('duracionViaje').value = '';

        // Volver al primer tab
        const firstTab = new bootstrap.Tab(document.getElementById('info-basica-tab'));
        firstTab.show();

        const modal = new bootstrap.Modal(document.getElementById('modalNuevaRuta'));
        modal.show();
    }

    /**
     * Guardar ruta de viaje con validación mejorada
     */
    /**
     * Guardar ruta de viaje con validación mejorada y navegación automática
     */
    function guardarRutaViaje() {
        const form = document.getElementById('formNuevaRuta');

        // Validar formulario manual para detectar campos ocultos en otros tabs
        if (!form.checkValidity()) {
            // Buscar el primer campo inválido
            const invalidField = form.querySelector(':invalid');
            if (invalidField) {
                // Encontrar el tab que contiene el campo inválido
                const tabPane = invalidField.closest('.tab-pane');
                if (tabPane) {
                    const tabId = tabPane.id;
                    // Encontrar el botón que activa ese tab
                    const tabButton = document.querySelector(`button[data-bs-target="#${tabId}"]`);
                    if (tabButton) {
                        // Activar el tab
                        const tab = new bootstrap.Tab(tabButton);
                        tab.show();
                    }
                }

                // Esperar un momento para que se muestre el tab y luego reportar validez
                setTimeout(() => {
                    invalidField.focus();
                    // Mostrar alerta específica
                    Swal.fire({
                        icon: 'warning',
                        title: 'Faltan Datos',
                        text: 'Por favor complete el campo: ' + (invalidField.previousElementSibling ? invalidField.previousElementSibling.innerText : 'requerido'),
                        confirmButtonColor: '#667eea',
                        timer: 3000
                    });
                }, 100);
            }
            return;
        }

        // Validar que terminal origen y destino sean diferentes
        const terminalOrigen = document.getElementById('terminalOrigen').value;
        const terminalDestino = document.getElementById('terminalDestino').value;

        if (terminalOrigen && terminalDestino && terminalOrigen === terminalDestino) {
            // Ir al tab de horarios si no estamos ahí
            const tabButton = document.querySelector('button[data-bs-target="#horarios"]');
            if (tabButton) {
                const tab = new bootstrap.Tab(tabButton);
                tab.show();
            }

            Swal.fire({
                icon: 'error',
                title: 'Error de Ruta',
                text: 'La terminal de origen y la de destino no pueden ser la misma.',
                confirmButtonColor: '#667eea'
            });
            return;
        }

        // Mostrar confirmación
        Swal.fire({
            title: '¿Confirmar Creación?',
            text: 'Se creará una nueva ruta de viaje con la información proporcionada',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, crear ruta',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar formulario
                form.submit();
            }
        });
    }

    /**
     * Calcular duración del viaje
     */
    function calcularDuracion() {
        const horaSalida = document.getElementById('horaSalida').value;
        const horaLlegada = document.getElementById('horaLlegada').value;

        if (horaSalida && horaLlegada) {
            const [horaSal, minSal] = horaSalida.split(':').map(Number);
            const [horaLleg, minLleg] = horaLlegada.split(':').map(Number);

            let totalMinutos = (horaLleg * 60 + minLleg) - (horaSal * 60 + minSal);

            // Si la hora de llegada es menor, asumimos que es al día siguiente
            if (totalMinutos < 0) {
                totalMinutos += 24 * 60;
            }

            const horas = Math.floor(totalMinutos / 60);
            const minutos = totalMinutos % 60;

            const duracionTexto = `${horas}h ${minutos}m`;
            document.getElementById('duracionViaje').value = duracionTexto;

            // Actualizar timeline
            document.getElementById('salidaInfo').textContent = horaSalida;
            document.getElementById('llegadaInfo').textContent = horaLlegada;
            document.getElementById('duracionInfo').textContent = `Duración: ${duracionTexto}`;
        }
    }

    /**
     * Actualizar precio final
     */
    function actualizarPrecio() {
        const precioBase = parseFloat(document.getElementById('precioBase').value) || 0;
        document.getElementById('precioFinal').textContent = precioBase.toFixed(2);
    }

    /**
     * Mostrar vista previa de ruta
     */
    function mostrarVistaPrevia() {
        const rutaSelect = document.getElementById('rutaSelect');
        const selectedOption = rutaSelect.options[rutaSelect.selectedIndex];

        if (selectedOption.value) {
            const origen = selectedOption.getAttribute('data-origen');
            const destino = selectedOption.getAttribute('data-destino');

            document.getElementById('origenText').textContent = origen;
            document.getElementById('destinoText').textContent = destino;
            document.getElementById('rutaPreview').style.display = 'block';
        } else {
            document.getElementById('rutaPreview').style.display = 'none';
        }
    }

    /**
     * ✅ LOGICA DE ASIGNACIÓN: Cargar buses disponibles por tipo
     * VERSIÓN MEJORADA con debugging
     */
    document.getElementById('tipoBusSelect').addEventListener('change', function() {
        const tipoId = this.value;
        const tipoTexto = this.options[this.selectedIndex].text;
        const selectBus = document.getElementById('selectBus');
        const selectChofer = document.getElementById('selectChofer');

        console.log('🔍 Tipo de bus seleccionado:', {
            id: tipoId,
            nombre: tipoTexto
        });

        // Resetear selects
        selectBus.innerHTML = '<option value="">Cargando buses...</option>';
        selectBus.disabled = true;

        selectChofer.innerHTML = '<option value="">Esperando selección de bus...</option>';
        selectChofer.value = '';

        if (!tipoId) {
            selectBus.innerHTML = '<option value="">Seleccione primero el Tipo de Bus...</option>';
            return;
        }

        // Llamada AJAX para obtener buses
        fetch('<?php echo URLROOT; ?>/ventas/obtener_buses_tipo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'tipo_id=' + tipoId
            })
            .then(response => response.json())
            .then(data => {
                console.log('📊 Respuesta del servidor:', data);

                selectBus.disabled = false;
                if (data.success && data.buses && data.buses.length > 0) {
                    let options = '<option value="">Seleccione Bus...</option>';
                    data.buses.forEach(bus => {
                        const detalle = [bus.marca, bus.modelo].filter(Boolean).join(' ');
                        const chofer = bus.chofer_asignado ? ` · ${bus.chofer_asignado}` : ' · sin chofer asignado';
                        options += `<option value="${bus.id}">${bus.placa} · ${detalle} · ${bus.asientos} asientos${chofer}</option>`;
                        console.log('✅ Bus disponible:', bus);
                    });
                    selectBus.innerHTML = options;

                    // Mensaje de éxito
                    console.log(`✅ Se encontraron ${data.buses.length} bus(es) del tipo: ${tipoTexto}`);
                } else {
                    // ⚠️ NO HAY BUSES DISPONIBLES
                    selectBus.innerHTML = '<option value="">No hay buses disponibles de este tipo</option>';

                    // Logging detallado del problema
                    console.warn('⚠️ NO SE ENCONTRARON BUSES DISPONIBLES');
                    console.warn('Tipo de bus seleccionado:', tipoTexto);
                    console.warn('ID del tipo de bus:', tipoId);
                    console.warn('Respuesta del servidor:', data);

                    // Mostrar alerta al usuario con información útil
                    Swal.fire({
                        icon: 'warning',
                        title: 'No hay buses disponibles',
                        html: `
                            <p>No se encontraron buses activos del tipo:</p>
                            <p class="fw-bold text-primary">${tipoTexto}</p>
                            <hr>
                            <p class="small text-muted">
                                <strong>Posibles causas:</strong><br>
                                • No hay buses registrados de este tipo<br>
                                • Los buses están inactivos (estado = 0)<br>
                                • El tipo de bus no está correctamente asignado
                            </p>
                            <p class="small text-info">
                                <i class="bi bi-info-circle me-1"></i>
                                Revisa la consola del navegador (F12) para más detalles
                            </p>
                        `,
                        confirmButtonColor: '#667eea',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                console.error('❌ ERROR en la petición AJAX:', error);
                selectBus.innerHTML = '<option value="">Error al cargar buses</option>';
                selectBus.disabled = false;

                // Alerta de error de red
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo conectar con el servidor. Verifica tu conexión a internet.',
                    confirmButtonColor: '#667eea'
                });
            });
    });

    /**
     * ✅ LOGICA DE ASIGNACIÓN: Cargar tripulación al seleccionar bus
     * VERSIÓN MEJORADA: Muestra información completa del bus
     */
    // Choferes activos del personal (perfil Chofer). El asignado al bus va marcado.
    const CHOFERES = <?php echo json_encode(array_map(fn($c) => ['id' => (int) $c->id, 'nombre' => trim($c->nombres . ' ' . $c->apellidos)], $data['choferes'] ?? []), JSON_UNESCAPED_UNICODE); ?>;

    function opcionesChoferes(asignadoId) {
        let html = asignadoId ? '' : '<option value="">Sin chofer asignado · seleccione uno</option>';
        CHOFERES.forEach(c => {
            const esAsignado = asignadoId && Number(asignadoId) === c.id;
            const nombre = c.nombre.replace(/[&<>"]/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch]));
            html += `<option value="${c.id}" ${esAsignado ? 'selected' : ''}>${nombre}${esAsignado ? ' (asignado al bus)' : ''}</option>`;
        });
        return html;
    }

    function cargarTripulacion(busId) {
        const selectChofer = document.getElementById('selectChofer');

        if (!busId) {
            selectChofer.value = '';
            selectChofer.innerHTML = '<option value="">Esperando selección de bus...</option>';
            // Limpiar info del bus si existe
            const infoBusDiv = document.getElementById('infoBusAsignado');
            if (infoBusDiv) infoBusDiv.remove();
            return;
        }

        selectChofer.innerHTML = '<option value="">Buscando conductor asignado...</option>';

        // Llamada AJAX para obtener tripulación
        fetch('<?php echo URLROOT; ?>/ventas/obtener_tripulacion_bus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'bus_id=' + busId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const trip = data.data;

                    // ✅ Verificar si tiene chofer asignado
                    if (trip.chofer_id && trip.nombre_chofer) {
                        // Tiene chofer asignado: se preselecciona, pero se puede elegir otro solo para este viaje
                        selectChofer.innerHTML = opcionesChoferes(trip.chofer_id);

                        // Feedback visual de éxito
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: `Bus ${trip.bus_placa} - ${trip.nombre_chofer}`
                        });
                    } else {
                        // ✅ Bus sin chofer asignado (pero existe en la BD)
                        selectChofer.innerHTML = opcionesChoferes(null);

                        // Advertencia visual
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 4000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'info',
                            title: `Bus ${trip.bus_placa || 'seleccionado'}`,
                            text: 'Sin conductor asignado. Por favor seleccione uno.'
                        });
                    }

                    // ✅ Mostrar información del bus (siempre)
                    mostrarInfoBusAsignado(trip);

                } else {
                    selectChofer.innerHTML = '<option value="">Sin conductor fijo asignado</option>';

                    // Advertencia si no hay datos
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    Toast.fire({
                        icon: 'warning',
                        title: 'No se pudo cargar información del bus'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                selectChofer.innerHTML = '<option value="">Error al buscar conductor</option>';
            });
    }

    /**
     * ✅ NUEVA FUNCIÓN: Mostrar información completa del bus
     */
    function mostrarInfoBusAsignado(tripulacion) {
        let infoBusDiv = document.getElementById('infoBusAsignado');

        if (!infoBusDiv) {
            infoBusDiv = document.createElement('div');
            infoBusDiv.id = 'infoBusAsignado';
            infoBusDiv.className = 'alert alert-info mt-3 mb-0';

            // Insertar después del campo de chofer
            const selectChoferContainer = document.getElementById('selectChofer').closest('.col-md-6');
            if (selectChoferContainer && selectChoferContainer.parentElement) {
                // Crear una nueva fila para la info del bus
                const newRow = document.createElement('div');
                newRow.className = 'col-12';
                newRow.appendChild(infoBusDiv);
                selectChoferContainer.parentElement.appendChild(newRow);
            }
        }

        // Animación de entrada
        infoBusDiv.style.animation = 'fadeIn 0.3s ease-in';

        // Determinar icono según pisos
        const iconoBus = tripulacion.bus_pisos > 1 ? '🚌🚌' : '🚌';
        const colorChofer = tripulacion.nombre_chofer ? 'text-success' : 'text-muted';
        const colorCopiloto = tripulacion.nombre_copiloto ? 'text-success' : 'text-muted';

        infoBusDiv.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-bus-front-fill text-primary me-3" style="font-size: 2rem;"></i>
                <div class="flex-grow-1">
                    <h6 class="mb-2 fw-bold">
                        <i class="bi bi-info-circle-fill text-info me-1"></i>
                        Información del Bus Seleccionado ${iconoBus}
                    </h6>
                    <div class="row small">
                        <div class="col-md-3">
                            <strong>Placa:</strong> <span class="badge bg-warning text-dark">${tripulacion.bus_placa || 'N/A'}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Unidad:</strong> #${tripulacion.bus_numero || 'N/A'}
                        </div>
                        <div class="col-md-3">
                            <strong>Tipo:</strong> ${tripulacion.tipo_bus || 'N/A'}
                        </div>
                        <div class="col-md-3">
                            <strong>Pisos:</strong> ${tripulacion.bus_pisos || 1} ${tripulacion.bus_pisos > 1 ? '(Doble Piso)' : '(Un Piso)'}
                        </div>
                    </div>
                    <div class="row small mt-2">
                        <div class="col-md-6">
                            <i class="bi bi-person-fill ${colorChofer} me-1"></i>
                            <strong>Conductor:</strong> ${tripulacion.nombre_chofer || '⚠️ Sin asignar'}
                        </div>
                        <div class="col-md-6">
                            <i class="bi bi-person-fill ${colorCopiloto} me-1"></i>
                            <strong>Copiloto:</strong> ${tripulacion.nombre_copiloto || 'Sin asignar'}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Listeners
    document.getElementById('horaSalida').addEventListener('change', calcularDuracion);
    document.getElementById('horaLlegada').addEventListener('change', calcularDuracion);
    document.getElementById('precioBase').addEventListener('input', actualizarPrecio);
    document.getElementById('rutaSelect').addEventListener('change', mostrarVistaPrevia);
    document.getElementById('selectBus').addEventListener('change', function() {
        cargarTripulacion(this.value);
    });
    // Initialize
    mostrarVistaPrevia();

    /**
     * Inicialización al cargar la página
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Establecer fecha mínima
        const hoy = new Date().toISOString().split('T')[0];
        const fechaSalida = document.getElementById('fechaSalida');
        if (fechaSalida) {
            fechaSalida.min = hoy;
        }

        document.getElementById('rutaSelect').addEventListener('change', mostrarVistaPrevia);
        document.getElementById('horaSalida').addEventListener('change', calcularDuracion);
        document.getElementById('horaLlegada').addEventListener('change', calcularDuracion);
        document.getElementById('precioBase').addEventListener('input', actualizarPrecio);

        // Animación de tabs
        const tabButtons = document.querySelectorAll('#rutaTabs button');
        tabButtons.forEach(button => {
            button.addEventListener('shown.bs.tab', function(e) {
                // Añadir animación suave al cambiar de tab
                const targetPane = document.querySelector(e.target.getAttribute('data-bs-target'));
                targetPane.style.animation = 'fadeIn 0.3s ease-in';
            });
        });

        // Validación en tiempo real de terminales
        const terminalOrigen = document.getElementById('terminalOrigen');
        const terminalDestino = document.getElementById('terminalDestino');

        function validarTerminales() {
            if (terminalOrigen.value && terminalDestino.value && terminalOrigen.value === terminalDestino.value) {
                terminalDestino.classList.add('is-invalid');
                if (!document.getElementById('terminalError')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.id = 'terminalError';
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = 'La terminal de destino debe ser diferente a la de origen';
                    terminalDestino.parentNode.appendChild(errorDiv);
                }
            } else {
                terminalDestino.classList.remove('is-invalid');
                const errorDiv = document.getElementById('terminalError');
                if (errorDiv) errorDiv.remove();
            }
        }

        terminalOrigen.addEventListener('change', validarTerminales);
        terminalDestino.addEventListener('change', validarTerminales);
    });
</script>

<style>
    /* Animaciones */
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

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Cards con efectos */
    .card {
        transition: all 0.3s ease;
        border-radius: 12px;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
    }

    /* Tabla mejorada */
    .table-hover tbody tr {
        transition: all 0.2s ease;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.08);
        transform: scale(1.01);
    }

    /* Modal personalizado */
    .modal-content {
        border-radius: 20px;
        overflow: hidden;
    }

    .modal-header {
        border-bottom: none;
    }

    .modal-footer {
        border-top: none;
    }

    /* Tabs personalizados */
    .nav-pills .nav-link {
        transition: all 0.3s ease;
        font-weight: 500;
        border: 2px solid transparent;
    }

    .nav-pills .nav-link:hover {
        background-color: rgba(102, 126, 234, 0.1);
        transform: translateY(-2px);
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    /* Form controls mejorados */
    .form-control,
    .form-select {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        transition: all 0.3s ease;
        padding: 0.75rem 1rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        transform: translateY(-2px);
    }

    .form-control-lg,
    .form-select-lg {
        padding: 1rem 1.25rem;
        font-size: 1.1rem;
    }

    /* Labels mejorados */
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    /* Switches personalizados */
    .form-check-input {
        width: 3rem;
        height: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
        box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
    }

    .form-check-label {
        cursor: pointer;
        user-select: none;
    }

    /* Botones mejorados */
    .btn {
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .btn:active {
        transform: translateY(0);
    }

    .btn-lg {
        padding: 0.75rem 2rem;
        font-size: 1rem;
    }

    /* Badges personalizados */
    .badge {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* Alertas mejoradas */
    .alert {
        border-radius: 12px;
        border: none;
        animation: slideIn 0.5s ease;
    }

    .alert-primary {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        color: #667eea;
    }

    /* Input groups */
    .input-group-text {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-weight: 600;
    }

    .input-group .form-control:not(:last-child),
    .input-group .input-group-text:not(:last-child) {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .input-group .form-control:not(:first-child),
    .input-group .input-group-text:not(:first-child) {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    /* Timeline visual */
    .bg-gradient {
        position: relative;
        overflow: hidden;
    }

    .bg-gradient::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
        pointer-events: none;
    }

    /* Scrollbar personalizado */
    .modal-body::-webkit-scrollbar {
        width: 8px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }

    /* Servicios grid */
    .form-check.form-switch {
        transition: all 0.3s ease;
    }

    .form-check.form-switch:hover {
        background-color: rgba(102, 126, 234, 0.05) !important;
        transform: translateY(-2px);
    }

    /* Precio card */
    .card.bg-gradient {
        position: relative;
        overflow: hidden;
    }

    .card.bg-gradient::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: pulse 3s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 0.5;
        }

        50% {
            opacity: 1;
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .modal-dialog {
            margin: 0.5rem;
        }

        .modal-header {
            padding: 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .nav-pills .nav-link {
            font-size: 0.85rem;
            padding: 0.5rem;
        }

        .btn-lg {
            padding: 0.6rem 1.5rem;
            font-size: 0.9rem;
        }
    }

    /* Validación visual */
    .is-invalid {
        border-color: #dc3545 !important;
        animation: shake 0.5s;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
    }

    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        animation: fadeIn 0.3s ease;
    }

    /* Loading state */
    .btn.loading {
        position: relative;
        pointer-events: none;
        opacity: 0.7;
    }

    .btn.loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Breadcrumb mejorado */
    .breadcrumb {
        background: transparent;
        padding: 0;
        margin: 0;
    }

    .breadcrumb-item+.breadcrumb-item::before {
        content: '›';
        font-size: 1.2rem;
        color: #6c757d;
    }

    .breadcrumb-item a {
        color: #667eea;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .breadcrumb-item a:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    /* Form text helpers */
    .form-text {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }

    /* Z-index fix */
    .position-relative.z-1 {
        z-index: 1;
    }

    /* Opacity utilities */
    .opacity-10 {
        opacity: 0.1;
    }

    .opacity-75 {
        opacity: 0.75;
    }
</style>


<!-- MODAL PUNTO DE VENTA (POS) -->
<!-- MODAL PUNTO DE VENTA (POS) -->
<!-- MODAL ELIMINADO: La venta ahora se realiza en pantalla completa (/ventas/venta_pasajes) -->


<script>
    const URLROOT = '<?php echo URLROOT; ?>';
    let asientoSeleccionado = null;

    function abrirVenta(idRuta) {
        window.location.href = `${URLROOT}/ventas/venta_pasajes?viaje_id=${idRuta}`;
    }

    // Las funciones de renderizado de bus antiguas han sido removidas.
    // La funcionalidad ahora reside en Ventas/venta_pasajes.

    function generarAsientoHTML(numero, esPremium, ocupados) {
        const isSold = ocupados.some(o => o == numero); // loose check
        let classes = 'seat';
        if (isSold) classes += ' seat-sold';
        else classes += ' seat-free';

        if (esPremium) classes += ' seat-premium border-warning';

        return `<div class="${classes}" data-numero="${numero}" title="Asiento ${numero}">
                    ${numero}
                </div>`;
    }

    function agregarEventosAsientos(container) {
        container.querySelectorAll('.seat-free').forEach(seat => {
            seat.onclick = () => seleccionarAsiento(seat);
        });
    }

    function generarAsientosSimple(start, end, container, ocupados, distribucion) {
        let seatsOnLeft = 2; // Default 2-2
        let seatsOnRight = 2;

        if (distribucion === '2-1') {
            seatsOnRight = 1;
        }

        const seatsPerRow = seatsOnLeft + seatsOnRight;
        let currentSeatInRow = 0;

        for (let i = start; i <= end; i++) {
            // Generar Asiento
            const seat = document.createElement('div');
            let statusClass = 'seat-free';
            let title = `Asiento ${i}`;

            if (ocupados.some(o => o == i)) {
                statusClass = 'seat-sold';
                title += ' (Vendido)';
            }

            seat.className = `seat ${statusClass}`;
            seat.innerText = i;
            seat.dataset.numero = i;
            seat.title = title;

            if (statusClass === 'seat-free') {
                seat.onclick = () => seleccionarAsiento(seat);
            }
            container.appendChild(seat);
            currentSeatInRow++;

            if (currentSeatInRow === seatsOnLeft) {
                const spacer = document.createElement('div');
                spacer.className = 'aisle-spacer';
                container.appendChild(spacer);
            }

            if (currentSeatInRow >= seatsPerRow) {
                currentSeatInRow = 0;
            }
        }
    }

    function seleccionarAsiento(element) {
        if (element.classList.contains('seat-sold')) return;
        const previo = document.querySelector('.seat-selected');
        if (previo) {
            previo.classList.remove('seat-selected');
            previo.classList.add('seat-free');
        }
        if (previo === element) {
            document.getElementById('inputAsiento').value = '';
            asientoSeleccionado = null;
            return;
        }
        element.classList.remove('seat-free');
        element.classList.add('seat-selected');
        asientoSeleccionado = element.dataset.numero;
        document.getElementById('inputAsiento').value = `Asiento #${asientoSeleccionado}`;
    }

    function limpiarFormularioVenta() {
        asientoSeleccionado = null;
        document.getElementById('inputAsiento').value = '';
        document.getElementById('inputDNI').value = '';
        document.getElementById('inputNombres').value = '';
        document.getElementById('inputApellidos').value = '';
    }

    // Variable global para almacenar datos del último boleto emitido
    let ultimoBoletoEmitido = null;

    function procesarVenta() {
        if (!asientoSeleccionado) {
            Swal.fire({
                icon: 'warning',
                title: 'Seleccione un asiento',
                text: 'Debe elegir un asiento disponible.'
            });
            return;
        }

        const nombres = document.getElementById('inputNombres').value.trim();
        const apellidos = document.getElementById('inputApellidos').value.trim();
        const documento = document.getElementById('inputDNI').value.trim();

        if (!nombres || !apellidos) {
            Swal.fire({
                icon: 'warning',
                title: 'Faltan datos',
                text: 'Complete los datos del pasajero.'
            });
            return;
        }

        // Obtener el ID del viaje actual (debe estar almacenado cuando se abre el modal)
        const viajeId = document.getElementById('viajeIdActual')?.value;
        const precioBase = document.getElementById('precioBaseActual')?.value || '25.00';

        if (!viajeId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se ha identificado el viaje. Por favor, cierre y vuelva a abrir el modal de venta.'
            });
            return;
        }

        // Mostrar loading
        Swal.fire({
            title: 'Procesando venta...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Preparar datos para enviar al backend
        const datosVenta = {
            viaje_id: viajeId,
            numero_asiento: asientoSeleccionado,
            nombre_pasajero: `${nombres} ${apellidos}`.toUpperCase(),
            documento_pasajero: documento || 'S/N',
            precio: precioBase
        };

        // Llamar al backend para procesar la venta
        $.ajax({
            url: `${URLROOT}/ventas/procesar_venta`,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(datosVenta),
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta del servidor:', response);

                if (response.success && response.ticket) {
                    // Mapear datos del backend al formato esperado por imprimirTicket()
                    ultimoBoletoEmitido = {
                        // Datos de la empresa
                        empresaDireccion: 'Av. El Alto N° 777',
                        empresaTelefono: '967885780',
                        empresaEmail: 'atencioncliente@busdriver.com',
                        empresaRuc: '20201563254',

                        // Número de boleto (del backend)
                        numeroBoleto: response.ticket.id_boleto || `${response.ticket.id_boleto}`,

                        // Datos del viaje (del backend)
                        fechaViaje: response.ticket.fecha_salida,
                        horaSalida: response.ticket.hora_salida,
                        placaBus: response.ticket.bus_placa || 'N/A',
                        origen: response.ticket.ciudad_origen,
                        destino: response.ticket.ciudad_destino,
                        asiento: response.ticket.numero_asiento,

                        // Datos del pasajero
                        nombrePasajero: response.ticket.nombre_pasajero,
                        documentoPasajero: response.ticket.numero_documento,

                        // Datos de venta
                        fechaExpedicion: response.ticket.fecha_venta,
                        importe: parseFloat(response.ticket.precio)
                    };

                    // Mostrar modal de éxito con botón de impresión
                    Swal.fire({
                        icon: 'success',
                        title: '¡Boleto Emitido!',
                        html: `
                            <p class="mb-3">Venta realizada exitosamente.</p>
                            <div class="alert alert-info">
                                <strong>Boleto:</strong> ${ultimoBoletoEmitido.numeroBoleto}<br>
                                <strong>Pasajero:</strong> ${ultimoBoletoEmitido.nombrePasajero}<br>
                                <strong>Asiento:</strong> #${ultimoBoletoEmitido.asiento}<br>
                                <strong>Ruta:</strong> ${ultimoBoletoEmitido.origen} → ${ultimoBoletoEmitido.destino}
                            </div>
                        `,
                        confirmButtonText: '🖨️ Imprimir Ticket',
                        showCancelButton: true,
                        cancelButtonText: 'Cerrar',
                        confirmButtonColor: '#6366f1',
                        cancelButtonColor: '#94a3b8'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Llamar a la función de impresión
                            imprimirTicketVenta();
                        }

                        // Cerrar el modal de venta
                        const modalEl = document.getElementById('modalVentaBoletos');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) {
                                modal.hide();
                            }
                        }

                        // Limpiar formulario
                        limpiarFormularioVenta();

                        // Recargar la página para actualizar asientos ocupados
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en la venta',
                        text: response.message || 'No se pudo completar la venta. Intente nuevamente.'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en AJAX:', error);
                console.error('Respuesta:', xhr.responseText);

                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo procesar la venta. Verifique su conexión e intente nuevamente.'
                });
            }
        });
    }

    // Función para imprimir el ticket usando el sistema de impresión térmica
    function imprimirTicketVenta() {
        if (!ultimoBoletoEmitido) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No hay datos de boleto disponibles para imprimir.'
            });
            return;
        }

        // Verificar que la función de impresión esté disponible
        if (typeof imprimirTicket !== 'function') {
            Swal.fire({
                icon: 'error',
                title: 'Error del Sistema',
                text: 'El sistema de impresión no está disponible. Verifique que el archivo impresion-ticket.js esté cargado.'
            });
            console.error('La función imprimirTicket() no está definida');
            return;
        }

        // Llamar a la función de impresión
        try {
            imprimirTicket(ultimoBoletoEmitido);
        } catch (error) {
            console.error('Error al imprimir ticket:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de Impresión',
                text: 'Ocurrió un error al generar el ticket. Revise la consola para más detalles.'
            });
        }
    }

    // ========================================
    // EVENTO CRÍTICO: Cargar datos cuando el modal se muestra
    // ========================================
    $('#modalVentaBoletos').on('shown.bs.modal', function() {
        console.log('✅ Modal completamente visible. Iniciando carga de datos...');

        const viajeId = document.getElementById('viajeIdActual').value;

        if (!viajeId) {
            console.error('❌ No hay ID de viaje');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo identificar el viaje. Por favor, cierre y vuelva a abrir.'
            });
            return;
        }

        // Mostrar loading en el contenedor del bus
        const wrapper = document.querySelector('.bus-body');
        if (wrapper) {
            wrapper.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted">Cargando diagrama del bus...</p>
                </div>
            `;
        }

        // Cargar datos del viaje vía AJAX
        $.ajax({
            url: `${URLROOT}/ventas/obtener_ruta_viaje/${viajeId}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('✅ Datos recibidos del servidor:', response);

                if (response.success && response.data) {
                    const data = response.data;

                    // Actualizar info del header del modal
                    const headerInfo = document.getElementById('posRutaInfo');
                    if (headerInfo) {
                        headerInfo.textContent = `${data.origen || 'N/A'} → ${data.destino || 'N/A'} | ${data.fecha_salida || ''} ${data.hora_salida || ''}`;
                    }

                    // Guardar precio base
                    const precioBase = parseFloat(data.precio_base) || 0;
                    document.getElementById('precioBaseActual').value = precioBase.toFixed(2);
                    document.getElementById('inputPrecio').value = precioBase.toFixed(2);

                    console.log('🎨 Llamando a renderizarBus() con datos:', data);

                    // Renderizar el diagrama del bus
                    renderizarBus(data);

                    console.log('✅ Renderizado completado');
                } else {
                    console.warn('⚠️ Respuesta sin datos válidos:', response);
                    if (wrapper) {
                        wrapper.innerHTML = `
                            <div class="alert alert-warning text-center">
                                <i class="bi bi-exclamation-triangle fs-1"></i>
                                <p class="mt-2">No se encontraron datos del viaje</p>
                            </div>
                        `;
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error AJAX:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });

                if (wrapper) {
                    wrapper.innerHTML = `
                        <div class="alert alert-danger text-center">
                            <i class="bi bi-x-circle fs-1"></i>
                            <p class="mt-2"><strong>Error de conexión</strong></p>
                            <small>${error || 'No se pudo cargar la información del viaje'}</small>
                        </div>
                    `;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo cargar la información del viaje. Verifique su conexión.'
                });
            }
        });
    });

    // Limpiar al cerrar el modal
    $('#modalVentaBoletos').on('hidden.bs.modal', function() {
        console.log('🔄 Modal cerrado, limpiando recursos...');
        limpiarFormularioVenta();

        const wrapper = document.querySelector('.bus-body');
        if (wrapper) {
            wrapper.innerHTML = '';
        }
    });

    // ========================================
    // FUNCIONES DE RESERVA VS. VENTA
    // ========================================

    /**
     * Procesar RESERVA (Estado 2)
     * Valida solo Nombre y CI
     */
    function procesarReserva() {
        console.log('🟡 Procesando RESERVA...');

        const asiento = document.getElementById('inputAsiento').value;
        const nombres = document.getElementById('inputNombres').value.trim();
        const apellidos = document.getElementById('inputApellidos').value.trim();
        const dni = document.getElementById('inputDNI').value.trim();

        // Validación mínima para reserva
        if (!asiento || asiento === '--') {
            Swal.fire({
                icon: 'warning',
                title: 'Seleccione un asiento',
                text: 'Debe elegir un asiento disponible.'
            });
            return;
        }

        if (!nombres || !apellidos) {
            Swal.fire({
                icon: 'warning',
                title: 'Faltan datos',
                text: 'Complete al menos Nombres y Apellidos para la reserva.'
            });
            return;
        }

        // Preparar datos
        const viajeId = document.getElementById('viajeIdActual').value;
        const precio = document.getElementById('inputPrecio').value;

        const datosReserva = {
            viaje_id: viajeId,
            numero_asiento: asiento,
            nombre_pasajero: `${nombres} ${apellidos}`.toUpperCase(),
            documento_pasajero: dni || 'S/N',
            precio: precio,
            tipo_venta: 2 // 2 = Reserva
        };

        // Enviar al backend
        enviarVentaBackend(datosReserva, 'reserva');
    }

    /**
     * Procesar VENTA (Estado 1)
     * Valida todos los campos
     */
    function procesarVenta() {
        console.log('🟢 Procesando VENTA...');

        const asiento = document.getElementById('inputAsiento').value;
        const nombres = document.getElementById('inputNombres').value.trim();
        const apellidos = document.getElementById('inputApellidos').value.trim();
        const dni = document.getElementById('inputDNI').value.trim();
        const precio = parseFloat(document.getElementById('inputPrecio').value);

        // Validación completa para venta
        if (!asiento || asiento === '--') {
            Swal.fire({
                icon: 'warning',
                title: 'Seleccione un asiento',
                text: 'Debe elegir un asiento disponible.'
            });
            return;
        }

        if (!nombres || !apellidos) {
            Swal.fire({
                icon: 'warning',
                title: 'Faltan datos',
                text: 'Complete los datos del pasajero.'
            });
            return;
        }

        if (!precio || precio <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Precio inválido',
                text: 'El precio debe ser mayor a 0.'
            });
            return;
        }

        // Preparar datos
        const viajeId = document.getElementById('viajeIdActual').value;

        const datosVenta = {
            viaje_id: viajeId,
            numero_asiento: asiento,
            nombre_pasajero: `${nombres} ${apellidos}`.toUpperCase(),
            documento_pasajero: dni || 'S/N',
            precio: precio.toFixed(2),
            tipo_venta: 1 // 1 = Venta
        };

        // Enviar al backend
        enviarVentaBackend(datosVenta, 'venta');
    }

    /**
     * Confirmar RESERVA existente (Convertir a Venta)
     * Actualiza el registro existente
     */
    function confirmarReserva() {
        console.log('✅ Confirmando RESERVA existente...');

        const boletoId = document.getElementById('boletoIdExistente').value;
        const asiento = document.getElementById('inputAsiento').value;
        const nombres = document.getElementById('inputNombres').value.trim();
        const apellidos = document.getElementById('inputApellidos').value.trim();
        const dni = document.getElementById('inputDNI').value.trim();
        const precio = parseFloat(document.getElementById('inputPrecio').value);

        if (!boletoId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró el ID de la reserva.'
            });
            return;
        }

        // Validación completa
        if (!nombres || !apellidos || !precio || precio <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Faltan datos',
                text: 'Complete todos los datos para confirmar la venta.'
            });
            return;
        }

        // Preparar datos (incluye ID de boleto existente)
        const viajeId = document.getElementById('viajeIdActual').value;

        const datosConfirmacion = {
            id_boleto_existente: boletoId, // ⚠️ CRÍTICO: Indica UPDATE
            viaje_id: viajeId,
            numero_asiento: asiento,
            nombre_pasajero: `${nombres} ${apellidos}`.toUpperCase(),
            documento_pasajero: dni || 'S/N',
            precio: precio.toFixed(2),
            tipo_venta: 1 // 1 = Venta confirmada
        };

        // Enviar al backend
        enviarVentaBackend(datosConfirmacion, 'confirmacion');
    }

    /**
     * Función genérica para enviar datos al backend
     */
    function enviarVentaBackend(datos, tipo) {
        console.log(`📤 Enviando ${tipo} al backend:`, datos);

        // Mostrar loading
        Swal.fire({
            title: tipo === 'reserva' ? 'Guardando Reserva...' : 'Procesando Venta...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Llamar al backend
        $.ajax({
            url: `${URLROOT}/ventas/procesar_venta`,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(datos),
            dataType: 'json',
            success: function(response) {
                console.log('✅ Respuesta del servidor:', response);

                if (response.success || response.status === 'success') {
                    // Éxito
                    const mensaje = tipo === 'reserva' ?
                        'Reserva guardada correctamente' :
                        'Venta confirmada correctamente';

                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: mensaje,
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        // Si es venta o confirmación, abrir impresión
                        if (tipo === 'venta' || tipo === 'confirmacion') {
                            if (response.ticket && typeof imprimirTicket === 'function') {
                                imprimirTicket(response.ticket);
                            }
                        }

                        // Cerrar modal y recargar
                        const modalEl = document.getElementById('modalVentaBoletos');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }

                        // Recargar página para actualizar asientos
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || response.mensaje || 'No se pudo completar la operación'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error AJAX:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'No se pudo procesar la operación. Verifique su conexión.'
                });
            }
        });
    }

    /**
     * Cargar datos de una reserva existente (al hacer clic en asiento amarillo)
     */
    function cargarDatosReserva(boletoId, numeroAsiento) {
        console.log(`📥 Cargando datos de reserva ID: ${boletoId}`);

        Swal.showLoading();

        $.ajax({
            url: `${URLROOT}/ventas/obtener_datos_reserva/${boletoId}`,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                Swal.close();

                if (response.success && response.data) {
                    const d = response.data;

                    // Llenar formulario
                    document.getElementById('inputAsiento').value = numeroAsiento;
                    document.getElementById('inputNombres').value = d.nombres || '';
                    document.getElementById('inputApellidos').value = d.apellidos || '';
                    document.getElementById('inputDNI').value = d.numero_documento || '';
                    document.getElementById('boletoIdExistente').value = d.id;

                    // Cambiar modo de botones
                    document.getElementById('botonesNuevo').style.display = 'none';
                    document.getElementById('botonesConfirmarReserva').style.display = 'block';

                    console.log('✅ Datos de reserva cargados. Modo: CONFIRMAR RESERVA');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'No se pudieron cargar los datos de la reserva'
                    });
                }
            },
            error: function() {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo conectar con el servidor'
                });
            }
        });
    }

    /**
     * Cancelar reserva actual
     */
    function cancelarReservaActual() {
        const boletoId = document.getElementById('boletoIdExistente').value;

        if (!boletoId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró el ID de la reserva'
            });
            return;
        }

        Swal.fire({
            title: '¿Cancelar Reserva?',
            text: 'Esta acción liberará el asiento. ¿Está seguro?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${URLROOT}/ventas/cancelar_boleto/${boletoId}`,
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Reserva Cancelada',
                                text: 'El asiento ha sido liberado'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.mensaje || 'No se pudo cancelar la reserva'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo conectar con el servidor'
                        });
                    }
                });
            }
        });
    }

    /**
     * Limpiar formulario de venta
     */
    function limpiarFormularioVenta() {
        asientoSeleccionado = null;
        document.getElementById('inputAsiento').value = '';
        document.getElementById('inputDNI').value = '';
        document.getElementById('inputNombres').value = '';
        document.getElementById('inputApellidos').value = '';
        document.getElementById('boletoIdExistente').value = '';

        // Mostrar botones de nuevo (modo normal)
        document.getElementById('botonesNuevo').style.display = 'block';
        document.getElementById('botonesConfirmarReserva').style.display = 'none';
    }
</script>

<style>
    /* Estilos Bus Engine */
    /* Estilos Bus Engine RENOVADOS */
    .bus-body {
        width: auto;
        min-width: 300px;
        max-width: 450px;
        background: linear-gradient(180deg, #e8e8e8 0%, #f5f5f5 100%);
        border: 4px solid #5a5a5a;
        border-radius: 2rem;
        padding: 1.5rem;
        margin: 0 auto;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .seats-grid {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: center;
    }

    /* Ajuste para que los contenedores flexibles internos se vean bien */
    .bus-body .d-flex {
        justify-content: center;
    }

    .seat {
        width: 40px;
        height: 40px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        user-select: none;
        position: relative;
    }

    /* Eliminar el ::after antiguo */
    .seat::after {
        display: none;
    }

    .seat:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
        z-index: 5;
    }

    /* Asiento Libre (Azul - Estándar) */
    .seat-free {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: 2px solid #1d4ed8;
        color: white;
    }

    /* Asiento Premium (Naranja) */
    .seat-premium {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border: 2px solid #b45309;
        color: white;
    }

    /* Asiento Seleccionado (Verde vibrante) */
    .seat-selected {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: 2px solid #047857;
        color: white !important;
        /* Force white text */
        transform: scale(1.15);
        box-shadow: 0 0 15px rgba(16, 185, 129, 0.6);
        z-index: 10;
    }

    /* Asiento Vendido (Gris Oscuro) */
    .seat-sold {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        border: 2px solid #334155;
        color: #e2e8f0;
        cursor: not-allowed;
        opacity: 1;
    }

    .seat-sold:hover {
        transform: none;
        filter: none;
    }

    /* Leyenda */
    .seat-legend {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        display: inline-block;
        vertical-align: middle;
    }

    /* Estilos específicos para la cabina */
    .driver-cabin {
        background: linear-gradient(180deg, #2c2c2c 0%, #1a1a1a 100%);
        border-radius: 1.5rem 1.5rem 0.5rem 0.5rem;
        padding: 15px;
        margin-bottom: 20px;
        color: #888;
        border-bottom: none !important;
        /* Override bootstrap */
        width: 100%;
    }

    .seat-crew {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
        border: 2px solid #1f2937;
        color: #e5e7eb;
        font-size: 0.7rem;
        border-radius: 0.4rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>


<script>
    // URL Root para JS


    $(document).ready(function() {
        console.log("-----------------------------------------");
        console.log("Sistema de Rutas: Script de Ventas Iniciado");
        console.log("jQuery Version:", $.fn.jquery);
        console.log("-----------------------------------------");

        // Validar dependencias
        if (typeof bootstrap === 'undefined') {
            console.error("ERROR CRÍTICO: Bootstrap JS no está cargado.");
            Swal.fire('Error del Sistema', 'No se cargaron las librerías necesarias (Bootstrap).', 'error');
            return;
        }

        /**
         * Función genérica para cargar datos en el modal
         * @param {string} id - ID del viaje
         * @param {string} mode - 'edit' | 'view'
         * @param {jQuery} btn - El botón jQuery que disparó la acción
         */
        function cargarDatosEnModal(id, mode, btn) {
            console.log(`[CargarDatos] ID: ${id}, Mode: ${mode}`);

            if (!id) {
                Swal.fire('Error', 'ID de ruta no válido', 'error');
                return;
            }

            const originalContent = btn.html();
            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            btn.prop('disabled', true);

            const url = `${URLROOT}/ventas/obtener_ruta_viaje/${id}`;
            console.log(`[AJAX] Requesting: ${url}`);

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log("[AJAX] Success:", response);

                    if (response.error) {
                        Swal.fire('Error', response.error, 'error');
                        return;
                    }

                    // ✅ CORRECCIÓN: Desempaquetar 'data' si existe (el backend devuelve {success:true, data:{...}})
                    if (response.data) {
                        Object.assign(response, response.data);
                    }

                    const form = document.getElementById('formNuevaRuta');
                    if (form) form.reset();

                    const elId = document.getElementById('rutaViajeId');
                    if (elId) elId.value = response.id || id;

                    // Referencias DOM seguras
                    const modalTitle = document.querySelector('#modalNuevaRuta .modal-title');
                    const modalDesc = document.querySelector('#modalNuevaRuta .modal-header p');
                    const saveBtn = document.getElementById('btnGuardarRuta');
                    const cancelBtn = document.querySelector('#modalNuevaRuta .modal-footer button[data-bs-dismiss="modal"]');
                    const modalHeader = document.querySelector('#modalNuevaRuta .modal-header');

                    if (!modalTitle || !saveBtn) {
                        console.error("Error: Elementos del modal no encontrados en el DOM");
                        return;
                    }

                    // Resetear estado UI
                    $('#formNuevaRuta :input').prop('disabled', false);
                    $(saveBtn).show();
                    if (cancelBtn) cancelBtn.innerHTML = '<b>Cancelar</b>';

                    // Referencia al botón de eliminar
                    const btnEliminar = document.getElementById('btnEliminarRutaModal');

                    // Configurar visuales según modo
                    if (mode === 'edit') {
                        // Premium Gold Accent
                        modalTitle.innerHTML = '<span class="text-warning"><i class="bi bi-pencil-square me-2"></i>Editar Ruta</span>';
                        if (modalDesc) modalDesc.innerHTML = '<span class="badge bg-warning text-dark me-2">Modo Edición</span> Modifique los detalles del viaje seleccionado';
                        saveBtn.innerHTML = '<b><i class="bi bi-check2-circle me-2"></i>Actualizar Ruta</b>';

                        // Mostrar botón de eliminar en modo edición
                        if (btnEliminar) btnEliminar.style.display = 'inline-block';

                        // Borde dorado
                        if (modalHeader) modalHeader.classList.add('border-bottom', 'border-warning', 'border-3');
                    } else {
                        // Default / Restaurar (por si acaso se usa para View en futuro)
                        modalTitle.innerHTML = '<i class="bi bi-geo-alt-fill text-primary me-2"></i>Información';

                        // Ocultar botón de eliminar en modo creación
                        if (btnEliminar) btnEliminar.style.display = 'none';

                        if (modalHeader) modalHeader.classList.remove('border-warning', 'border-3');
                    }


                    // --- Poblar Campos (Igual para ambos) ---
                    try {


                        // Tab 1 (Poblado Dinámico del Select de Rutas)
                        const $rutaSelect = $('#rutaSelect');
                        $rutaSelect.empty();
                        $rutaSelect.append('<option value="">Seleccione ruta...</option>');

                        if (response.lista_rutas && Array.isArray(response.lista_rutas)) {
                            response.lista_rutas.forEach(ruta => {
                                // Crear opción
                                const texto = `${ruta.origen} → ${ruta.destino}`;
                                const option = new Option(texto, ruta.id);

                                // Añadir metadatos si son necesarios (como en el PHP original)
                                $(option).attr('data-origen', ruta.origen);
                                $(option).attr('data-destino', ruta.destino);

                                $rutaSelect.append(option);
                            });
                        }

                        // Seleccionar la ruta actual
                        $rutaSelect.val(response.ruta_id).trigger('change');
                        // LOGICA CORREGIDA: Carga en cascada (Tipo -> Bus -> Chofer)
                        const tipoBusId = response.tipo_bus_id;
                        const busIdGuardado = response.bus_id; // ID del bus guardado

                        if (tipoBusId) {
                            $('#tipoBusSelect').val(tipoBusId);

                            // Fetch manual para controlar la selección del bus guardado
                            const selectBus = document.getElementById('selectBus');
                            selectBus.innerHTML = '<option value="">Cargando bus asignado...</option>';
                            selectBus.disabled = false;

                            fetch(URLROOT + '/ventas/obtener_buses_tipo', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: 'tipo_id=' + tipoBusId
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success && data.buses.length > 0) {
                                        let options = '<option value="">Seleccione Bus...</option>';
                                        data.buses.forEach(bus => {
                                            const isSelected = (busIdGuardado && bus.id == busIdGuardado) ? 'selected' : '';
                                            options += `<option value="${bus.id}" ${isSelected}>${bus.placa} - Bus #${bus.numero_interno} (${bus.marca})</option>`;
                                        });
                                        selectBus.innerHTML = options;

                                        // Si hay bus, cargar la tripulación
                                        if (busIdGuardado) {
                                            cargarTripulacion(busIdGuardado);
                                        }
                                    } else {
                                        selectBus.innerHTML = '<option value="">No hay buses disponibles</option>';
                                    }
                                })
                                .catch(err => {
                                    console.error("Error loading buses:", err);
                                    selectBus.innerHTML = '<option value="">Error al cargar</option>';
                                });
                        }

                        setTimeout(() => {
                            if (response.fecha_salida_date) $('#fechaSalida').val(response.fecha_salida_date);
                        }, 50);

                        $('#estadoRuta').val(response.estado);
                        $('#terminalOrigen').val(response.terminal_origen_id);
                        $('#terminalDestino').val(response.terminal_destino_id);
                        $('#horaSalida').val(response.hora_salida);
                        $('#horaLlegada').val(response.hora_llegada);
                        $('#precioBase').val(response.precio_base);
                        $('#tipoServicio').val(response.tipo_servicio);
                        $('#notasAdicionales').val(response.notas);

                        $('input[name="servicios[]"]').prop('checked', false);
                        if (response.servicios_incluidos && Array.isArray(response.servicios_incluidos)) {
                            response.servicios_incluidos.forEach(servicio => {
                                $(`input[name="servicios[]"][value="${servicio}"]`).prop('checked', true);
                            });
                        }

                        if (typeof calcularDuracion === 'function') calcularDuracion();
                        if (typeof actualizarPrecio === 'function') actualizarPrecio();

                    } catch (err) {
                        console.error("Error al poblar formulario:", err);
                    }

                    // Mostrar Modal
                    const modalEl = document.getElementById('modalNuevaRuta');
                    if (modalEl) {
                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("[AJAX] Error:", status, error);
                    console.log("Response Text:", xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo cargar la información. Verifique su conexión o consola.'
                    });
                },
                complete: function() {
                    btn.html(originalContent);
                    btn.prop('disabled', false);
                    btn.removeClass('disabled');
                }
            });
        }

        // ============================
        // EVENT HANDLERS
        // ============================

        // ============================
        // EVENT HANDLERS
        // ============================

        console.log("Registrando event handlers para botones...");

        // Botón EDITAR
        $(document).on('click', '.btn-editar-ruta', function(e) {
            e.preventDefault();
            // Parche robusto: Encontrar el botón ya sea el target directo o un padre
            const btn = $(this).closest('.btn-editar-ruta');
            const id = btn.data('id');

            console.log("Click en Editar - ID detectado:", id);

            if (id) {
                cargarDatosEnModal(id, 'edit', btn);
            } else {
                console.error("No se pudo obtener el ID del botón editar");
            }
        });

        // Botón DESPACHAR (Finalizar y Crear Nuevo)
        $(document).on('click', '.btn-despachar-ruta', function(e) {
            e.preventDefault();
            const btn = $(this);
            const id = btn.data('id') || btn.closest('.btn-despachar-ruta').data('id');

            Swal.fire({
                title: '🚌 ¿Despachar Bus?',
                html: `
                    <div style="text-align: left; margin: 0 auto; max-width: 450px;">
                        <p><strong>Esta acción realizará lo siguiente:</strong></p>
                        <ul style="line-height: 1.8;">
                            <li>✅ <strong>Archivará</strong> el viaje actual (historial intacto)</li>
                            <li>✅ <strong>Creará</strong> un nuevo viaje para mañana</li>
                            <li>✅ El bus quedará <strong>listo para vender pasajes</strong></li>
                            <li>💰 <strong>NO se borrarán</strong> las ventas anteriores</li>
                        </ul>
                        <div class="alert alert-info mt-3" style="font-size: 0.9em;">
                            <i class="bi bi-info-circle"></i> Los boletos vendidos quedan registrados para reportes y auditoría.
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-check-circle me-2"></i>Sí, despachar bus',
                cancelButtonText: 'Cancelar',
                width: '600px'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Procesando...',
                        html: `
                            <div class="text-center">
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p>Finalizando viaje actual...</p>
                                <p>Creando nuevo viaje...</p>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false
                    });

                    $.post(`${URLROOT}/ventas/despachar_ruta/${id}`, {
                        csrf_token: CSRF_TOKEN
                    }, function(res) {
                        try {
                            const data = typeof res === 'string' ? JSON.parse(res) : res;

                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Bus Despachado Exitosamente!',
                                    html: `
                                        <div style="text-align: left; margin: 0 auto; max-width: 400px;">
                                            <p><strong>Resumen de la operación:</strong></p>
                                            <ul>
                                                <li>✅ Viaje anterior archivado</li>
                                                <li>✅ Nuevo viaje creado (ID: <strong>${data.nuevo_viaje_id}</strong>)</li>
                                                <li>✅ Bus listo para venta</li>
                                            </ul>
                                            <p class="mt-3 text-muted">Redirigiendo a la pantalla de venta de pasajes...</p>
                                        </div>
                                    `,
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = data.redirect;
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al Despachar',
                                    text: data.message || 'No se pudo completar la operación',
                                    confirmButtonColor: '#ef4444'
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Conexión',
                            text: 'No se pudo conectar con el servidor. Revise su conexión.',
                            confirmButtonColor: '#ef4444'
                        });
                    });
                }
            });
        });

        // Botón ELIMINAR
        $(document).on('click', '.btn-eliminar-ruta', function(e) {
            e.preventDefault();

            const btn = $(this);
            const id = btn.data('id') || btn.closest('.btn-eliminar-ruta').data('id');
            const row = btn.closest('tr');

            console.log("Click en Eliminar ID:", id);

            if (!id) {
                Swal.fire('Error', 'No se ha identificado el viaje a eliminar.', 'error');
                return;
            }

            Swal.fire({
                title: '¿Confirmar eliminación?',
                text: "Se cancelará el viaje y se liberará el bus. Esta acción es irreversible.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Sí, eliminar ruta',
                cancelButtonText: 'Cancelar',
                backdrop: `rgba(15, 23, 42, 0.6)`
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading
                    Swal.fire({
                        title: 'Eliminando...',
                        text: 'Por favor espere',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: `${URLROOT}/ventas/eliminar_ruta_viaje/${id}`,
                        type: 'POST',
                        data: {
                            csrf_token: CSRF_TOKEN
                        },
                        dataType: 'json',
                        success: function(response) {
                            console.log("Respuesta de eliminación:", response);

                            if (response.success) {
                                // Mostrar mensaje de éxito
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Eliminado!',
                                    text: 'La ruta ha sido eliminada correctamente',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Recargar la página para actualizar la lista
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'No se pudo eliminar la ruta'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Delete Error:", xhr);
                            console.error("Status:", status);
                            console.error("Error:", error);
                            console.error("Response Text:", xhr.responseText);

                            Swal.fire({
                                icon: 'error',
                                title: 'Error de Conexión',
                                text: 'No se pudo conectar con el servidor. Revise la consola para más detalles.'
                            });
                        }
                    });
                }
            });
        });

        // Evento: Al cerrar el modal, restaurar limpieza
        const modalEl = document.getElementById('modalNuevaRuta');
        if (modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function() {
                console.log("Modal cerrado: Restaurando estado");
                const form = document.getElementById('formNuevaRuta');
                if (form) form.reset();
                $('#formNuevaRuta :input').prop('disabled', false);
                document.getElementById('rutaViajeId').value = '';

                // Restaurar estilo header
                const modalTitle = document.querySelector('#modalNuevaRuta .modal-title');
                const modalDesc = document.querySelector('#modalNuevaRuta .modal-header p');
                const saveBtn = document.getElementById('btnGuardarRuta');
                const modalHeader = document.querySelector('#modalNuevaRuta .modal-header');
                const btnEliminar = document.getElementById('btnEliminarRutaModal');

                if (modalHeader) modalHeader.classList.remove('border-bottom', 'border-warning', 'border-3');

                if (modalTitle) modalTitle.innerHTML = '<i class="bi bi-geo-alt-fill text-primary me-2"></i>Nueva Ruta';
                if (modalDesc) modalDesc.textContent = 'Configure los detalles del viaje programado';

                if (saveBtn) {
                    $(saveBtn).show();
                    saveBtn.innerHTML = '<b>Guardar Cambios</b> <i class="bi bi-arrow-right ms-2"></i>';
                }

                // Ocultar botón de eliminar al cerrar
                if (btnEliminar) btnEliminar.style.display = 'none';

                // Volver al tab 1
                const firstTabEl = document.getElementById('info-basica-tab');
                if (firstTabEl) {
                    const firstTab = new bootstrap.Tab(firstTabEl);
                    firstTab.show();
                }
            });
        }
    }); // Cierre del $(document).ready() principal

    /**
     * ✅ FUNCIÓN GLOBAL: Eliminar ruta desde el modal de edición
     * Debe estar FUERA del scope de $(document).ready() para ser accesible desde onclick
     */
    function eliminarRutaDesdeModal() {
        const rutaId = document.getElementById('rutaViajeId').value;

        if (!rutaId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo identificar la ruta a eliminar'
            });
            return;
        }

        Swal.fire({
            title: '¿Eliminar esta ruta?',
            text: "Se cancelará el viaje y se liberará el bus. Esta acción es irreversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            backdrop: `rgba(15, 23, 42, 0.6)`
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Eliminando...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: `${URLROOT}/ventas/eliminar_ruta_viaje/${rutaId}`,
                    type: 'POST',
                    data: {
                        csrf_token: CSRF_TOKEN
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log("Respuesta de eliminación desde modal:", response);

                        if (response.success) {
                            // Cerrar el modal primero
                            const modalEl = document.getElementById('modalNuevaRuta');
                            if (modalEl) {
                                const modal = bootstrap.Modal.getInstance(modalEl);
                                if (modal) {
                                    modal.hide();
                                }
                            }

                            // Mostrar mensaje de éxito y recargar
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: 'La ruta ha sido eliminada correctamente',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'No se pudo eliminar la ruta'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Delete Error desde modal:", xhr);
                        console.error("Status:", status);
                        console.error("Error:", error);
                        console.error("Response Text:", xhr.responseText);

                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Conexión',
                            text: 'No se pudo conectar con el servidor. Revise la consola para más detalles.'
                        });
                    }
                });
            }
        });
    }
</script>