<?php require APPROOT . '/views/layouts/header.php'; ?>
<?php require APPROOT . '/views/layouts/sidebar.php'; ?>

<!--begin::App Main-->
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0 fw-bold"><i class="bi bi-gear-fill me-2"></i>Configuración del Sistema</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                        <li class="breadcrumb-item active">Configuración</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0">Personalización de Marca (White Label)</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="<?php echo URLROOT; ?>/configuracion/guardar" method="POST" enctype="multipart/form-data">

                                <!-- Sección Identidad -->
                                <h6 class="text-uppercase text-muted fw-bold mb-3 fs-7 ls-1">Identidad Visual</h6>

                                <div class="row mb-4">
                                    <div class="col-md-4 text-center">
                                        <label class="form-label d-block fw-bold">Logo Actual</label>
                                        <div class="p-3 border rounded-3 bg-light mb-2 d-flex align-items-center justify-content-center" style="height: 150px;">
                                            <?php if (!empty($data['config']['empresa_logo'])): ?>
                                                <img src="<?php echo URLROOT . '/' . $data['config']['empresa_logo']; ?>" alt="Logo Empresa" class="img-fluid" style="max-height: 100px;">
                                            <?php else: ?>
                                                <div class="text-muted">
                                                    <i class="bi bi-image fs-1"></i><br>
                                                    Sin Logo
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="empresa_nombre" class="form-label">Nombre de la Empresa</label>
                                            <input type="text" class="form-control form-control-lg" name="empresa_nombre"
                                                value="<?php echo $data['config']['empresa_nombre'] ?? ''; ?>" placeholder="Ej: Valhalla Transport">
                                        </div>
                                        <div class="mb-3">
                                            <label for="empresa_slogan" class="form-label">Slogan o Subtítulo</label>
                                            <input type="text" class="form-control" name="empresa_slogan"
                                                value="<?php echo $data['config']['empresa_slogan'] ?? ''; ?>" placeholder="Ej: Empresa de Transporte">
                                        </div>
                                        <div class="mb-3">
                                            <label for="empresa_nit" class="form-label">NIT de la empresa</label>
                                            <input type="text" class="form-control" id="empresa_nit" name="empresa_nit" maxlength="30"
                                                value="<?php echo htmlspecialchars($data['config']['empresa_nit'] ?? ''); ?>" placeholder="Se imprime en el ticket">
                                        </div>
                                        <div class="mb-3">
                                            <label for="empresa_logo" class="form-label">Subir Nuevo Logo</label>
                                            <input class="form-control" type="file" id="empresa_logo" name="empresa_logo" accept="image/*">
                                            <div class="form-text">Formatos permitidos: PNG, JPG, SVG. Fondo transparente recomendado.</div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary px-5 btn-lg rounded-pill fw-bold">
                                        <i class="bi bi-save me-2"></i>Guardar Cambios
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>

                    <?php
                    $cfg = $data['config'];
                    $esAdmin = ($_SESSION['rol'] ?? '') === 'Administrador';
                    $qrActivo = !empty($cfg['pago_qr_activo']) && !empty($cfg['pago_qr_imagen']);
                    ?>
                    <div class="card shadow-sm border-0 rounded-4 mt-4" id="cobro-qr">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title fw-bold mb-0"><i class="bi bi-qr-code me-2"></i>Cobro con QR</h5>
                            <div class="card-tools">
                                <span class="badge <?php echo $qrActivo ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $qrActivo ? 'Activo' : 'Inactivo'; ?></span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small mb-4">
                                Al vender, el vendedor puede elegir <strong>Cobrar con QR</strong>: el asiento se reserva unos minutos y se muestra este QR con el monto
                                (también en una segunda pantalla para el pasajero). Cuando el pago llega a la cuenta, el vendedor lo confirma y se imprime el boleto.
                                En el arqueo, los cobros QR se informan aparte del efectivo.
                            </p>

                            <?php if (!$esAdmin): ?>
                                <div class="alert alert-secondary mb-0">
                                    <i class="bi bi-lock-fill me-1"></i> Solo un usuario con rol <strong>Administrador</strong> puede cambiar el QR de cobro.
                                </div>
                            <?php else: ?>
                                <form action="<?php echo URLROOT; ?>/configuracion/guardar_qr" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                    <div class="row g-4">
                                        <div class="col-md-4 text-center">
                                            <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-center" style="min-height: 220px;">
                                                <?php if (!empty($cfg['pago_qr_imagen'])): ?>
                                                    <img src="<?php echo URLROOT . '/' . htmlspecialchars($cfg['pago_qr_imagen']); ?>" alt="QR de cobro actual" class="img-fluid" style="max-height: 200px;">
                                                <?php else: ?>
                                                    <div class="text-muted"><i class="bi bi-qr-code fs-1"></i><br>Sin QR cargado</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label class="form-label" for="pago_qr_imagen">Imagen del QR</label>
                                                <input class="form-control" type="file" id="pago_qr_imagen" name="pago_qr_imagen" accept="image/png,image/jpeg,image/webp">
                                                <div class="form-text">PNG, JPG o WEBP, máximo 2 MB. Use el QR descargado de la app del banco (sin recortar el código).</div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label" for="pago_qr_titular">Titular de la cuenta</label>
                                                    <input class="form-control" id="pago_qr_titular" name="pago_qr_titular" maxlength="120" value="<?php echo htmlspecialchars($cfg['pago_qr_titular'] ?? ''); ?>" placeholder="Nombre del dueño">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label" for="pago_qr_entidad">Banco / billetera</label>
                                                    <input class="form-control" id="pago_qr_entidad" name="pago_qr_entidad" maxlength="120" value="<?php echo htmlspecialchars($cfg['pago_qr_entidad'] ?? ''); ?>" placeholder="Ej. Banco Unión, Tigo Money">
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label" for="pago_qr_instrucciones">Instrucciones para el pasajero</label>
                                                    <input class="form-control" id="pago_qr_instrucciones" name="pago_qr_instrucciones" maxlength="300" value="<?php echo htmlspecialchars($cfg['pago_qr_instrucciones'] ?? ''); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label" for="pago_qr_minutos">Minutos para pagar</label>
                                                    <input class="form-control" type="number" min="3" max="60" id="pago_qr_minutos" name="pago_qr_minutos" value="<?php echo (int) ($cfg['pago_qr_minutos'] ?? 15); ?>">
                                                </div>
                                            </div>
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" role="switch" id="pago_qr_activo" name="pago_qr_activo" value="1" <?php echo !empty($cfg['pago_qr_activo']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="pago_qr_activo">Permitir cobrar con QR en la venta de pasajes</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold"><i class="bi bi-save me-2"></i>Guardar QR</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<!--end::App Main-->

<!-- SweetAlert2 Logic -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Verificar si hay mensaje de éxito en la URL
        const urlParams = new URLSearchParams(window.location.search);
        const mensajesQr = {
            qr_guardado: ['success', 'QR de cobro guardado', ''],
            qr_sin_imagen: ['warning', 'Datos guardados', 'Para activar el cobro con QR primero cargue la imagen del QR.'],
            qr_formato: ['error', 'Imagen no válida', 'Suba una imagen PNG, JPG o WEBP del QR.'],
            qr_tamano: ['error', 'Imagen muy pesada', 'La imagen del QR debe pesar como máximo 2 MB.'],
            qr_sin_permiso: ['error', 'Sin permiso', 'Solo un Administrador puede cambiar el QR de cobro.'],
            qr_error: ['error', 'No se pudo guardar', 'Intente nuevamente.'],
            csrf: ['error', 'Sesión inválida', 'Recargue la página e intente nuevamente.'],
        };
        if (mensajesQr[urlParams.get('msg')]) {
            const [icon, title, text] = mensajesQr[urlParams.get('msg')];
            Swal.fire({ icon, title, text });
            window.history.replaceState({}, document.title, window.location.pathname + '#cobro-qr');
        }
        if (urlParams.has('msg') && urlParams.get('msg') === 'guardado') {
            Swal.fire({
                title: '¡Guardado!',
                text: 'La configuración del sistema ha sido actualizada correctamente.',
                icon: 'success',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#3085d6',
                timer: 3000,
                timerProgressBar: true
            });

            // Limpiar la URL para que no salga el alert al recargar
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
</script>

<?php require APPROOT . '/views/layouts/footer.php'; ?>