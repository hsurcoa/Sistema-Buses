<!-- Obtener Configuración Dinámica -->
<?php
$db_sidebar = new Database();
$db_sidebar->query("SELECT clave, valor FROM configuracion_sistema");
$results_sidebar = $db_sidebar->resultSet();
$config_sidebar = [];
foreach ($results_sidebar as $r) {
    $config_sidebar[$r->clave] = $r->valor;
}

$nombreEmpresa = !empty($config_sidebar['empresa_nombre']) ? $config_sidebar['empresa_nombre'] : 'Valhalla';
$sloganEmpresa = !empty($config_sidebar['empresa_slogan']) ? $config_sidebar['empresa_slogan'] : 'EMPRESA DE TRANSPORTE';
$logoEmpresa = !empty($config_sidebar['empresa_logo']) ? URLROOT . '/' . $config_sidebar['empresa_logo'] : '';
?>
<!--begin::Sidebar-->
<aside class="app-sidebar" id="appSidebar">
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">
        <a href="<?php echo URLROOT; ?>/dashboard" class="brand-link">
            <?php if (!empty($logoEmpresa)): ?>
                <img src="<?php echo $logoEmpresa; ?>" alt="Logo" class="brand-image" />
            <?php else: ?>
                <span class="brand-mark"><?php echo strtoupper(substr($nombreEmpresa, 0, 1)); ?></span>
            <?php endif; ?>
            <span class="brand-text">
                <?php echo htmlspecialchars($nombreEmpresa); ?>
                <span class="brand-subtitle"><?php echo htmlspecialchars($sloganEmpresa); ?></span>
            </span>
        </a>
    </div>
    <!--end::Sidebar Brand-->

    <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" role="navigation" aria-label="Navegación principal" id="navigation">

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
                            <a href="<?php echo URLROOT; ?>/ventas/crear_ruta" class="nav-link">
                                <i class="nav-icon bi bi-plus-circle"></i>
                                <p>Crear Rutas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo URLROOT; ?>/reportes/pasajeros" class="nav-link">
                                <i class="nav-icon bi bi-file-earmark-text"></i>
                                <p>Reporte de Pasajeros</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-ticket-perforated"></i>
                        <p>Post-Venta / Boletos</p>
                    </a>
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
                            <a href="<?php echo URLROOT; ?>/encomiendas/crear" class="nav-link">
                                <i class="nav-icon bi bi-plus-circle"></i>
                                <p>Nueva Encomienda</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo URLROOT; ?>/encomiendas" class="nav-link">
                                <i class="nav-icon bi bi-list-ul"></i>
                                <p>Listado / Entregas</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-clock-history"></i>
                        <p>
                            Reservas
                            <span class="nav-badge badge">5</span>
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-people"></i>
                        <p>Clientes / Pasajeros</p>
                    </a>
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
                        <?php if (($_SESSION['rol'] ?? '') === 'Administrador'): ?>
                        <li class="nav-item">
                            <a href="<?php echo URLROOT; ?>/admin/usuarios" class="nav-link">
                                <i class="nav-icon fas fa-users-cog"></i>
                                <p>Usuarios del sistema</p>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a href="<?php echo URLROOT; ?>/admin/roles_permisos" class="nav-link">
                                <i class="nav-icon fas fa-key"></i>
                                <p>Roles y permisos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo URLROOT; ?>/admin/registrar_personal" class="nav-link">
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
                            <a href="<?php echo URLROOT; ?>/admin/registrar_buses" class="nav-link">
                                <i class="nav-icon bi bi-bus-front"></i>
                                <p>Registrar Buses</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo URLROOT; ?>/admin/asignar_buses" class="nav-link">
                                <i class="nav-icon bi bi-person-badge"></i>
                                <p>Asignar Buses</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo URLROOT; ?>/admin/registrar_terminal" class="nav-link">
                                <i class="nav-icon bi bi-building"></i>
                                <p>Sucursales</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo URLROOT; ?>/series" class="nav-link">
                                <i class="nav-icon bi bi-ticket-perforated"></i>
                                <p>Registrar Serie de Boletos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo URLROOT; ?>/admin/rutas_paradas" class="nav-link">
                                <i class="nav-icon bi bi-map"></i>
                                <p>Rutas y Paradas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo URLROOT; ?>/admin/tipos_buses" class="nav-link">
                                <i class="nav-icon bi bi-sliders"></i>
                                <p>Tipos de Buses</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="<?php echo URLROOT; ?>/caja" class="nav-link">
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
                            <a href="<?php echo URLROOT; ?>/reportes/financiero" class="nav-link">
                                <i class="nav-icon bi bi-currency-exchange"></i>
                                <p>Reporte Financiero</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo URLROOT; ?>/reportes/pasajeros" class="nav-link">
                                <i class="nav-icon bi bi-file-earmark-text"></i>
                                <p>Reporte de Pasajeros</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="<?php echo URLROOT; ?>/configuracion" class="nav-link">
                        <i class="nav-icon bi bi-gear"></i>
                        <p>Configuración</p>
                    </a>
                </li>
                <li class="nav-header">MANTENIMIENTO</li>
                <li class="nav-item">
                    <a href="<?php echo URLROOT; ?>/backup" class="nav-link">
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
