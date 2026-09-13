<!doctype html>
<html lang="en">
<!--begin::Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo SITENAME; ?></title>

    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->

    <!--begin::Primary Meta Tags-->
    <meta name="title" content="<?php echo SITENAME; ?>" />
    <meta name="author" content="ColorlibHQ" />
    <meta name="description"
        content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS. Fully accessible with WCAG 2.1 AA compliance." />
    <meta name="keywords"
        content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard, accessible admin panel, WCAG compliant" />
    <!--end::Primary Meta Tags-->

    <!--begin::Accessibility Features-->
    <!-- Skip links will be dynamically added by accessibility.js -->
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="<?php echo URLROOT; ?>/css/adminlte.css" as="style" />
    <!--end::Accessibility Features-->

    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
        integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" media="print"
        onload="this.media='all'" />
    <!--end::Fonts-->

    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
        crossorigin="anonymous" />
    <!--end::Third Party Plugin(OverlayScrollbars)-->

    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        crossorigin="anonymous" />
    <!--end::Third Party Plugin(Bootstrap Icons)-->

    <!--begin::Required Plugin(AdminLTE)-->
    <?php
    // Cache Busting: Agregar timestamp para forzar recarga de archivos CSS
    $cssVersion = time(); // Cambia en cada request durante desarrollo
    // Para producción, usar: $cssVersion = '1.0.0'; (versión fija)
    ?>
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/adminlte.css?v=<?php echo $cssVersion; ?>" />
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/custom.css?v=<?php echo $cssVersion; ?>" />
    <!--end::Required Plugin(AdminLTE)-->

    <!--begin::SweetAlert2 (Para confirmaciones elegantes)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!--end::SweetAlert2-->

    <!-- FontAwesome 6 Free -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- QRCode.js (Para generación de códigos QR en tickets) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- jQuery (Required for Bootstrap 4/5 plugins & Custom Scripts) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- Fabric.js (Para renderizado del diagrama del bus) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js" integrity="sha512-CeIsOAsgJnmevfCi2C7Zsyy6bQKi43utIjdA87Q0ZY84oDqnI0uwfM9+bKiIkI75lUeI00WG/+uJzOmuHlesMA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- BusRenderer Component (Componente unificado para renderizado de buses) -->
    <script src="<?php echo URLROOT; ?>/js/bus-renderer.js?v=<?php echo time(); ?>"></script>

    <!-- apexcharts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
        integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0=" crossorigin="anonymous" />

    <!-- jsvectormap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css"
        integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4=" crossorigin="anonymous" />
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
        <!--begin::Header-->
        <nav class="app-header navbar navbar-expand bg-body">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Start Navbar Links-->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" id="sidebarToggle" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="#" class="nav-link">Home</a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="#" class="nav-link">Contact</a>
                    </li>
                </ul>
                <!--end::Start Navbar Links-->

                <!--begin::End Navbar Links-->
                <ul class="navbar-nav ms-auto">
                    <!--begin::Navbar Search-->
                    <li class="nav-item">
                        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                            <i class="bi bi-search"></i>
                        </a>
                    </li>
                    <!--end::Navbar Search-->

                    <!--begin::Dark Mode Toggle-->
                    <li class="nav-item">
                        <button class="nav-link btn btn-link" id="darkModeToggle" title="Cambiar tema">
                            <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                        </button>
                    </li>
                    <!--end::Dark Mode Toggle-->

                    <!--begin::Fullscreen Toggle-->
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                            <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                            <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
                        </a>
                    </li>
                    <!--end::Fullscreen Toggle-->

                    <!--begin::User Menu Dropdown-->
                    <!--begin::User Menu Dropdown-->
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle d-flex align-items-center py-1 pe-0" data-bs-toggle="dropdown">
                            <!-- Avatar Seguro (Icono) -->
                            <div class="d-flex align-items-center justify-content-center bg-primary bg-gradient text-white rounded-circle shadow-sm me-2" style="width: 38px; height: 38px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <!-- Info Usuario -->
                            <div class="d-none d-md-flex flex-column text-start lh-1">
                                <span class="fw-bold text-dark fs-6">
                                    <?php echo isset($_SESSION['usuario']) ? ucwords(strtolower($_SESSION['usuario'])) : 'Invitado'; ?>
                                </span>
                                <span class="text-muted" style="font-size: 0.75rem;">
                                    <?php echo isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Conectado'; ?>
                                </span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end border-0 shadow-lg animate__animated animate__fadeIn">
                            <!--begin::User Image (Icono Infalible)-->
                            <li class="user-header text-bg-primary bg-gradient d-flex flex-column align-items-center justify-content-center">
                                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow mb-2" style="width: 90px; height: 90px; font-size: 3rem;">
                                    <?php if (isset($_SESSION['usuario'])): ?>
                                        <!-- Iniciales del usuario -->
                                        <?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?>
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <p class="mb-0 fs-5 fw-bold">
                                    <?php echo isset($_SESSION['usuario']) ? ucwords(strtolower($_SESSION['usuario'])) : 'Usuario'; ?>
                                </p>
                                <p class="fs-7 opacity-75">
                                    <?php echo isset($_SESSION['rol']) ? ucfirst($_SESSION['rol']) : 'Administrador'; ?>
                                </p>
                            </li>
                            <!--end::User Image-->

                            <!--begin::Menu Body-->
                            <li class="user-body bg-light py-2">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <a href="#" class="btn btn-light btn-sm w-100 text-start">
                                            <i class="fas fa-user-circle me-2 text-primary"></i> Mi Perfil
                                        </a>
                                    </div>
                                    <div class="col-12">
                                        <a href="#" class="btn btn-light btn-sm w-100 text-start">
                                            <i class="fas fa-cog me-2 text-secondary"></i> Configuración
                                        </a>
                                    </div>
                                </div>
                            </li>
                            <!--end::Menu Body-->

                            <!--begin::Menu Footer-->
                            <li class="user-footer bg-white p-2 border-top">
                                <div class="d-grid gap-2">
                                    <a href="<?php echo URLROOT; ?>/admin/logout" class="btn btn-danger btn-flat">
                                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                    </a>
                                </div>
                            </li>
                            <!--end::Menu Footer-->
                        </ul>
                    </li>
                    <!--end::User Menu Dropdown-->
                </ul>
                <!--end::End Navbar Links-->
            </div>
            <!--end::Container-->
        </nav>
        <!--end::Header-->