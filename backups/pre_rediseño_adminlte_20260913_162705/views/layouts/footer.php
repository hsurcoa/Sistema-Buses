    <!--begin::Footer-->
    <footer class="app-footer">
        <!--begin::To the end-->
        <div class="float-end d-none d-sm-inline">Anything you want</div>
        <!--end::To the end-->
        <!--begin::Copyright-->
        <strong>
            Copyright &copy; 2014-2025&nbsp;
            <a href="https://adminlte.io" class="text-decoration-none">AdminLTE.io</a>.
        </strong>
        All rights reserved.
        <!--end::Copyright-->
    </footer>
    <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <!--begin::Script-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
        crossorigin="anonymous"></script>
    <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        crossorigin="anonymous"></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
    <script src="<?php echo URLROOT; ?>/js/adminlte.js"></script>
    <!--end::Required Plugin(AdminLTE)-->

    <!--begin::SweetAlert2 JS-->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!--end::SweetAlert2 JS-->

    <!--begin::Sistema de Impresión de Tickets Térmicos-->
    <script src="<?php echo URLROOT; ?>/assets/js/impresion-ticket.js"></script>
    <!--end::Sistema de Impresión de Tickets Térmicos-->

    <!--begin::OverlayScrollbars Configure-->
    <script>
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);

            // Disable OverlayScrollbars on mobile devices to prevent touch interference
            const isMobile = window.innerWidth <= 992;

            if (
                sidebarWrapper &&
                OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined &&
                !isMobile
            ) {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll,
                    },
                });
            }
        });
    </script>
    <!--end::OverlayScrollbars Configure-->

    <!--end::Script-->
    <!-- Sidebar Mini Logic (Updated) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebarToggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Toggle de la clase EXACTA solicitada
                    document.body.classList.toggle('sidebar-collapsed');
                });
            }
        });
    </script>

    <!-- Dark Mode Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const htmlElement = document.documentElement;
            const sidebar = document.getElementById('appSidebar');

            // Cargar tema guardado
            const savedTheme = localStorage.getItem('theme') || 'light';
            applyTheme(savedTheme);

            // Toggle al hacer click
            darkModeToggle.addEventListener('click', function() {
                const currentTheme = htmlElement.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                applyTheme(newTheme);
                localStorage.setItem('theme', newTheme);
            });

            function applyTheme(theme) {
                // Aplicar al documento principal
                htmlElement.setAttribute('data-bs-theme', theme);

                // Aplicar al sidebar
                if (sidebar) {
                    sidebar.setAttribute('data-bs-theme', theme);
                }

                // Sincronizar clase 'dark-mode' para custom.css
                if (theme === 'dark') {
                    document.body.classList.add('dark-mode');
                } else {
                    document.body.classList.remove('dark-mode');
                }

                // Actualizar icono
                updateIcon(theme);
            }

            function updateIcon(theme) {
                if (theme === 'dark') {
                    themeIcon.className = 'bi bi-sun-fill';
                } else {
                    themeIcon.className = 'bi bi-moon-stars-fill';
                }
            }
        });
    </script>

    <!-- Active Menu Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /**
             * Sidebar Active Menu Handler
             * Detects current URL and activates corresponding sidebar link.
             * Also expands parent menu if the active link is inside a subplot.
             */
            const currentUrl = window.location.href;
            // Target the specific sidebar menu class used in sidebar.php
            const navLinks = document.querySelectorAll('.sidebar-menu .nav-link');

            navLinks.forEach(link => {
                // Check if the link's href matches the current URL
                if (link.href === currentUrl || (currentUrl.startsWith(link.href) && link.href !== '<?php echo URLROOT; ?>/dashboard' && link.href !== '<?php echo URLROOT; ?>/' && link.href !== '<?php echo URLROOT; ?>')) {

                    // Add active class to the current link
                    link.classList.add('active');

                    // Check if it's inside a treeview (submenu)
                    const parentTreeview = link.closest('.nav-treeview');
                    if (parentTreeview) {
                        // Find the parent menu item (Li) containing the treeview
                        const parentItem = parentTreeview.closest('.nav-item');
                        if (parentItem) {
                            // Add menu-open to expand the submenu
                            parentItem.classList.add('menu-open');

                            // Find the parent link (the one that toggles the submenu) and make it active too
                            const parentLink = parentItem.querySelector('.nav-link');
                            if (parentLink) {
                                parentLink.classList.add('active');
                            }
                        }
                    }
                }
            });
        });
    </script>
    </body>
    <!--end::Body-->

    </html>