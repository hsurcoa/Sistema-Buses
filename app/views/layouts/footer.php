    <!--begin::Footer-->
    <footer class="app-footer">
        <span>&copy; <?php echo date('Y'); ?> <?php echo SITENAME; ?>. Todos los derechos reservados.</span>
    </footer>
    <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->

    <!--begin::Scripts-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!--begin::SweetAlert2 JS-->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!--end::SweetAlert2 JS-->

    <!--begin::Sistema de Impresión de Tickets Térmicos-->
    <script src="<?php echo URLROOT; ?>/assets/js/impresion-ticket.js"></script>
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
    </body>

</html>
