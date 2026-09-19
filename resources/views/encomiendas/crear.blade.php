@extends('layouts.app')

@section('content')

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <h3 class="mb-0"><i class="bi bi-box-seam-fill me-2"></i>Nueva Encomienda</h3>
            <div class="text-muted small">Elegí la ruta y el destino exacto: el precio se arma solo, según el destino, el tipo de paquete y el peso.</div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form action="<?php echo URLROOT; ?>/encomiendas/guardar" method="POST" id="formEncomienda">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="row g-4">
                    <!-- Columna Izquierda: Ruta y Partes -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0 rounded-4 mb-4">
                            <div class="card-header bg-white py-3 d-flex align-items-center">
                                <span class="badge rounded-pill bg-primary me-2">1</span>
                                <h5 class="card-title mb-0">Viaje y destino</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Ruta</label>
                                        <select class="form-select" id="ruta_id" name="ruta_id" required onchange="cargarDatosRuta()">
                                            <option value="">Seleccione ruta...</option>
                                            <?php foreach ($data['rutas'] as $ruta): ?>
                                                <option value="<?php echo $ruta->id; ?>"><?php echo htmlspecialchars($ruta->origen . ' - ' . $ruta->destino); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Viaje (salida)</label>
                                        <select class="form-select" name="viaje_id" id="viaje_id" required onchange="mostrarInfoVehiculo()">
                                            <option value="">Primero seleccione ruta...</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Info del bus/minibus asignado a ese viaje: se llena al elegir el horario -->
                                <div id="infoVehiculo" class="d-none mt-3 p-2 px-3 bg-light border rounded-3 small d-flex align-items-center gap-3"></div>

                                <div class="mt-3">
                                    <label class="form-label fw-semibold text-success"><i class="bi bi-geo-alt-fill me-1"></i>Parada / destino exacto</label>
                                    <select class="form-select" name="parada_id" id="parada_id" required onchange="cotizar()">
                                        <option value="">Seleccione ruta primero...</option>
                                    </select>
                                    <div class="form-text">El precio base del envío depende de este destino.</div>
                                    <div id="avisoParadas" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0 rounded-4">
                            <div class="card-header bg-white py-3 d-flex align-items-center">
                                <span class="badge rounded-pill bg-primary me-2">2</span>
                                <h5 class="card-title mb-0">Remitente y destinatario</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 border-end">
                                        <h6 class="text-muted text-uppercase small fw-bold mb-3">Remitente (quien envía)</h6>
                                        <div class="mb-3">
                                            <label class="form-label">CI / RUC</label>
                                            <input type="text" name="remitente_dni" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Nombre completo</label>
                                            <input type="text" name="remitente_nombre" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted text-uppercase small fw-bold mb-3">Destinatario (quien recibe)</h6>
                                        <div class="mb-3">
                                            <label class="form-label">CI / RUC</label>
                                            <input type="text" name="destinatario_dni" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Nombre completo</label>
                                            <input type="text" name="destinatario_nombre" class="form-control" required>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label">Teléfono (para notificaciones)</label>
                                            <input type="text" name="destinatario_telefono" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Paquete y Pago (fijo al hacer scroll) -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0 rounded-4" style="position: sticky; top: 1rem;">
                            <div class="card-header bg-white py-3 d-flex align-items-center">
                                <span class="badge rounded-pill bg-primary me-2">3</span>
                                <h5 class="card-title mb-0">El paquete</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Descripción del contenido</label>
                                    <textarea name="descripcion" class="form-control" rows="2" required placeholder="Ej: Caja con repuestos..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold"><i class="bi bi-tags-fill me-1 text-warning"></i>Tipo de paquete</label>
                                    <select class="form-select" name="tipo_paquete_id" id="tipo_paquete_id" required onchange="cotizar()">
                                        <option value="">Cargando arancel...</option>
                                    </select>
                                    <div class="form-text">
                                        Los precios y pesos incluidos se administran en
                                        <a href="<?php echo URLROOT; ?>/admin/tipos_encomienda" target="_blank">Arancel de Encomiendas</a>.
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Peso (kg)</label>
                                        <input type="number" step="0.1" min="0.1" name="peso" id="peso" class="form-control" required value="1" oninput="cotizarConPausa()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Cuidado</label>
                                        <select name="tipo_carga" class="form-select">
                                            <option value="GENERAL">General</option>
                                            <option value="FRAGIL">Frágil</option>
                                            <option value="DOCUMENTOS">Documentos</option>
                                            <option value="ELECTRONICA">Electrónica</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Valor declarado (Bs.)</label>
                                    <input type="number" step="0.01" min="0" name="valor_declarado" class="form-control" value="0">
                                    <small class="text-muted">Para cálculo de seguro (opcional)</small>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <label class="form-label">Clave de retiro (opcional)</label>
                                    <input type="password" name="clave_retiro" class="form-control" maxlength="4" placeholder="4 dígitos">
                                    <small class="text-muted">Si la carga, va a hacer falta para marcar la encomienda como entregada.</small>
                                </div>

                                <!-- TOTAL: se calcula en el servidor (mismo motor de precios que usa Ventas) -->
                                <div class="alert alert-success text-center mb-3">
                                    <small class="text-uppercase fw-bold">Total a pagar</small>
                                    <h2 class="fw-bold mb-0"><span id="precio_total_display">0.00</span> Bs</h2>
                                    <div id="detalle_calculo" style="font-size: 0.78rem;" class="mt-1 text-muted"></div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" id="btnGuardarEncomienda" class="btn btn-success btn-lg">
                                        <i class="bi bi-cash-coin"></i> Registrar y cobrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    const API_URL = '<?php echo URLROOT; ?>';
    const CSRF_TOKEN = '<?php echo csrf_token(); ?>';
    let catalogoParadas = [];
    let catalogoViajes = [];

    function cargarDatosRuta() {
        const rutaId = document.getElementById('ruta_id').value;
        const selectViaje = document.getElementById('viaje_id');
        const selectParada = document.getElementById('parada_id');
        const selectPaquete = document.getElementById('tipo_paquete_id');
        const avisoParadas = document.getElementById('avisoParadas');
        const btnGuardar = document.getElementById('btnGuardarEncomienda');

        selectViaje.innerHTML = '<option>Cargando viajes...</option>';
        selectParada.innerHTML = '<option>Cargando paradas...</option>';
        selectPaquete.innerHTML = '<option>Cargando...</option>';
        avisoParadas.innerHTML = '';
        document.getElementById('infoVehiculo').classList.add('d-none');
        if (btnGuardar) btnGuardar.disabled = false;
        limpiarPrecio();

        if (!rutaId) {
            selectViaje.innerHTML = '<option value="">Primero seleccione ruta...</option>';
            selectParada.innerHTML = '<option value="">Primero seleccione ruta...</option>';
            return;
        }

        // 1. Viajes de la ruta (con el bus/minibus asignado a cada uno)
        fetch(`${API_URL}/encomiendas/obtener_viajes/${rutaId}`)
            .then(res => res.json())
            .then(data => {
                catalogoViajes = data;
                selectViaje.innerHTML = '<option value="">Seleccione horario...</option>';
                if (data.length > 0) {
                    data.forEach(viaje => {
                        const vehiculo = viaje.placa ? `${viaje.placa}${viaje.tipo_bus ? ' · ' + viaje.tipo_bus : ''}` : 'Bus sin asignar';
                        selectViaje.innerHTML += `<option value="${viaje.id}">${viaje.fecha_salida} ${(viaje.hora_salida || '').substring(0, 5)} — ${vehiculo}</option>`;
                    });
                } else {
                    selectViaje.innerHTML += '<option value="" disabled>No hay viajes programados a futuro para esta ruta</option>';
                    avisoParadas.innerHTML += `<div class="alert alert-warning small py-2 px-3 mb-2">
                        No hay viajes futuros en esta ruta. <a href="${API_URL}/ventas/crear_ruta" class="alert-link">Programar un viaje →</a>
                    </div>`;
                }
            });

        // 2. Paradas (destinos con precio) y catálogo de tipos de paquete (arancel)
        fetch(`${API_URL}/admin/obtener_info_ruta_json/${rutaId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    catalogoParadas = data.paradas || [];
                    selectParada.innerHTML = '<option value="">Seleccione destino...</option>';

                    if (catalogoParadas.length > 0) {
                        catalogoParadas.forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p.id;
                            opt.text = p.label_encomienda; // "Batallas - Base Bs 10.00"
                            selectParada.appendChild(opt);
                        });
                    } else {
                        selectParada.innerHTML = '<option value="" disabled selected>⚠️ No hay paradas configuradas para esta ruta</option>';
                        avisoParadas.innerHTML += `<div class="alert alert-danger small py-2 px-3 mb-0">
                            Esta ruta todavía no tiene destinos con precio de encomienda configurados.
                            <a href="${API_URL}/admin/rutas_paradas?ruta=${rutaId}" class="alert-link">Configurar paradas de esta ruta →</a>
                        </div>`;
                        if (btnGuardar) btnGuardar.disabled = true;
                    }

                    selectPaquete.innerHTML = '';
                    data.catalogos.paquetes.forEach(pq => {
                        const opt = document.createElement('option');
                        opt.value = pq.id;
                        opt.text = `${pq.nombre} (+${parseFloat(pq.precio_extra).toFixed(2)} Bs)`;
                        selectPaquete.appendChild(opt);
                    });
                    if (!data.catalogos.paquetes.length) {
                        avisoParadas.innerHTML += `<div class="alert alert-warning small py-2 px-3 mb-0 mt-2">
                            No hay tipos de paquete activos en el arancel.
                            <a href="${API_URL}/admin/tipos_encomienda" class="alert-link">Cargar el arancel →</a>
                        </div>`;
                    }

                    cotizar();
                }
            })
            .catch(err => {
                console.error("Error cargando info ruta:", err);
                selectParada.innerHTML = '<option>Error de carga</option>';
            });
    }

    function mostrarInfoVehiculo() {
        const viajeId = document.getElementById('viaje_id').value;
        const caja = document.getElementById('infoVehiculo');
        const viaje = catalogoViajes.find(v => String(v.id) === String(viajeId));

        if (!viaje) { caja.classList.add('d-none'); return; }

        caja.classList.remove('d-none');
        if (viaje.placa) {
            caja.innerHTML = `
                <i class="bi bi-bus-front-fill text-primary fs-5"></i>
                <div>
                    <div class="fw-semibold">${viaje.placa}${viaje.tipo_bus ? ' · ' + viaje.tipo_bus : ''}</div>
                    <div class="text-muted">${viaje.capacidad_pasajeros ? viaje.capacidad_pasajeros + ' asientos de pasajeros' : 'Este es el vehículo que hace este viaje'}</div>
                </div>`;
        } else {
            caja.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                <div class="text-warning">Este viaje todavía no tiene bus asignado. Se puede registrar igual, pero avise a Administrador.</div>`;
        }
    }

    // --- Cotización: siempre contra el servidor, mismo motor de precios que Ventas ---
    let timerCotizar = null;
    function cotizarConPausa() {
        clearTimeout(timerCotizar);
        timerCotizar = setTimeout(cotizar, 350);
    }

    function limpiarPrecio() {
        document.getElementById('precio_total_display').innerText = '0.00';
        document.getElementById('detalle_calculo').innerText = '';
    }

    function cotizar() {
        const paradaId = document.getElementById('parada_id').value;
        const paqueteId = document.getElementById('tipo_paquete_id').value;
        const peso = parseFloat(document.getElementById('peso').value || 0);

        if (!paradaId || !paqueteId) { limpiarPrecio(); return; }

        fetch(`${API_URL}/admin/cotizar_envio`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ csrf_token: CSRF_TOKEN, parada_id: paradaId, tipo: 'encomienda', paquete_id: paqueteId, peso: peso }),
        })
            .then(res => res.json())
            .then(res => {
                if (!res.status) { limpiarPrecio(); return; }
                document.getElementById('precio_total_display').innerText = parseFloat(res.precio_total).toFixed(2);
                document.getElementById('detalle_calculo').innerText = res.desglose;
            })
            .catch(() => limpiarPrecio());
    }
</script>

@endsection
