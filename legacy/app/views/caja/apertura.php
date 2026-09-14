<!-- Main Wrapper -->
<main class="app-main">
    <div class="app-content">
        <div class="container-fluid">
            <div class="row justify-content-center mt-5">
                <div class="col-md-5">
                    <div class="card shadow-lg border-0 rounded-3">
                        <div class="card-header bg-primary text-white text-center py-4">
                            <i class="bi bi-wallet2 display-4 mb-2"></i>
                            <h3 class="fw-bold mb-0">Apertura de Caja</h3>
                            <p class="mb-0 opacity-75">Inicie su turno de trabajo</p>
                        </div>

                        <div class="card-body p-4">
                            <?php if (isset($data['error'])) : ?>
                                <div class="alert alert-danger">
                                    <?php echo $data['error']; ?>
                                </div>
                            <?php endif; ?>

                            <form action="<?php echo URLROOT; ?>/caja/abrir" method="POST" id="formApertura">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                <!-- Fecha Actual -->
                                <div class="text-center mb-4">
                                    <small class="text-muted text-uppercase fw-bold">Fecha de Operación</small>
                                    <h4 class="text-dark fw-bold"><?php echo date('d/m/Y H:i:s'); ?></h4>
                                </div>

                                <!-- Sucursal de la caja -->
                                <div class="mb-4">
                                    <?php if (!empty($data['puede_elegir_sucursal'])): ?>
                                        <label for="sucursal_id" class="form-label text-muted fw-bold small">SUCURSAL</label>
                                        <select class="form-select form-select-lg" name="sucursal_id" id="sucursal_id" required>
                                            <option value="">Seleccione la sucursal…</option>
                                            <?php foreach ($data['sucursales'] as $s): ?>
                                                <option value="<?php echo (int) $s->id; ?>" <?php echo (!empty($data['sucursal_usuario']) && (int) $data['sucursal_usuario']->id === (int) $s->id) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($s->nombre_sede); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Todo lo que cobre en esta caja quedará registrado en esa sucursal.</div>
                                    <?php elseif (!empty($data['sucursal_usuario'])): ?>
                                        <div class="text-center">
                                            <small class="text-muted text-uppercase fw-bold">Sucursal</small>
                                            <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($data['sucursal_usuario']->nombre_sede); ?></h5>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning mb-0">
                                            Su usuario no tiene una sucursal asignada. Pida al administrador que se la asigne en <strong>Usuarios del sistema</strong> para poder abrir caja.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Input Monto -->
                                <div class="mb-4">
                                    <label for="monto_inicial" class="form-label text-muted fw-bold small">MONTO INICIAL (Base/Cambio)</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light border-end-0 fw-bold">Bs.</span>
                                        <input type="number"
                                            name="monto_inicial"
                                            id="monto_inicial"
                                            class="form-control border-start-0 fw-bold fs-3 text-center"
                                            placeholder="0.00"
                                            step="0.01"
                                            min="0"
                                            required
                                            autofocus>
                                    </div>
                                    <div class="form-text text-center">Ingrese con cuánto dinero (cambio) está iniciando en el cajón.</div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                        <i class="bi bi-check-circle-fill me-2"></i> ABRIR CAJA
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer bg-light text-center py-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i> No podrá registrar ventas hasta abrir caja.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Foco automático y validación simple
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('monto_inicial');
        input.focus();

        document.getElementById('formApertura').addEventListener('submit', function(e) {
            if (input.value === '') input.value = '0';

            Swal.fire({
                title: 'Abriendo Caja...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
    });
</script>