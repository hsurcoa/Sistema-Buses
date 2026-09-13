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