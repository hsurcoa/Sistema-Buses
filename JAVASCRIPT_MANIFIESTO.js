/**
 * JAVASCRIPT COMPLETO - MODAL MANIFIESTO DE PASAJEROS
 * Reemplazar la función cargarTablaPasajeros (línea 974-986)
 * con este código completo
 */

/**
 * Cargar tabla de manifiesto de pasajeros
 * @param {number} id - ID del viaje
 */
function cargarTablaPasajeros(id) {
    // 1. Limpiar tabla
    $('#tbody_manifiesto').html('');
    
    // 2. Mostrar loading
    $('#tbody_manifiesto').html(`
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
            </td>
        </tr>
    `);
    
    // 3. AJAX a PHP
    $.getJSON(`${URLROOT}/ventas/listar_manifiesto/${id}`, function(res) {
        // Limpiar tabla nuevamente
        $('#tbody_manifiesto').html('');
        
        // Variable para calcular total
        let totalRecaudado = 0;
        
        // 4. Verificar si hay pasajeros
        if (res.pasajeros && res.pasajeros.length > 0) {
            res.pasajeros.forEach(function(p) {
                // Calcular total solo de vendidos
                if (p.estado === 'vendido') {
                    totalRecaudado += parseFloat(p.precio);
                }
                
                // Concatenar nombre completo
                const nombreCompleto = p.nombres + ' ' + p.apellidos;
                
                // Determinar badge de estado
                let badgeEstado = '';
                if (p.estado === 'vendido') {
                    badgeEstado = `<span class="badge bg-success badge-estado">
                                    <i class="fas fa-check"></i> PAGADO
                                   </span>`;
                } else if (p.estado === 'reservado') {
                    badgeEstado = `<span class="badge bg-warning text-dark badge-estado">
                                    <i class="fas fa-clock"></i> RESERVA
                                   </span>`;
                }
                
                // Construir fila
                const fila = `
                    <tr>
                        <td class="text-center">
                            <span class="badge rounded-circle bg-dark p-2" style="width:35px; height:35px; line-height:20px;">${p.numero_asiento}</span>
                        </td>
                        <td>${p.numero_documento}</td>
                        <td>${nombreCompleto}</td>
                        <td class="text-center">${badgeEstado}</td>
                        <td>
                            <i class="fas fa-map-marker-alt text-danger"></i> ${p.destino}
                        </td>
                        <td class="text-right text-primary font-weight-bold">Bs. ${parseFloat(p.precio).toFixed(2)}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" onclick="editFromList(${p.id})" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarBoleto(${p.id})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                
                // Agregar fila a la tabla
                $('#tbody_manifiesto').append(fila);
            });
        } else {
            // Sin pasajeros
            $('#tbody_manifiesto').html(`
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">No hay pasajeros registrados en este viaje</p>
                    </td>
                </tr>
            `);
        }
        
        // 5. Mostrar total recaudado formateado
        $('#txtTotalRecaudado').text(totalRecaudado.toFixed(2) + ' Bs');
        
    }).fail(function() {
        // Error en la petición
        $('#tbody_manifiesto').html(`
            <tr>
                <td colspan="7" class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p class="mb-0">Error al cargar los datos. Intente nuevamente.</p>
                </td>
            </tr>
        `);
    });
}

/**
 * Eliminar boleto
 * @param {number} id - ID del boleto
 */
function eliminarBoleto(id) {
    if (typeof Swal !== 'undefined') {
        // Usar SweetAlert2 si está disponible
        Swal.fire({
            title: '¿Eliminar boleto?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                ejecutarEliminacion(id);
            }
        });
    } else {
        // Usar confirm nativo
        if (confirm('¿Está seguro de eliminar este boleto?')) {
            ejecutarEliminacion(id);
        }
    }
}

/**
 * Ejecutar eliminación de boleto
 * @param {number} id - ID del boleto
 */
function ejecutarEliminacion(id) {
    $.post(`${URLROOT}/ventas/cancelar_boleto/${id}`, function(res) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Eliminado', 'El boleto ha sido eliminado', 'success');
        } else {
            toastr.success('Boleto eliminado correctamente');
        }
        
        // Recargar tabla
        const viajeId = $('#select_viaje').val();
        if (viajeId) {
            cargarTablaPasajeros(viajeId);
            // Recargar diagrama
            cargarDiagramaBus(viajeId);
        }
    }).fail(function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'No se pudo eliminar el boleto', 'error');
        } else {
            toastr.error('No se pudo eliminar el boleto');
        }
    });
}
