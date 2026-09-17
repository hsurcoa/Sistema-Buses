<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
    <meta charset="utf-8" />
    <title>{{ SITENAME }}</title>

    <!--begin::Favicon (antes no había ninguno: el navegador mostraba su
         icono genérico de pestaña sin título/favicon). SVG con el emoji de
         bus para que se vea nítido en cualquier tamaño, con favicon.ico como
         respaldo para navegadores que no soportan favicon en SVG.-->
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%9A%8C%3C/text%3E%3C/svg%3E">
    <link rel="shortcut icon" href="{{ URLROOT }}/favicon.ico">
    <!--end::Favicon-->

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

    <!--begin::Preconnect a los CDN restantes (Bootstrap/Iconos/Fabric/QR/etc.
         siguen viniendo de afuera). No evita que fallen, pero adelanta la
         conexión DNS/TLS para que, si responden, tarden menos en aparecer. -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <!--end::Preconnect CDNs restantes-->

    <!--begin::Bootstrap 5 (standalone, ya no depende de AdminLTE)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous" />
    <!--end::Bootstrap 5-->

    <!--begin::Bootstrap Icons-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <!--end::Bootstrap Icons-->

    <!-- FontAwesome 6 Free (usado por iconos existentes en varias vistas) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!--begin::Diseño propio del sistema (reemplaza adminlte.css)-->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}" />
    <!--end::Diseño propio-->

    <!--begin::SweetAlert2 (alojado local: ver public/vendor/sweetalert2. Antes
         venia del CDN de jsdelivr — si esa conexion fallaba o tardaba, TODA
         la pagina quedaba en blanco, porque nada en el sitio puede mostrar
         ni un solo dialogo sin esto. Con la copia local, el sitio deja de
         depender de que un servidor externo este arriba en ese momento.)-->
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
    <!--end::SweetAlert2-->

    <!-- QRCode.js (códigos QR en tickets) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- jQuery (alojado local: ver public/vendor/jquery. Es la libreria de la
         que depende TODO el JS del sitio -- si el CDN externo fallaba, ni un
         solo boton funcionaba y la pagina quedaba en blanco.) -->
    <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>

    <!-- Fabric.js (diagrama del bus) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js" integrity="sha512-CeIsOAsgJnmevfCi2C7Zsyy6bQKi43utIjdA87Q0ZY84oDqnI0uwfM9+bKiIkI75lUeI00WG/+uJzOmuHlesMA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- BusRenderer -->
    <script src="{{ asset('js/bus-renderer.js') }}?v={{ filemtime(public_path('js/bus-renderer.js')) }}"></script>

    <!-- apexcharts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css" integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0=" crossorigin="anonymous" />

    <!-- jsvectormap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css" integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4=" crossorigin="anonymous" />

    <!-- Flatpickr: reemplaza el input type="date" nativo del navegador (distinto
         en cada uno, incomodo para años lejanos) en todo el sistema -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css" />

    @stack('styles')
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
                                @if(auth()->check())
                                    {{ strtoupper(substr(auth()->user()->nombreCompleto(), 0, 1)) }}
                                @else
                                    <i class="fas fa-user"></i>
                                @endif
                            </div>
                            <div class="d-none d-md-flex flex-column text-start lh-1 ms-2">
                                <span class="fw-semibold user-name">
                                    {{ auth()->check() ? ucwords(strtolower(auth()->user()->nombreCompleto())) : 'Invitado' }}
                                </span>
                                <span class="user-role">
                                    {{ auth()->check() ? ucfirst(auth()->user()->rol?->nombre ?? 'Conectado') : 'Conectado' }}
                                    @if(auth()->check() && auth()->user()->sucursal?->nombre_sede) &middot; {{ auth()->user()->sucursal->nombre_sede }} @endif
                                </span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end border-0 shadow-lg user-dropdown">
                            <li class="user-dropdown-header">
                                <div class="user-avatar user-avatar-lg">
                                    @if(auth()->check())
                                        {{ strtoupper(substr(auth()->user()->nombreCompleto(), 0, 1)) }}
                                    @else
                                        <i class="fas fa-user"></i>
                                    @endif
                                </div>
                                <p class="mb-0 fw-bold">
                                    {{ auth()->check() ? ucwords(strtolower(auth()->user()->nombreCompleto())) : 'Usuario' }}
                                </p>
                                <p class="user-role-lg">
                                    {{ auth()->check() ? ucfirst(auth()->user()->rol?->nombre ?? 'Administrador') : 'Administrador' }}
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
                                        <a href="{{ URLROOT }}/configuracion" class="btn btn-light btn-sm w-100 text-start">
                                            <i class="fas fa-cog me-2 text-secondary"></i> Configuración
                                        </a>
                                    </div>
                                </div>
                            </li>
                            <li class="border-top p-2">
                                <div class="d-grid">
                                    <form method="POST" action="{{ route('logout') }}" id="formLogout">
                                        @csrf
                                        <button type="button" class="btn btn-danger w-100" id="btnLogout">
                                            <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                                        </button>
                                    </form>
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

        @php
            $configSidebar = \Illuminate\Support\Facades\DB::table('configuracion_sistema')->pluck('valor', 'clave');
            $nombreEmpresa = $configSidebar->get('empresa_nombre') ?: 'Valhalla';
            $sloganEmpresa = $configSidebar->get('empresa_slogan') ?: 'EMPRESA DE TRANSPORTE';
            $logoEmpresa = $configSidebar->get('empresa_logo') ? asset($configSidebar->get('empresa_logo')) : '';
            $rolActual = auth()->user()?->rol?->nombre ?? '';
        @endphp
        <!--begin::Sidebar-->
        <aside class="app-sidebar" id="appSidebar">
            <!--begin::Sidebar Brand-->
            <div class="sidebar-brand">
                <a href="{{ URLROOT }}/dashboard" class="brand-link">
                    @if(!empty($logoEmpresa))
                        <img src="{{ $logoEmpresa }}" alt="Logo" class="brand-image" />
                    @else
                        <span class="brand-mark">{{ strtoupper(substr($nombreEmpresa, 0, 1)) }}</span>
                    @endif
                    <span class="brand-text">
                        {{ $nombreEmpresa }}
                        <span class="brand-subtitle">{{ $sloganEmpresa }}</span>
                    </span>
                </a>
            </div>
            <!--end::Sidebar Brand-->

            <!--begin::Sidebar Wrapper-->
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" role="navigation" aria-label="Navegación principal" id="navigation">

                        <li class="nav-item">
                            <a href="{{ URLROOT }}/dashboard" class="nav-link">
                                <i class="nav-icon bi bi-grid-1x2-fill"></i>
                                <p>Panel</p>
                            </a>
                        </li>

                        <li class="nav-header">OPERACIONES DIARIAS</li>

                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-bus-front"></i>
                                <p>
                                    Venta de Pasajes
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/ventas/crear_ruta" class="nav-link">
                                        <i class="nav-icon bi bi-ticket-perforated-fill"></i>
                                        <p>Vender Pasajes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/reportes/pasajeros" class="nav-link">
                                        <i class="nav-icon bi bi-file-earmark-text"></i>
                                        <p>Reporte de Pasajeros</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-box-seam"></i>
                                <p>
                                    Encomiendas
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/encomiendas/crear" class="nav-link">
                                        <i class="nav-icon bi bi-plus-circle"></i>
                                        <p>Nueva Encomienda</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/encomiendas" class="nav-link">
                                        <i class="nav-icon bi bi-list-ul"></i>
                                        <p>Listado / Entregas</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-user-shield"></i>
                                <p>
                                    Administrador
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @if($rolActual === 'Administrador')
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/admin/usuarios" class="nav-link">
                                        <i class="nav-icon fas fa-users-cog"></i>
                                        <p>Usuarios del sistema</p>
                                    </a>
                                </li>
                                @endif
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/admin/roles_permisos" class="nav-link">
                                        <i class="nav-icon fas fa-key"></i>
                                        <p>Roles y permisos</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/admin/registrar_personal" class="nav-link">
                                        <i class="nav-icon fas fa-user-plus"></i>
                                        <p>Registrar personal</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- REGISTROS -->
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-folder2-open"></i>
                                <p>
                                    Registros
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/admin/registrar_buses" class="nav-link">
                                        <i class="nav-icon bi bi-bus-front"></i>
                                        <p>Registrar Buses</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/admin/asignar_buses" class="nav-link">
                                        <i class="nav-icon bi bi-person-badge"></i>
                                        <p>Choferes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/admin/registrar_terminal" class="nav-link">
                                        <i class="nav-icon bi bi-building"></i>
                                        <p>Sucursales</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/series" class="nav-link">
                                        <i class="nav-icon bi bi-ticket-perforated"></i>
                                        <p>Registrar Serie de Boletos</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/admin/rutas_paradas" class="nav-link">
                                        <i class="nav-icon bi bi-map"></i>
                                        <p>Rutas y tarifas</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/admin/tipos_buses" class="nav-link">
                                        <i class="nav-icon bi bi-sliders"></i>
                                        <p>Tipos de Buses</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="{{ URLROOT }}/caja" class="nav-link">
                                <i class="nav-icon bi bi-currency-dollar"></i>
                                <p>Control de Caja</p>
                            </a>
                        </li>

                        <li class="nav-header">ADMINISTRACIÓN</li>

                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-file-earmark-bar-graph"></i>
                                <p>
                                    Reportes
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/reportes/financiero" class="nav-link">
                                        <i class="nav-icon bi bi-currency-exchange"></i>
                                        <p>Reporte Financiero</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/reportes/pasajeros" class="nav-link">
                                        <i class="nav-icon bi bi-file-earmark-text"></i>
                                        <p>Reporte de Pasajeros</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ URLROOT }}/reportes/cancelaciones" class="nav-link">
                                        <i class="nav-icon bi bi-arrow-counterclockwise"></i>
                                        <p>Bitácora de Cancelaciones</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="{{ URLROOT }}/configuracion" class="nav-link">
                                <i class="nav-icon bi bi-gear"></i>
                                <p>Configuración</p>
                            </a>
                        </li>
                        <li class="nav-header">MANTENIMIENTO</li>
                        <li class="nav-item">
                            <a href="{{ URLROOT }}/backup" class="nav-link">
                                <i class="nav-icon bi bi-cloud-download"></i>
                                <p>Copia de Seguridad</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <!--end::Sidebar Wrapper-->
        </aside>
        <!--end::Sidebar-->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        @yield('content')

        <!--begin::Footer-->
        <footer class="app-footer">
            <span>&copy; {{ date('Y') }} {{ SITENAME }}. Todos los derechos reservados.</span>
        </footer>
        <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->

    <!--begin::Scripts-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!--begin::SweetAlert2 JS (alojado local, ver comentario junto a su CSS más arriba)-->
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <!--end::SweetAlert2 JS-->

    <!--begin::Sesion expirada (aviso global)-->
    <script>
        // Si la sesion vence con la pantalla abierta, cualquier peticion AJAX
        // recibe 401 + code SESSION_EXPIRED (ver legacy/public/index.php). En vez
        // de un error generico, se ofrece volver a iniciar sesion.
        (function() {
            let avisado = false;

            function avisarSesionExpirada(redirect) {
                if (avisado) return;
                avisado = true;
                const destino = redirect || '{{ URLROOT }}/login.php';
                if (typeof Swal === 'undefined') {
                    window.location.href = destino;
                    return;
                }
                Swal.fire({
                    icon: 'warning',
                    title: 'Su sesión expiró',
                    text: 'Por seguridad debe iniciar sesión nuevamente. Los datos no guardados de esta pantalla se perderán.',
                    confirmButtonText: 'Iniciar sesión',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then(() => { window.location.href = destino; });
            }

            function revisar(status, texto) {
                if (status !== 401 || !texto) return;
                try {
                    const data = typeof texto === 'string' ? JSON.parse(texto) : texto;
                    if (data && data.code === 'SESSION_EXPIRED') avisarSesionExpirada(data.redirect);
                } catch (e) {}
            }

            if (window.jQuery) {
                jQuery(document).ajaxComplete(function(_e, xhr) {
                    revisar(xhr.status, xhr.responseText);
                });
            }

            if (window.fetch) {
                const fetchOriginal = window.fetch;
                window.fetch = function() {
                    return fetchOriginal.apply(this, arguments).then(function(res) {
                        if (res.status === 401) {
                            res.clone().text().then(t => revisar(401, t)).catch(() => {});
                        }
                        return res;
                    });
                };
            }
        })();
    </script>
    <!--end::Sesion expirada-->


    <!--begin::Sistema de Impresión de Tickets Térmicos-->
    <script src="{{ asset('assets/js/impresion-ticket.js') }}"></script>
    <!--end::Sistema de Impresión de Tickets Térmicos-->

    <!--begin::App Shell Script (reemplaza adminlte.js)-->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const body = document.body;
            const sidebar = document.getElementById('appSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const toggleBtn = document.getElementById('sidebarToggle');

            function isMobile() {
                return window.innerWidth < 992;
            }

            // ---- Toggle de sidebar (mini en desktop, off-canvas en móvil) ----
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (isMobile()) {
                        body.classList.toggle('sidebar-open-mobile');
                    } else {
                        body.classList.toggle('sidebar-collapsed');
                    }
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    body.classList.remove('sidebar-open-mobile');
                });
            }

            // ---- Submenús (treeview) ----
            document.querySelectorAll('.sidebar-menu > .nav-item').forEach(function(item) {
                const link = item.querySelector(':scope > .nav-link');
                const submenu = item.querySelector(':scope > .nav-treeview');
                if (!link || !submenu) return;

                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isOpen = item.classList.contains('menu-open');

                    // Cerrar hermanos abiertos (acordeón)
                    item.parentElement.querySelectorAll(':scope > .nav-item.menu-open').forEach(function(sibling) {
                        if (sibling !== item) sibling.classList.remove('menu-open');
                    });

                    item.classList.toggle('menu-open', !isOpen);
                });
            });

            // ---- Resaltar enlace activo según la URL actual ----
            const currentUrl = window.location.href.split('?')[0].replace(/\/$/, '');
            document.querySelectorAll('.sidebar-menu .nav-link[href]').forEach(function(link) {
                const linkUrl = link.href.split('?')[0].replace(/\/$/, '');
                if (linkUrl && linkUrl === currentUrl && link.getAttribute('href') !== '#') {
                    link.classList.add('active');
                    const parentTreeview = link.closest('.nav-treeview');
                    if (parentTreeview) {
                        const parentItem = parentTreeview.closest('.nav-item');
                        if (parentItem) {
                            parentItem.classList.add('menu-open');
                            const parentLink = parentItem.querySelector(':scope > .nav-link');
                            if (parentLink) parentLink.classList.add('active');
                        }
                    }
                }
            });

            // ---- Fullscreen ----
            const fsBtn = document.getElementById('fullscreenToggle');
            const fsIcon = document.getElementById('fullscreenIcon');
            if (fsBtn) {
                fsBtn.addEventListener('click', function() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen?.().catch(() => {});
                    } else {
                        document.exitFullscreen?.().catch(() => {});
                    }
                });
                document.addEventListener('fullscreenchange', function() {
                    if (fsIcon) {
                        fsIcon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
                    }
                });
            }
        });
    </script>
    <!--end::App Shell Script-->

    <!--begin::Dark Mode Toggle (contenido, independiente del sidebar)-->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const themeIcon = document.getElementById('themeIcon');

            function applyTheme(theme) {
                document.body.classList.toggle('dark-mode', theme === 'dark');
                if (themeIcon) {
                    themeIcon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
                }
                // Permite que scripts de la página (ej. gráficos de Chart.js, que
                // pintan sus colores una sola vez al crearse) se repinten cuando
                // el usuario cambia de tema sin recargar.
                document.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
            }

            const savedTheme = localStorage.getItem('theme') || 'light';
            applyTheme(savedTheme);

            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function() {
                    const isDark = document.body.classList.contains('dark-mode');
                    const newTheme = isDark ? 'light' : 'dark';
                    applyTheme(newTheme);
                    localStorage.setItem('theme', newTheme);
                });
            }
        });
    </script>
    <!--end::Dark Mode Toggle-->

    <!--begin::Flatpickr (reemplaza el input type="date" nativo, ver public/css/custom.css)-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/es.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof flatpickr === 'undefined') return;
            flatpickr.localize(flatpickr.l10ns.es);

            // input[type=date] pierde el picker nativo (inconsistente entre
            // navegadores) y pasa a texto; se mantiene el mismo name/value/
            // required para no tocar ningun formulario/back-end existente.
            document.querySelectorAll('input[type="date"]').forEach(function(input) {
                input.type = 'text';
                flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    allowInput: true,
                    disableMobile: true,
                });
            });
        });
    </script>
    <!--end::Flatpickr-->

    <!--begin::Confirmacion de logout con SweetAlert2-->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnLogout = document.getElementById('btnLogout');
            if (!btnLogout) return;
            btnLogout.addEventListener('click', function() {
                Swal.fire({
                    icon: 'question',
                    title: '¿Cerrar sesión?',
                    text: 'Volverá a la pantalla de inicio de sesión.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cerrar sesión',
                    cancelButtonText: 'Cancelar',
                }).then(function(result) {
                    if (result.isConfirmed) document.getElementById('formLogout').submit();
                });
            });
        });
    </script>
    <!--end::Confirmacion de logout-->

    <!--begin::Tarjetas con inclinacion 3D (componente global .tool-card, ver public/css/custom.css)-->
    <script>
        (function() {
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const hasFinePointer = window.matchMedia('(pointer: fine)').matches;
            if (reduceMotion || !hasFinePointer) return;

            document.querySelectorAll('.tool-card').forEach(function(card) {
                const icon = card.querySelector('.icon');

                card.addEventListener('pointermove', function(event) {
                    const rect = card.getBoundingClientRect();
                    const px = (event.clientX - rect.left) / rect.width - 0.5;
                    const py = (event.clientY - rect.top) / rect.height - 0.5;

                    card.style.transform =
                        'perspective(900px) rotateX(' + (-py * 9).toFixed(2) + 'deg) rotateY(' + (px * 11).toFixed(2) + 'deg) translateY(-8px)';
                    card.style.setProperty('--mx', ((px + 0.5) * 100) + '%');
                    card.style.setProperty('--my', ((py + 0.5) * 100) + '%');

                    if (icon) icon.style.transform = `translate3d(${px * 12}px, ${py * 12}px, 24px) scale(1.14)`;
                });
                card.addEventListener('pointerleave', function() {
                    card.style.transform = '';
                    if (icon) icon.style.transform = '';
                });
            });
        })();
    </script>
    <!--end::Tarjetas con inclinacion 3D-->

    @stack('scripts')
    </body>

</html>
