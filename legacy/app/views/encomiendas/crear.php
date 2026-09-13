<?php require_once APPROOT . '/views/layouts/header.php'; ?>
<?php require_once APPROOT . '/views/layouts/sidebar.php'; ?>

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Nueva Encomienda</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <form action="<?php echo URLROOT; ?>/encomiendas/guardar" method="POST">

                <div class="row">
                    <!-- Columna Izquierda: Datos del Envío -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h3 class="card-title">1. Detalles del Viaje & Ruta</h3>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Ruta de Destino</label>
                                        <select class="form-select" id="ruta_id" name="ruta_id" required onchange="cargarDatosRuta()">
                                            <option value="">Seleccione Ruta...</option>
                                            <?php foreach ($data['rutas'] as $ruta): ?>
                                                <option value="<?php echo $ruta->id; ?>"><?php echo $ruta->origen . ' - ' . $ruta->destino; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Viaje Disponible (Salida)</label>
                                        <select class="form-select" name="viaje_id" id="viaje_id" required>
                                            <option value="">Primero seleccione ruta...</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- NUEVO: Selector de Parada -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-success">📍 Parada / Destino Exacto</label>
                                    <select class="form-select" name="parada_id" id="parada_id" required onchange="calcularTotal()">
                                        <option value="">Seleccione Ruta primero...</option>
                                    </select>
                                    <small class="text-muted">El precio base depende del destino seleccionado.</small>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h3 class="card-title">2. Datos de las Partes</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 border-end">
                                        <h5 class="mb-3">Remitente (Quien envía)</h5>
                                        <div class="mb-3">
                                            <label>DNI / RUC</label>
                                            <input type="text" name="remitente_dni" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Nombre Completo</label>
                                            <input type="text" name="remitente_nombre" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Destinatario (Quien recibe)</h5>
                                        <div class="mb-3">
                                            <label>DNI / RUC</label>
                                            <input type="text" name="destinatario_dni" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label>Nombre Completo</label>
                                            <input type="text" name="destinatario_nombre" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Teléfono (Para notificaciones)</label>
                                            <input type="text" name="destinatario_telefono" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Paquete y Pago -->
                    <div class="col-md-4">
                        <div class="card mb-4 border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h3 class="card-title"><i class="bi bi-box-seam"></i> 3. El Paquete</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Descripción del Contenido</label>
                                    <textarea name="descripcion" class="form-control" rows="2" required placeholder="Ej: Caja con repuestos..."></textarea>
                                </div>

                                <!-- NUEVO: Tipo de Paquete (Catálogo) -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">📦 Tipo de Paquete</label>
                                    <select class="form-select" name="tipo_paquete_id" id="tipo_paquete_id" required onchange="calcularTotal()">
                                        <option value="">Cargando catálogo...</option>
                                    </select>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label>Peso (KG)</label>
                                        <input type="number" step="0.1" name="peso" class="form-control" required value="1">
                                    </div>
                                    <div class="col-6">
                                        <label>Cuidado</label>
                                        <select name="tipo_carga" class="form-select">
                                            <option value="GENERAL">General</option>
                                            <option value="FRAGIL">Frágil</option>
                                            <option value="DOCUMENTOS">Documentos</option>
                                            <option value="ELECTRONICA">Electrónica</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label>Valor Declarado (Bs.)</label>
                                    <input type="number" step="0.01" name="valor_declarado" class="form-control" value="0">
                                    <small class="text-muted">Para cálculo de seguro (Opcional)</small>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <label>Clave de Retiro (Opcional)</label>
                                    <input type="password" name="clave_retiro" class="form-control" maxlength="4" placeholder="4 Dígitos">
                                </div>

                                <!-- TOTAL CALCULADO -->
                                <div class="alert alert-success text-center">
                                    <small>Total a Pagar:</small>
                                    <h2 class="fw-bold mb-0"><span id="precio_total_display">0.00</span> Bs</h2>
                                    <input type="hidden" name="total_calculado" id="total_calculado" value="0">
                                    <div id="detalle_calculo" style="font-size: 0.8rem;" class="mt-1 text-muted"></div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-cash-coin"></i> Registrar y Cobrar
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
    // URL Base para las peticiones API
    const API_URL = '<?php echo URLROOT; ?>';

    function cargarDatosRuta() {
        const rutaId = document.getElementById('ruta_id').value;
        const selectViaje = document.getElementById('viaje_id');
        const selectParada = document.getElementById('parada_id');
        const selectPaquete = document.getElementById('tipo_paquete_id');

        // Resetear selects
        selectViaje.innerHTML = '<option>Cargando viajes...</option>';
        selectParada.innerHTML = '<option>Cargando paradas...</option>';
        selectPaquete.innerHTML = '<option>Cargando...</option>';

        if (!rutaId) {
            selectViaje.innerHTML = '<option value="">Primero seleccione ruta...</option>';
            selectParada.innerHTML = '<option value="">Primero seleccione ruta...</option>';
            return;
        }

        // 1. Cargar Viajes (Lógica existente)
        fetch(`${API_URL}/encomiendas/obtener_viajes/${rutaId}`)
            .then(res => res.json())
            .then(data => {
                selectViaje.innerHTML = '<option value="">Seleccione Horario...</option>';
                if (data.length > 0) {
                    data.forEach(viaje => {
                        selectViaje.innerHTML += `<option value="${viaje.id}">${viaje.fecha_salida} ${viaje.hora_salida} - Bus: ${viaje.placa || 'Sin Asignar'}</option>`;
                    });
                } else {
                    selectViaje.innerHTML += '<option disabled>No hay viajes programados</option>';
                }
            });

        // 2. Cargar Paradas y Catálogo de Paquetes (NUEVA API)
        fetch(`${API_URL}/admin/obtener_info_ruta_json/${rutaId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Llenar Paradas
                    selectParada.innerHTML = '<option value="">Seleccione Destino...</option>';

                    if (data.paradas && data.paradas.length > 0) {
                        data.paradas.forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p.id;
                            opt.text = p.label_encomienda; // "Batallas - Base Bs 10.00"
                            opt.dataset.precio = p.precio_encomienda;
                            selectParada.appendChild(opt);
                        });
                    } else {
                        selectParada.innerHTML = '<option value="" disabled selected>⚠️ No hay paradas configuradas para esta ruta</option>';
                    }

                    // Llenar Tipos de Paquete
                    selectPaquete.innerHTML = '';
                    data.catalogos.paquetes.forEach(pq => {
                        const opt = document.createElement('option');
                        opt.value = pq.id;
                        opt.text = `${pq.nombre} (+${pq.precio_extra} Bs)`;
                        opt.dataset.extra = pq.precio_extra;
                        selectPaquete.appendChild(opt);
                    });

                    // Disparar cálculo inicial
                    calcularTotal();
                }
            })
            .catch(err => {
                console.error("Error cargando info ruta:", err);
                selectParada.innerHTML = '<option>Error de carga</option>';
            });
    }

    function calcularTotal() {
        // Obtener elementos
        const selParada = document.getElementById('parada_id');
        const selPaquete = document.getElementById('tipo_paquete_id');

        // Obtener valores seleccionados
        const optParada = selParada.options[selParada.selectedIndex];
        const optPaquete = selPaquete.options[selPaquete.selectedIndex];

        let total = 0;
        let detalle = '';

        if (optParada && optParada.value) {
            const precioBase = parseFloat(optParada.dataset.precio || 0);
            const precioExtra = parseFloat(optPaquete ? optPaquete.dataset.extra : 0);

            total = precioBase + precioExtra;

            const nombrePaquete = optPaquete ? optPaquete.text.split('(')[0] : '';
            detalle = `Base: ${precioBase.toFixed(2)} + Tipo: ${precioExtra.toFixed(2)}`;
        }

        // Mostrar en pantalla
        document.getElementById('precio_total_display').innerText = total.toFixed(2);

        // Actualizar input hidden para envío (opcional, mejor validar en backend)
        document.getElementById('total_calculado').value = total;
        document.getElementById('detalle_calculo').innerText = detalle;
    }
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>