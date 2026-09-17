@extends('layouts.app')

@section('content')
<?php
$sucursales = $data['terminales'] ?? [];
$esAdmin = !empty($data['es_admin']);
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Sucursales</h3>
            <div class="text-muted small">Cada terminal es una sucursal con su propia caja. Las ventas, cajas y encomiendas quedan registradas en la sucursal donde se hacen.</div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-3">
                <?php if ($esAdmin): ?>
                    <div class="col-xl-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title" id="tituloSucursal"><i class="bi bi-building-add me-2"></i>Nueva sucursal</h3>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo URLROOT; ?>/admin/guardar_terminal" method="POST" enctype="multipart/form-data" id="formSucursal">
                                    <input type="hidden" name="csrf_token" value="<?php echo $e(csrf_token()); ?>">
                                    <input type="hidden" name="id" id="sucId">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label" for="sucNombre">Nombre <span class="text-danger">*</span></label>
                                            <input class="form-control" id="sucNombre" name="nombre_sede" maxlength="100" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="sucDireccion">Dirección <span class="text-danger">*</span></label>
                                            <input class="form-control" id="sucDireccion" name="direccion" maxlength="255" required>
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label" for="sucOficina">Oficina</label>
                                            <input class="form-control" id="sucOficina" name="numero_oficina" maxlength="50">
                                        </div>
                                        <div class="col-sm-8">
                                            <label class="form-label" for="sucTelefono">Teléfono</label>
                                            <input class="form-control" id="sucTelefono" name="telefono" maxlength="30">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="sucPrefijo">Prefijo de boleto <span class="text-danger">*</span></label>
                                            <input class="form-control text-uppercase font-monospace" id="sucPrefijo" name="prefijo_boleto" maxlength="5" pattern="[A-Za-z0-9]{2,5}" required>
                                            <div class="form-text">Los boletos de esta sucursal se numeran así: <span class="font-monospace" id="ejemploCodigo">EAL-000001</span></div>
                                        </div>
                                    </div>

                                    <hr>
                                    <div class="fw-semibold mb-1">QR de cobro propio <span class="text-muted fw-normal">(opcional)</span></div>
                                    <div class="small text-muted mb-2">Si no se carga, esta sucursal usa el QR general de Configuración.</div>
                                    <div id="qrActual" class="mb-2" hidden>
                                        <img id="qrActualImg" alt="QR actual de la sucursal" style="max-height: 120px" class="border rounded p-1 bg-white">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" value="1" id="sucQuitarQr" name="quitar_qr">
                                            <label class="form-check-label small" for="sucQuitarQr">Quitar este QR (usar el general)</label>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <input class="form-control" type="file" id="sucQr" name="pago_qr_imagen" accept="image/png,image/jpeg,image/webp" aria-label="Imagen del QR de la sucursal">
                                        </div>
                                        <div class="col-sm-6">
                                            <input class="form-control" id="sucQrTitular" name="pago_qr_titular" maxlength="120" placeholder="Titular" aria-label="Titular de la cuenta del QR">
                                        </div>
                                        <div class="col-sm-6">
                                            <input class="form-control" id="sucQrEntidad" name="pago_qr_entidad" maxlength="120" placeholder="Banco / billetera" aria-label="Banco o billetera del QR">
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-4">
                                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check2 me-1"></i> Guardar sucursal</button>
                                        <button type="button" class="btn btn-light" id="btnCancelarSucursal" hidden>Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="<?php echo $esAdmin ? 'col-xl-8' : 'col-12'; ?>">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Sucursal</th>
                                            <th>Boletos</th>
                                            <th>QR</th>
                                            <th>Movimiento</th>
                                            <th>Estado</th>
                                            <?php if ($esAdmin): ?><th class="text-end">Acciones</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sucursales as $s): ?>
                                            <?php $historial = $s->usuarios + $s->cajas + $s->boletos + $s->encomiendas + $s->viajes; ?>
                                            <tr class="<?php echo $s->estado ? '' : 'text-muted'; ?>">
                                                <td>
                                                    <div class="fw-semibold"><?php echo $e($s->nombre_sede); ?></div>
                                                    <div class="small text-muted"><?php echo $e($s->direccion); ?><?php if ($s->telefono): ?> · <?php echo $e($s->telefono); ?><?php endif; ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge text-bg-dark font-monospace"><?php echo $e($s->prefijo_boleto); ?></span>
                                                    <div class="small text-muted">último N° <?php echo (int) $s->correlativo_boleto; ?></div>
                                                </td>
                                                <td class="small"><?php echo $s->pago_qr_imagen ? '<span class="badge bg-success-subtle text-success">Propio</span>' : '<span class="text-muted">General</span>'; ?></td>
                                                <td class="small text-muted">
                                                    <?php echo (int) $s->usuarios; ?> usuarios · <?php echo (int) $s->cajas; ?> cajas<br>
                                                    <?php echo (int) $s->boletos; ?> boletos · <?php echo (int) $s->viajes; ?> viajes
                                                </td>
                                                <td>
                                                    <?php echo $s->estado ? '<span class="badge bg-success-subtle text-success">Activa</span>' : '<span class="badge bg-secondary-subtle text-secondary">Inactiva</span>'; ?>
                                                </td>
                                                <?php if ($esAdmin): ?>
                                                    <td class="text-end text-nowrap">
                                                        <button type="button" class="btn btn-sm btn-light text-primary" title="Editar"
                                                            onclick='editarSucursal(<?php echo json_encode([
                                                                "id" => (int) $s->id, "nombre_sede" => $s->nombre_sede, "direccion" => $s->direccion,
                                                                "numero_oficina" => $s->numero_oficina, "telefono" => $s->telefono, "prefijo_boleto" => $s->prefijo_boleto,
                                                                "pago_qr_titular" => $s->pago_qr_titular, "pago_qr_entidad" => $s->pago_qr_entidad,
                                                                "pago_qr_imagen" => $s->pago_qr_imagen ? URLROOT . "/" . $s->pago_qr_imagen : null,
                                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'>
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <a class="btn btn-sm btn-light <?php echo $s->estado ? 'text-warning' : 'text-success'; ?>"
                                                            href="<?php echo URLROOT; ?>/admin/cambiar_estado_terminal/<?php echo (int) $s->id; ?>"
                                                            title="<?php echo $s->estado ? 'Desactivar' : 'Activar'; ?>">
                                                            <i class="bi <?php echo $s->estado ? 'bi-pause-circle' : 'bi-play-circle'; ?>"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-light text-danger" <?php echo $historial ? 'disabled title="Tiene historial: solo se puede desactivar"' : 'title="Eliminar"'; ?>
                                                            onclick="eliminarSucursal(<?php echo (int) $s->id; ?>, '<?php echo $e($s->nombre_sede); ?>')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    (function() {
        const params = new URLSearchParams(location.search);
        const mensajes = { creada: 'Sucursal creada', actualizada: 'Sucursal actualizada', eliminada: 'Sucursal eliminada' };
        if (params.get('msg')) {
            if (mensajes[params.get('msg')]) {
                Swal.fire({ icon: 'success', title: mensajes[params.get('msg')], timer: 1600, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'No se pudo completar', text: params.get('detalle') || 'Intente nuevamente.' });
            }
            history.replaceState(null, '', location.pathname);
        }

        const form = document.getElementById('formSucursal');
        if (!form) return;
        const prefijo = document.getElementById('sucPrefijo');
        const ejemplo = () => document.getElementById('ejemploCodigo').textContent = (prefijo.value.toUpperCase() || 'EAL') + '-000001';
        prefijo.addEventListener('input', () => { prefijo.value = prefijo.value.toUpperCase().replace(/[^A-Z0-9]/g, ''); ejemplo(); });

        window.editarSucursal = function(s) {
            form.reset();
            document.getElementById('sucId').value = s.id;
            document.getElementById('sucNombre').value = s.nombre_sede || '';
            document.getElementById('sucDireccion').value = s.direccion || '';
            document.getElementById('sucOficina').value = s.numero_oficina || '';
            document.getElementById('sucTelefono').value = s.telefono || '';
            prefijo.value = s.prefijo_boleto || '';
            document.getElementById('sucQrTitular').value = s.pago_qr_titular || '';
            document.getElementById('sucQrEntidad').value = s.pago_qr_entidad || '';
            document.getElementById('qrActual').hidden = !s.pago_qr_imagen;
            if (s.pago_qr_imagen) document.getElementById('qrActualImg').src = s.pago_qr_imagen;
            document.getElementById('tituloSucursal').innerHTML = '<i class="bi bi-pencil me-2"></i>Editar sucursal';
            document.getElementById('btnCancelarSucursal').hidden = false;
            ejemplo();
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        document.getElementById('btnCancelarSucursal').addEventListener('click', () => {
            form.reset();
            document.getElementById('sucId').value = '';
            document.getElementById('qrActual').hidden = true;
            document.getElementById('tituloSucursal').innerHTML = '<i class="bi bi-building-add me-2"></i>Nueva sucursal';
            document.getElementById('btnCancelarSucursal').hidden = true;
            ejemplo();
        });

        window.eliminarSucursal = function(id, nombre) {
            Swal.fire({
                icon: 'warning',
                title: 'Eliminar sucursal',
                text: 'Se eliminará ' + nombre + '. Esta acción no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            }).then(r => { if (r.isConfirmed) location.href = '<?php echo URLROOT; ?>/admin/eliminar_terminal/' + id; });
        };
        ejemplo();
    })();
</script>

@endsection
