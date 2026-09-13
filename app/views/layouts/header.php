<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
    <meta charset="utf-8" />
    <title><?php echo SITENAME; ?></title>

    <!--begin::Accessibility / Meta-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light" />
    <meta name="theme-color" content="#0f172a" />
    <meta name="description" content="Sistema de venta de pasajes y encomiendas." />
    <!--end::Accessibility / Meta-->

    <!--begin::Fonts (Inter)-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!--end::Fonts-->

    <!--begin::Bootstrap 5 (standalone, ya no depende de AdminLTE)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous" />
    <!--end::Bootstrap 5-->

    <!--begin::Bootstrap Icons-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <!--end::Bootstrap Icons-->

    <!-- FontAwesome 6 Free (usado por iconos existentes en varias vistas) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!--begin::Diseño propio del sistema (reemplaza adminlte.css)-->
    <?php $cssVersion = time(); // cache-busting en desarrollo ?>
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/custom.css?v=<?php echo $cssVersion; ?>" />
    <!--end::Diseño propio-->

    <!--begin::SweetAlert2-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!--end::SweetAlert2-->

    <!-- QRCode.js (códigos QR en tickets) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- jQuery (requerido por plugins y scripts existentes) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- Fabric.js (diagrama del bus) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js" integrity="sha512-CeIsOAsgJnmevfCi2C7Zsyy6bQKi43utIjdA87Q0ZY84oDqnI0uwfM9+bKiIkI75lUeI00WG/+uJzOmuHlesMA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- BusRenderer -->
    <script src="<?php echo URLROOT; ?>/js/bus-renderer.js?v=<?php echo time(); ?>"></script>

    <!-- apexcharts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css" integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0=" crossorigin="anonymous" />

    <!-- jsvectormap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css" integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4=" crossorigin="anonymous" />
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="app-loaded">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
        <!--begin::Header-->
        <nav class="app-header">
            <div class="app-header-inner">
                <!--begin::Start-->
                <ul class="navbar-nav app-header-start">
                    <li class="nav-item">
                        <a class="nav-link header-icon-btn" id="sidebarToggle" href="#" role="button" aria-label="Alternar menú">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                </ul>
                <!--end::Start-->

                <!--begin::End-->
                <ul class="navbar-nav app-header-end">
                    <!--begin::Dark Mode Toggle-->
                    <li class="nav-item">
                        <button class="nav-link header-icon-btn" id="darkModeToggle" title="Cambiar tema" type="button">
                            <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                        </button>
                    </li>
                    <!--end::Dark Mode Toggle-->

                    <!--begin::Fullscreen Toggle-->
                    <li class="nav-item">
                        <button class="nav-link header-icon-btn" id="fullscreenToggle" title="Pantalla completa" type="button">
                            <i class="bi bi-arrows-fullscreen" id="fullscreenIcon"></i>
                        </button>
                    </li>
                    <!--end::Fullscreen Toggle-->

                    <!--begin::User Menu Dropdown-->
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle d-flex align-items-center py-1 pe-0" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?php if (isset($_SESSION['usuario'])): ?>
                                    <?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?>
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="d-none d-md-flex flex-column text-start lh-1 ms-2">
                                <span class="fw-semibold user-name">
                                    <?php echo isset($_SESSION['usuario']) ? ucwords(strtolower($_SESSION['usuario'])) : 'Invitado'; ?>
                                </span>
                                <span class="user-role">
                                    <?php echo isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Conectado'; ?>
                                </span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end border-0 shadow-lg user-dropdown">
                            <li class="user-dropdown-header">
                                <div class="user-avatar user-avatar-lg">
                                    <?php if (isset($_SESSION['usuario'])): ?>
                                        <?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?>
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <p class="mb-0 fw-bold">
                                    <?php echo isset($_SESSION['usuario']) ? ucwords(strtolower($_SESSION['usuario'])) : 'Usuario'; ?>
                                </p>
                                <p class="user-role-lg">
                                    <?php echo isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Administrador'; ?>
                                </p>
                            </li>
                            <li class="px-3 py-2">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <a href="#" class="btn btn-light btn-sm w-100 text-start">
                                            <i class="fas fa-user-circle me-2 text-primary"></i> Mi Perfil
                                        </a>
                                    </div>
                                    <div class="col-12">
                                        <a href="<?php echo URLROOT; ?>/configuracion" class="btn btn-light btn-sm w-100 text-start">
                                            <i class="fas fa-cog me-2 text-secondary"></i> Configuración
                                        </a>
                                    </div>
                                </div>
                            </li>
                            <li class="border-top p-2">
                                <div class="d-grid">
                                    <a href="<?php echo URLROOT; ?>/admin/logout" class="btn btn-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </li>
                    <!--end::User Menu Dropdown-->
                </ul>
                <!--end::End-->
            </div>
        </nav>
        <!--end::Header-->
