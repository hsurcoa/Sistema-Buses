<!-- 
    EJEMPLO: Integración del Mapa de Asientos en el Modal de Tipos de Buses
    Archivo: app/views/admin/tipos_buses_con_mapa.php
    
    Este archivo muestra cómo integrar el selector de asientos dinámico
    dentro del modal de configuración de tipos de buses.
-->

<!-- Reemplazar el contenedor del diagrama en tipos_buses.php (líneas 132-151) con esto: -->

<!-- Columna Derecha: Mapa de Asientos Interactivo -->
<div class="col-md-8">
    <div class="mb-3">
        <label class="form-label">
            <i class="bi bi-diagram-3-fill me-2"></i>Diseño de Asientos
        </label>

        <!-- Contenedor dinámico del mapa -->
        <div id="contenedorMapaAsientos" class="border rounded p-3 bg-light" style="min-height: 500px;">

            <!-- Tabs para Pisos -->
            <div class="mb-3" id="pisoTabsDiagrama" style="display: none;">
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-outline-primary active" id="tabDiagramaPiso1" onclick="cambiarPisoDiagrama(1)">
                        <i class="bi bi-1-circle-fill me-2"></i>Piso 1
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="tabDiagramaPiso2" onclick="cambiarPisoDiagrama(2)">
                        <i class="bi bi-2-circle-fill me-2"></i>Piso 2
                    </button>
                </div>
            </div>

            <!-- Mapa Interactivo -->
            <div id="mapaInteractivo">
                <!-- Aquí se cargará dinámicamente el mapa de asientos -->
                <div class="text-center py-5">
                    <i class="bi bi-bus-front" style="font-size: 4rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">Seleccione el número de pisos para ver el diagrama</p>
                </div>
            </div>

        </div>
    </div>

    <!-- Botón para generar asientos automáticamente -->
    <div class="d-grid gap-2">
        <button type="button" class="btn btn-outline-success" id="btnGenerarAsientos" onclick="generarAsientosAutomatico()" disabled>
            <i class="bi bi-magic me-2"></i>Generar Asientos Automáticamente
        </button>
    </div>
</div>

<script>
    /**
     * Variables globales para el diagrama
     */
    let tipoBusActual = {
        id: null,
        nombre: '',
        capacidad: 0,
        pisos: 1
    };

    /**
     * Actualizar el diagrama cuando cambia el número de pisos
     */
    function cambiarPiso() {
        const pisos = parseInt(document.getElementById('pisosTipoBus').value);
        const capacidad = parseInt(document.getElementById('capacidadTipoBus').value) || 0;
        const nombre = document.getElementById('nombreTipoBus').value || 'Bus';

        tipoBusActual.pisos = pisos;
        tipoBusActual.capacidad = capacidad;
        tipoBusActual.nombre = nombre;

        // Mostrar/ocultar tabs
        const tabsContainer = document.getElementById('pisoTabsDiagrama');
        if (pisos === 2) {
            tabsContainer.style.display = 'block';
        } else {
            tabsContainer.style.display = 'none';
        }

        // Cargar diagrama
        if (capacidad > 0) {
            cargarDiagramaInteractivo();
            document.getElementById('btnGenerarAsientos').disabled = false;
        }
    }

    /**
     * Cargar el diagrama interactivo
     */
    function cargarDiagramaInteractivo() {
        const contenedor = document.getElementById('mapaInteractivo');

        // Mostrar loading
        contenedor.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-3">Generando diagrama...</p>
        </div>
    `;

        // Simular carga (en producción, hacer AJAX al backend)
        setTimeout(() => {
            renderizarMapa();
        }, 500);
    }

    /**
     * Renderizar el mapa de asientos
     */
    function renderizarMapa() {
        const contenedor = document.getElementById('mapaInteractivo');
        const pisos = tipoBusActual.pisos;
        const capacidad = tipoBusActual.capacidad;

        // Calcular distribución de asientos
        let html = '';

        if (pisos === 1) {
            html = generarHTMLPiso1(capacidad);
        } else {
            html = `
            <div id="diagrama-piso-1" class="diagrama-piso">
                ${generarHTMLPiso1(Math.ceil(capacidad * 0.6))}
            </div>
            <div id="diagrama-piso-2" class="diagrama-piso" style="display: none;">
                ${generarHTMLPiso2(Math.floor(capacidad * 0.4))}
            </div>
        `;
        }

        contenedor.innerHTML = html;
    }

    /**
     * Generar HTML para Piso 1
     */
    function generarHTMLPiso1(capacidad) {
        return `
        <div class="bus-preview piso1">
            <!-- Cabina -->
            <div class="preview-cabin">
                <div class="preview-steering">
                    <i class="bi bi-circle-fill"></i>
                </div>
                <div class="preview-seat premium">1</div>
                <div class="preview-seat premium">2</div>
            </div>
            
            <!-- Grid de asientos -->
            <div class="preview-grid">
                ${generarAsientosGrid(3, capacidad, 1)}
            </div>
            
            <!-- Servicios -->
            <div class="preview-services">
                <div class="preview-service">BAÑO</div>
                <div class="preview-service">ESCALERA</div>
                <div class="preview-service">DORMITORIO</div>
            </div>
        </div>
    `;
    }

    /**
     * Generar HTML para Piso 2
     */
    function generarHTMLPiso2(capacidad) {
        const inicio = Math.ceil(tipoBusActual.capacidad * 0.6) + 1;
        return `
        <div class="bus-preview piso2">
            <div class="preview-floor-badge">
                <i class="bi bi-2-circle-fill"></i> PISO 2
            </div>
            <div class="preview-grid">
                ${generarAsientosGrid(inicio, inicio + capacidad - 1, 2)}
            </div>
        </div>
    `;
    }

    /**
     * Generar grid de asientos
     */
    function generarAsientosGrid(inicio, fin, piso) {
        let html = '';
        let asientoNum = inicio;
        const filas = Math.ceil((fin - inicio + 1) / 4);

        for (let fila = 0; fila < filas; fila++) {
            html += '<div class="preview-row">';

            for (let col = 0; col < 4; col++) {
                if (col === 2) {
                    html += '<div class="preview-aisle"></div>';
                }

                if (asientoNum <= fin) {
                    html += `<div class="preview-seat" data-numero="${asientoNum}" data-piso="${piso}">${asientoNum}</div>`;
                    asientoNum++;
                }
            }

            html += '</div>';
        }

        return html;
    }

    /**
     * Cambiar entre pisos en el diagrama
     */
    function cambiarPisoDiagrama(numeroPiso) {
        document.getElementById('diagrama-piso-1').style.display = numeroPiso === 1 ? 'block' : 'none';
        document.getElementById('diagrama-piso-2').style.display = numeroPiso === 2 ? 'block' : 'none';

        document.getElementById('tabDiagramaPiso1').classList.toggle('active', numeroPiso === 1);
        document.getElementById('tabDiagramaPiso2').classList.toggle('active', numeroPiso === 2);
    }

    /**
     * Generar asientos automáticamente en la BD
     */
    function generarAsientosAutomatico() {
        const tipoBusId = document.getElementById('tipoBusId').value;

        if (!tipoBusId) {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'Primero debe guardar el tipo de bus'
            });
            return;
        }

        Swal.fire({
            title: '¿Generar asientos?',
            text: 'Esto creará automáticamente los asientos para este tipo de bus',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Llamar al backend
                fetch(`<?php echo URLROOT; ?>/admin/generar_asientos/${tipoBusId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Asientos Generados',
                                text: `Se generaron ${data.total} asientos correctamente`,
                                confirmButtonText: 'Ver Mapa'
                            }).then(() => {
                                window.location.href = `<?php echo URLROOT; ?>/admin/mapa_asientos/${tipoBusId}`;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al generar asientos: ' + error
                        });
                    });
            }
        });
    }

    /**
     * Listeners para actualizar el diagrama
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Actualizar diagrama cuando cambia la capacidad
        const capacidadInput = document.getElementById('capacidadTipoBus');
        if (capacidadInput) {
            capacidadInput.addEventListener('change', cambiarPiso);
        }

        // Actualizar diagrama cuando cambia el nombre
        const nombreInput = document.getElementById('nombreTipoBus');
        if (nombreInput) {
            nombreInput.addEventListener('blur', cambiarPiso);
        }
    });
</script>

<style>
    /* ========================================
   ESTILOS PARA EL PREVIEW DEL BUS
   ======================================== */

    .bus-preview {
        background: linear-gradient(180deg, #e8e8e8 0%, #f5f5f5 100%);
        border: 4px solid #5a5a5a;
        border-radius: 2rem;
        padding: 1.5rem;
        max-width: 500px;
        margin: 0 auto;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .preview-cabin {
        background: linear-gradient(180deg, #2c2c2c 0%, #1a1a1a 100%);
        border-radius: 1.5rem 1.5rem 0 0;
        padding: 1rem;
        margin: -1.5rem -1.5rem 1rem -1.5rem;
        display: grid;
        grid-template-columns: 60px 1fr 1fr;
        gap: 0.5rem;
        align-items: center;
    }

    .preview-steering {
        display: flex;
        justify-content: center;
        align-items: center;
        color: #888;
        font-size: 1.5rem;
    }

    .preview-grid {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .preview-row {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        align-items: center;
    }

    .preview-seat {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: 2px solid #1d4ed8;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.75rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .preview-seat.premium {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border-color: #b45309;
    }

    .preview-aisle {
        width: 30px;
        height: 40px;
        border-left: 1px dashed rgba(0, 0, 0, 0.2);
        border-right: 1px dashed rgba(0, 0, 0, 0.2);
    }

    .preview-services {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 2px dashed rgba(0, 0, 0, 0.1);
    }

    .preview-service {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        border: 2px solid #334155;
        border-radius: 0.5rem;
        padding: 0.5rem;
        text-align: center;
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
    }

    .preview-floor-badge {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 1rem;
        text-align: center;
        font-weight: 700;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .diagrama-piso {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>