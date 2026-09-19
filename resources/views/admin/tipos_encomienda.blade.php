@extends('layouts.app')

@section('content')
<?php
$tipos = $data['tipos'] ?? [];
$esAdmin = !empty($data['es_admin']);
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0">Arancel de Encomiendas</h3>
            <div class="text-muted small">Precio por tipo de paquete: un monto fijo, más un cargo por cada kg que pase del peso incluido. Este es el precio que se suma al costo base del destino elegido en cada envío.</div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-3">
                <?php if ($esAdmin): ?>
                    <div class="col-xl-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title" id="tituloTipo"><i class="bi bi-tag me-2"></i>Nuevo tipo de paquete</h3>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo URLROOT; ?>/admin/guardar_tipo_encomienda" method="POST" id="formTipo">
                                    <input type="hidden" name="csrf_token" value="<?php echo $e(csrf_token()); ?>">
                                    <input type="hidden" name="id" id="tipoId">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label" for="tipoNombre">Nombre <span class="text-danger">*</span></label>
                                            <input class="form-control" id="tipoNombre" name="nombre" maxlength="50" required placeholder="Ej. Caja mediana">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="tipoDescripcion">Descripción</label>
                                            <input class="form-control" id="tipoDescripcion" name="descripcion" maxlength="255" placeholder="Ej. Hasta 10kg, no fraccionable">
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label" for="tipoPrecioExtra">Precio del tipo (Bs) <span class="text-danger">*</span></label>
                                            <input class="form-control" type="number" step="0.01" min="0" id="tipoPrecioExtra" name="precio_extra" required value="0">
                                            <div class="form-text">Se suma al precio base del destino.</div>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label" for="tipoPesoIncluido">Peso incluido (kg)</label>
                                            <input class="form-control" type="number" step="0.01" min="0" id="tipoPesoIncluido" name="peso_incluido_kg" placeholder="Sin límite">
                                            <div class="form-text">Vacío = sin límite de peso.</div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="tipoPrecioKg">Precio por kg excedente (Bs)</label>
                                            <input class="form-control" type="number" step="0.01" min="0" id="tipoPrecioKg" name="precio_por_kg_excedente" value="0">
                                            <div class="form-text">Se cobra por cada kg que pase del peso incluido.</div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-4">
                                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check2 me-1"></i> Guardar tipo</button>
                                        <button type="button" class="btn btn-light" id="btnCancelarTipo" hidden>Cancelar</button>
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
                                            <th>Tipo de paquete</th>
                                            <th class="text-end">Precio</th>
                                            <th class="text-end">Peso incluido</th>
                                            <th class="text-end">Excedente</th>
                                            <th>Estado</th>
                                            <?php if ($esAdmin): ?><th class="text-end">Acciones</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!count($tipos)): ?>
                                            <tr><td colspan="6" class="text-center text-muted py-4">Todavía no hay tipos de paquete cargados.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($tipos as $t): ?>
                                            <tr class="<?php echo $t->estado ? '' : 'text-muted'; ?>">
                                                <td>
                                                    <div class="fw-semibold"><?php echo $e($t->nombre); ?></div>
                                                    <?php if ($t->descripcion): ?><div class="small text-muted"><?php echo $e($t->descripcion); ?></div><?php endif; ?>
                                                </td>
                                                <td class="text-end fw-semibold">Bs. <?php echo number_format((float) $t->precio_extra, 2); ?></td>
                                                <td class="text-end"><?php echo $t->peso_incluido_kg !== null ? number_format((float) $t->peso_incluido_kg, 2).' kg' : '<span class="text-muted">Sin límite</span>'; ?></td>
                                                <td class="text-end"><?php echo $t->peso_incluido_kg !== null ? 'Bs. '.number_format((float) $t->precio_por_kg_excedente, 2).'/kg' : '—'; ?></td>
                                                <td><?php echo $t->estado ? '<span class="badge bg-success-subtle text-success">Activo</span>' : '<span class="badge bg-secondary-subtle text-secondary">Inactivo</span>'; ?></td>
                                                <?php if ($esAdmin): ?>
                                                    <td class="text-end text-nowrap">
                                                        <button type="button" class="btn btn-sm btn-light text-primary" title="Editar"
                                                            onclick='editarTipo(<?php echo json_encode([
                                                                "id" => (int) $t->id, "nombre" => $t->nombre, "descripcion" => $t->descripcion,
                                                                "precio_extra" => $t->precio_extra, "peso_incluido_kg" => $t->peso_incluido_kg,
                                                                "precio_por_kg_excedente" => $t->precio_por_kg_excedente,
                                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>)'>
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <a class="btn btn-sm btn-light <?php echo $t->estado ? 'text-warning' : 'text-success'; ?>"
                                                            href="<?php echo URLROOT; ?>/admin/cambiar_estado_tipo_encomienda/<?php echo (int) $t->id; ?>"
                                                            title="<?php echo $t->estado ? 'Desactivar' : 'Activar'; ?>">
                                                            <i class="bi <?php echo $t->estado ? 'bi-pause-circle' : 'bi-play-circle'; ?>"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-light text-danger" title="Eliminar"
                                                            onclick="eliminarTipo(<?php echo (int) $t->id; ?>, '<?php echo $e($t->nombre); ?>')">
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
        const mensajes = { guardado: 'Tipo de paquete guardado', eliminado: 'Tipo de paquete eliminado' };
        if (params.get('msg')) {
            if (mensajes[params.get('msg')]) {
                Swal.fire({ icon: 'success', title: mensajes[params.get('msg')], timer: 1600, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'No se pudo completar', text: params.get('detalle') || 'Intente nuevamente.' });
            }
            history.replaceState(null, '', location.pathname);
        }

        const form = document.getElementById('formTipo');
        if (!form) return;

        window.editarTipo = function(t) {
            form.reset();
            document.getElementById('tipoId').value = t.id;
            document.getElementById('tipoNombre').value = t.nombre || '';
            document.getElementById('tipoDescripcion').value = t.descripcion || '';
            document.getElementById('tipoPrecioExtra').value = t.precio_extra ?? 0;
            document.getElementById('tipoPesoIncluido').value = t.peso_incluido_kg ?? '';
            document.getElementById('tipoPrecioKg').value = t.precio_por_kg_excedente ?? 0;
            document.getElementById('tituloTipo').innerHTML = '<i class="bi bi-pencil me-2"></i>Editar tipo de paquete';
            document.getElementById('btnCancelarTipo').hidden = false;
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        document.getElementById('btnCancelarTipo').addEventListener('click', () => {
            form.reset();
            document.getElementById('tipoId').value = '';
            document.getElementById('tituloTipo').innerHTML = '<i class="bi bi-tag me-2"></i>Nuevo tipo de paquete';
            document.getElementById('btnCancelarTipo').hidden = true;
        });

        window.eliminarTipo = function(id, nombre) {
            Swal.fire({
                icon: 'warning',
                title: 'Eliminar tipo de paquete',
                text: 'Se eliminará "' + nombre + '". Si ya se usó en alguna encomienda, se le va a pedir que lo desactive en vez de borrarlo.',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            }).then(r => { if (r.isConfirmed) location.href = '<?php echo URLROOT; ?>/admin/eliminar_tipo_encomienda/' + id; });
        };
    })();
</script>

@endsection
