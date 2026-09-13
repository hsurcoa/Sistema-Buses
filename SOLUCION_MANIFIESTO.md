# 📋 SOLUCIÓN COMPLETA - MODAL MANIFIESTO DE PASAJEROS

## 🎯 OBJETIVO CUMPLIDO

Se ha reestructurado completamente el Modal "Manifiesto de Pasajeros" según las especificaciones del desarrollador fullstack senior.

---

## 1️⃣ CÓDIGO PHP - Consulta SQL Optimizada

**Archivo:** `app/models/RutaModel.php`  
**Método:** `obtenerPasajerosPorViaje($viajeId)`

```php
public function obtenerPasajerosPorViaje($viajeId)
{
    $this->db->query("SELECT 
                        b.id,
                        b.numero_asiento,
                        c.numero_documento,
                        c.nombres,
                        c.apellidos,
                        CONCAT(c.nombres, ' ', c.apellidos) as nombre_pasajero,
                        c.celular as telefono,
                        b.estado, -- 'vendido' o 'reservado'
                        b.precio_final as precio,
                        r.destino, -- Destino de la RUTA
                        b.fecha_reserva as fecha_venta
                      FROM boletos b
                      INNER JOIN clientes c ON b.cliente_id = c.id
                      INNER JOIN viajes v ON b.viaje_id = v.id
                      INNER JOIN rutas r ON v.ruta_id = r.id
                      WHERE b.viaje_id = :viaje_id
                      ORDER BY b.numero_asiento ASC");

    $this->db->bind(':viaje_id', $viajeId);
    return $this->db->resultSet();
}
```

**✅ Características:**
- Usa INNER JOIN para garantizar datos completos
- Concatena nombres y apellidos
- Ordena por número de asiento
- Retorna todos los campos necesarios

---

## 2️⃣ CÓDIGO HTML - Modal Reestructurado

**Archivo:** `app/views/ventas/venta_pasajes.php`

Reemplazar desde la línea 1060 hasta 1120 con:

```html
<!-- MODAL MANIFIESTO DE PASAJEROS - PROFESIONAL -->
<div class="modal fade" id="modalManifiestoOficial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            
            <!-- Cabecera -->
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <div>
                    <h4 class="mb-0 font-weight-bold text-dark">Manifiesto de Pasajeros</h4>
                    <small class="text-muted">Sistema de Transporte Interprovincial</small>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="fas fa-print mr-1"></i> Imprimir
                </button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tabla_manifiesto">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center py-3" style="width: 80px;">Asiento</th>
                                <th class="py-3" style="width: 120px;">Documento</th>
                                <th class="py-3">Pasajero</th>
                                <th class="text-center py-3" style="width: 140px;">Estado</th>
                                <th class="py-3" style="width: 180px;">Destino</th>
                                <th class="text-end py-3" style="width: 120px;">Precio</th>
                                <th class="text-center py-3" style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_manifiesto" class="bg-white">
                            <!-- JavaScript genera las filas aquí -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer con Total -->
            <div class="modal-footer bg-light border-top py-3">
                <div class="w-100 d-flex justify-content-between align-items-center">
                    <span class="text-secondary font-weight-bold text-uppercase" style="letter-spacing: 1px;">
                        TOTAL RECAUDADO:
                    </span>
                    <span id="txtTotalRecaudado" class="text-success fw-bold fs-4">
                        0.00 Bs
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos Adicionales -->
<style>
    /* Badge circular para asiento */
    .badge-asiento-circular {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 35px;
        height: 35px;
        line-height: 20px;
        border-radius: 50%;
        background-color: #2c3e50;
        color: white;
        font-weight: bold;
        font-size: 0.9rem;
    }

    /* Badge de estado */
    .badge-estado {
        font-size: 0.85rem;
        padding: 6px 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    /* Precio destacado */
    .precio-destacado {
        color: #0d6efd;
        font-weight: bold;
        font-size: 1rem;
    }

    /* Hover en filas */
    #tabla_manifiesto tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s;
    }
</style>
```

---

## 3️⃣ CÓDIGO JAVASCRIPT - Lógica Completa

**Archivo:** `app/views/ventas/venta_pasajes.php`

Reemplazar la función `cargarTablaPasajeros` (línea 974-986) con:

```javascript
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
                            <span class="badge-asiento-circular">${p.numero_asiento}</span>
                        </td>
                        <td>${p.numero_documento}</td>
                        <td>${nombreCompleto}</td>
                        <td class="text-center">${badgeEstado}</td>
                        <td>
                            <i class="fas fa-map-marker-alt text-danger"></i> ${p.destino}
                        </td>
                        <td class="text-end precio-destacado">Bs. ${parseFloat(p.precio).toFixed(2)}</td>
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
            $.post(`${URLROOT}/ventas/cancelar_boleto/${id}`, function(res) {
                Swal.fire('Eliminado', 'El boleto ha sido eliminado', 'success');
                // Recargar tabla
                const viajeId = $('#select_viaje').val();
                cargarTablaPasajeros(viajeId);
                // Recargar diagrama
                cargarDiagramaBus(viajeId);
            }).fail(function() {
                Swal.fire('Error', 'No se pudo eliminar el boleto', 'error');
            });
        }
    });
}
```

---

## 📊 CARACTERÍSTICAS IMPLEMENTADAS

### ✅ Visual (Diseño Profesional)
- Modal centrado con `modal-dialog-centered`
- Scrollable con `modal-dialog-scrollable`
- Cabecera limpia con título y botón de imprimir
- Tabla con `table-light` en thead
- Badge circular oscuro para asientos (35px × 35px)
- Badges de estado con iconos y colores
- Icono rojo antes del destino
- Precio en azul y negrita
- Footer con total destacado en verde

### ✅ Datos (Base de Datos Real)
- Consulta SQL optimizada con INNER JOIN
- Usa tablas: `boletos`, `clientes`, `viajes`, `rutas`
- Campos exactos especificados
- Concatenación de nombres y apellidos
- Ordenamiento por número de asiento

### ✅ Lógica JavaScript
- Limpieza de tabla antes de cargar
- AJAX a endpoint correcto
- Cálculo de total solo de vendidos
- Formato de precio con 2 decimales
- Manejo de estados (vendido/reservado)
- Manejo de errores
- Loading spinner
- Mensaje cuando no hay datos

---

## 🚀 INSTRUCCIONES DE IMPLEMENTACIÓN

### Paso 1: Verificar el Modelo (Ya está correcto)
El modelo `RutaModel.php` ya tiene la consulta correcta.

### Paso 2: Reemplazar el HTML del Modal
1. Abrir: `app/views/ventas/venta_pasajes.php`
2. Buscar línea 1060: `<!-- MODAL MANIFIESTO DE PASAJEROS`
3. Reemplazar todo el bloque del modal con el código HTML proporcionado

### Paso 3: Reemplazar el JavaScript
1. Buscar la función `cargarTablaPasajeros` (línea 974)
2. Reemplazar con el código JavaScript proporcionado
3. Agregar la función `eliminarBoleto` después

### Paso 4: Probar
1. Reiniciar Apache (si es necesario)
2. Limpiar caché del navegador (Ctrl + F5)
3. Abrir el sistema
4. Click en "Manifiesto de Pasajeros"
5. Verificar que aparezca el diseño profesional

---

## 📝 NOTAS IMPORTANTES

- El modal usa Bootstrap 5 (clases como `text-end`, `fs-4`, `fw-bold`)
- Si usas Bootstrap 4, cambiar:
  - `text-end` → `text-right`
  - `fs-4` → `h4` o `display-4`
  - `fw-bold` → `font-weight-bold`
- El botón "Imprimir" usa `window.print()`
- Los botones de acción tienen tooltips con `title`
- El total se formatea con 2 decimales y sufijo "Bs"

---

## 🎯 RESULTADO FINAL

El modal ahora es:
- ✅ Idéntico a un reporte profesional
- ✅ Centrado en la pantalla
- ✅ Conectado a la base de datos real
- ✅ Con cálculo correcto de totales
- ✅ Con badges visuales según estado
- ✅ Con iconos y colores profesionales
- ✅ Con manejo de errores
- ✅ Con loading spinner

---

**Fecha:** 22/12/2025  
**Versión:** 3.0 Professional  
**Compatibilidad:** Bootstrap 5, PHP 8.x, MySQL 8.x
