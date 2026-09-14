<?php
$usuarios = $data['usuarios'] ?? [];
$roles = $data['roles'] ?? [];
$miId = (int) ($data['usuario_actual_id'] ?? 0);
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$porRol = [];
foreach ($usuarios as $u) {
    $clave = $u->rol ?: 'Sin rol';
    $porRol[$clave] = ($porRol[$clave] ?? 0) + 1;
}
ksort($porRol);
$automaticas = array_values(array_filter($usuarios, fn($u) => $u->automatica && $u->estado === 'activo'));
?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h3 class="mb-0">Usuarios del sistema</h3>
                    <div class="text-muted small">Cuentas que pueden iniciar sesión. El rol define qué puede hacer cada una (ver <a href="<?php echo URLROOT; ?>/admin/roles_permisos">Roles y permisos</a>).</div>
                </div>
                <button type="button" class="btn btn-primary" onclick="abrirUsuario()">
                    <i class="bi bi-person-plus me-1"></i> Nuevo usuario
                </button>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <?php if ($automaticas): ?>
                <div class="alert alert-warning d-flex gap-3 align-items-start">
                    <i class="bi bi-shield-exclamation fs-5"></i>
                    <div>
                        <strong><?php echo count($automaticas); ?> cuenta(s) creadas automáticamente para choferes siguen activas</strong>
                        (<?php echo $e(implode(', ', array_map(fn($u) => $u->username ?: $u->email, $automaticas))); ?>).
                        Se crearon para que los viajes pudieran guardar al chofer; eso ya no es necesario y algunas tienen rol con acceso al sistema.
                        Si esas personas no usan el sistema, desactívelas.
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Filtrar por rol" id="filtroRoles">
                        <button type="button" class="btn btn-sm btn-dark" data-rol="">Todos <span class="badge text-bg-light ms-1"><?php echo count($usuarios); ?></span></button>
                        <?php foreach ($porRol as $nombre => $cantidad): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-rol="<?php echo $e($nombre); ?>">
                                <?php echo $e($nombre); ?> <span class="badge text-bg-secondary ms-1"><?php echo $cantidad; ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <input type="search" class="form-control" id="buscarUsuario" placeholder="Buscar por nombre, correo, usuario o documento…">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="filtroEstado" aria-label="Filtrar por estado">
                                <option value="activo">Solo activos</option>
                                <option value="inactivo">Solo inactivos</option>
                                <option value="">Activos e inactivos</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Historial</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                    <?php
                                    $historial = (int) $u->total_boletos + (int) $u->total_cajas + (int) $u->total_encomiendas;
                                    $esYo = (int) $u->id === $miId;
                                    $datos = [
                                        'id' => (int) $u->id, 'nombres' => $u->nombres, 'apellidos' => $u->apellidos, 'email' => $u->email,
                                        'username' => $u->username, 'nro_documento' => $u->nro_documento, 'celular' => $u->celular,
                                        'rol_id' => (int) $u->rol_id, 'activo' => $u->estado === 'activo',
                                    ];
                                    ?>
                                    <tr data-rol="<?php echo $e($u->rol ?: 'Sin rol'); ?>" data-estado="<?php echo $e($u->estado); ?>">
                                        <td>
                                            <div class="fw-semibold">
                                                <?php echo $e(trim($u->nombres . ' ' . $u->apellidos)); ?>
                                                <?php if ($esYo): ?><span class="badge text-bg-primary ms-1">Usted</span><?php endif; ?>
                                                <?php if ($u->automatica): ?><span class="badge text-bg-warning ms-1" title="Cuenta creada automáticamente para un chofer">Automática</span><?php endif; ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?php echo $e($u->email); ?>
                                                <?php if ($u->username): ?> · <?php echo $e($u->username); ?><?php endif; ?>
                                            </div>
                                        </td>
                                        <td><span class="badge text-bg-light border"><?php echo $e($u->rol ?: 'Sin rol'); ?></span></td>
                                        <td>
                                            <?php if ($u->estado === 'activo'): ?>
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?php if ($historial): ?>
                                                <?php echo (int) $u->total_boletos; ?> boletos · <?php echo (int) $u->total_cajas; ?> cajas<?php if ($u->total_encomiendas): ?> · <?php echo (int) $u->total_encomiendas; ?> encomiendas<?php endif; ?>
                                            <?php else: ?>
                                                Sin movimientos
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <button type="button" class="btn btn-sm btn-light text-primary" title="Editar"
                                                onclick='abrirUsuario(<?php echo json_encode($datos, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if (!$esYo): ?>
                                                <?php if ($u->estado === 'activo'): ?>
                                                    <button type="button" class="btn btn-sm btn-light text-warning" title="Desactivar" onclick="cambiarEstado(<?php echo (int) $u->id; ?>, false, '<?php echo $e($u->email); ?>')">
                                                        <i class="bi bi-person-slash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-light text-success" title="Activar" onclick="cambiarEstado(<?php echo (int) $u->id; ?>, true, '<?php echo $e($u->email); ?>')">
                                                        <i class="bi bi-person-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-light text-danger" title="<?php echo $historial ? 'Tiene historial: solo se puede desactivar' : 'Eliminar'; ?>"
                                                    <?php echo $historial ? 'disabled' : ''; ?>
                                                    onclick="eliminarUsuario(<?php echo (int) $u->id; ?>, '<?php echo $e($u->email); ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="filaSinResultados" hidden>
                                    <td colspan="5" class="text-center text-muted py-4">No hay usuarios con esos filtros.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal crear / editar -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="tituloModalUsuario" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" id="formUsuario" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalUsuario">Nuevo usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="uId">
                <input type="hidden" name="csrf_token" value="<?php echo $e($_SESSION['csrf_token'] ?? ''); ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="uNombres">Nombres <span class="text-danger">*</span></label>
                        <input class="form-control" id="uNombres" name="nombres" required maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="uApellidos">Apellidos <span class="text-danger">*</span></label>
                        <input class="form-control" id="uApellidos" name="apellidos" required maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="uEmail">Correo (para iniciar sesión) <span class="text-danger">*</span></label>
                        <input class="form-control" type="email" id="uEmail" name="email" required maxlength="100" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="uRol">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" id="uRol" name="rol_id" required>
                            <option value="">Seleccione un rol…</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo (int) $rol->id; ?>"><?php echo $e($rol->nombre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="uUsername">Usuario <span class="text-muted fw-normal">(opcional)</span></label>
                        <input class="form-control" id="uUsername" name="username" maxlength="50" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="uDocumento">Documento</label>
                        <input class="form-control" id="uDocumento" name="nro_documento" maxlength="20">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="uCelular">Celular</label>
                        <input class="form-control" id="uCelular" name="celular" maxlength="20">
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <div class="fw-semibold" id="tituloPassword">Contraseña</div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="uGenerar" name="generar_password" value="1">
                        <label class="form-check-label" for="uGenerar">Generar una segura automáticamente</label>
                    </div>
                </div>
                <div class="row g-3" id="camposPassword">
                    <div class="col-md-6">
                        <input class="form-control" type="password" id="uPassword" name="password" minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                    </div>
                    <div class="col-md-6">
                        <input class="form-control" type="password" id="uPassword2" name="password_confirmacion" autocomplete="new-password" placeholder="Repita la contraseña" aria-label="Repita la contraseña">
                    </div>
                </div>
                <div class="form-text" id="ayudaPassword"></div>

                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="uActivo" name="activo" value="1" checked>
                    <label class="form-check-label" for="uActivo">Cuenta activa (puede iniciar sesión)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarUsuario"><i class="bi bi-check2 me-1"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        const URL = '<?php echo URLROOT; ?>/admin/';
        const CSRF = '<?php echo $e($_SESSION['csrf_token'] ?? ''); ?>';
        const modalEl = document.getElementById('modalUsuario');
        const form = document.getElementById('formUsuario');
        const generar = document.getElementById('uGenerar');
        let editando = false;

        // ---------- Filtros ----------
        let rolFiltro = '';
        const filas = [...document.querySelectorAll('#tablaUsuarios tbody tr[data-rol]')];
        function filtrar() {
            const q = document.getElementById('buscarUsuario').value.trim().toLowerCase();
            const estado = document.getElementById('filtroEstado').value;
            let visibles = 0;
            filas.forEach(tr => {
                const ok = (!rolFiltro || tr.dataset.rol === rolFiltro)
                    && (!estado || tr.dataset.estado === estado)
                    && (!q || tr.textContent.toLowerCase().includes(q));
                tr.hidden = !ok;
                if (ok) visibles++;
            });
            document.getElementById('filaSinResultados').hidden = visibles > 0;
        }
        document.querySelectorAll('#filtroRoles button').forEach(btn => btn.addEventListener('click', () => {
            rolFiltro = btn.dataset.rol;
            document.querySelectorAll('#filtroRoles button').forEach(b => {
                const activo = b === btn;
                b.classList.toggle('btn-dark', activo);
                b.classList.toggle('btn-outline-secondary', !activo);
            });
            filtrar();
        }));
        document.getElementById('buscarUsuario').addEventListener('input', filtrar);
        document.getElementById('filtroEstado').addEventListener('change', filtrar);
        filtrar();

        // ---------- Modal ----------
        function actualizarPassword() {
            const gen = generar.checked;
            document.getElementById('camposPassword').hidden = gen;
            document.getElementById('ayudaPassword').textContent = gen
                ? 'Se generará al guardar y se mostrará una sola vez para que la entregue al usuario.'
                : (editando ? 'Déjela vacía para mantener la contraseña actual.' : '');
        }
        generar.addEventListener('change', actualizarPassword);

        window.abrirUsuario = function(u) {
            form.reset();
            editando = !!u;
            document.getElementById('tituloModalUsuario').textContent = editando ? 'Editar usuario' : 'Nuevo usuario';
            document.getElementById('tituloPassword').textContent = editando ? 'Restablecer contraseña' : 'Contraseña';
            document.getElementById('uId').value = editando ? u.id : '';
            if (editando) {
                document.getElementById('uNombres').value = u.nombres || '';
                document.getElementById('uApellidos').value = u.apellidos || '';
                document.getElementById('uEmail').value = u.email || '';
                document.getElementById('uUsername').value = u.username || '';
                document.getElementById('uDocumento').value = u.nro_documento || '';
                document.getElementById('uCelular').value = u.celular || '';
                document.getElementById('uRol').value = u.rol_id || '';
                document.getElementById('uActivo').checked = !!u.activo;
            }
            actualizarPassword();
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        };

        form.addEventListener('submit', function(ev) {
            ev.preventDefault();
            const btn = document.getElementById('btnGuardarUsuario');
            btn.disabled = true;
            enviar('guardar_usuario', new FormData(form)).then(res => {
                btn.disabled = false;
                if (res.status !== 'success') {
                    Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: res.message });
                    return;
                }
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                if (res.password_generada) {
                    Swal.fire({
                        icon: 'success',
                        title: res.message,
                        html: `<p class="mb-2">Entregue estos datos al usuario. <strong>La contraseña no se volverá a mostrar.</strong></p>
                               <div class="text-start border rounded p-3 bg-light">
                                 <div>Correo: <strong>${escapeHtml(res.email)}</strong></div>
                                 <div>Contraseña: <code class="fs-5" id="passGenerada">${escapeHtml(res.password_generada)}</code></div>
                               </div>`,
                        confirmButtonText: 'Copiar y cerrar',
                        allowOutsideClick: false,
                    }).then(() => {
                        navigator.clipboard?.writeText(res.password_generada).catch(() => {});
                        location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false }).then(() => location.reload());
                }
            });
        });

        window.cambiarEstado = function(id, activar, email) {
            Swal.fire({
                icon: 'question',
                title: activar ? 'Activar usuario' : 'Desactivar usuario',
                text: activar ? email + ' podrá volver a iniciar sesión.' : email + ' no podrá iniciar sesión; su historial se conserva.',
                showCancelButton: true,
                confirmButtonText: activar ? 'Activar' : 'Desactivar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: activar ? '#198754' : '#d97706',
            }).then(r => {
                if (!r.isConfirmed) return;
                const fd = new FormData();
                fd.append('id', id);
                if (activar) fd.append('activo', '1');
                enviar('estado_usuario', fd).then(notificar);
            });
        };

        window.eliminarUsuario = function(id, email) {
            Swal.fire({
                icon: 'warning',
                title: 'Eliminar usuario',
                text: 'Se eliminará definitivamente la cuenta ' + email + '. Esta acción no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            }).then(r => {
                if (!r.isConfirmed) return;
                const fd = new FormData();
                fd.append('id', id);
                enviar('eliminar_usuario', fd).then(notificar);
            });
        };

        function enviar(accion, fd) {
            if (!fd.has('csrf_token')) fd.append('csrf_token', CSRF);
            return fetch(URL + accion, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .catch(() => ({ status: 'error', message: 'No se pudo contactar al servidor.' }));
        }

        function notificar(res) {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: res.message, timer: 1600, showConfirmButton: false }).then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'No se pudo completar', text: res.message });
            }
        }

        function escapeHtml(t) {
            const d = document.createElement('div');
            d.textContent = t;
            return d.innerHTML;
        }
    })();
</script>
