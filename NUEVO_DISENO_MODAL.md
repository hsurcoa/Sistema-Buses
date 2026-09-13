# 🎨 NUEVO DISEÑO - MODAL DE VENTA DE PASAJES

## Cambios Implementados según Imagen de Referencia

### ✅ CSS Actualizado
- Colores cyan (#00bcd4) para el banner y elementos principales
- Borde azul (#5b9bd5) alrededor del diagrama del bus
- Botón amarillo (#ffc107) para RESERVAR
- Botón verde (#28a745) para COBRAR Y EMITIR
- Leyenda horizontal con círculos de colores
- Display grande del número de asiento (3.5rem)

### ✅ HTML Reestructurado
1. **Banner Cyan Superior**: Muestra LA PAZ → COPACABANA con fecha y hora
2. **Leyenda Horizontal**: Libre, Reservado, Vendido, No Disponible
3. **Diagrama del Bus**: Con borde azul redondeado de 3px
4. **Panel de Control**: Diseño limpio con secciones bien definidas
5. **Botones de Acción**: Amarillo (RESERVAR) y Verde (COBRAR Y EMITIR)

## 📝 Instrucciones para Aplicar los Cambios

### Opción 1: Reemplazo Manual (Recomendado para Firefox)

1. **Abrir el archivo:**
   ```
   c:\xampp\htdocs\venta-pasajes\app\views\ventas\venta_pasajes.php
   ```

2. **Buscar la línea 435** que dice:
   ```html
   <!-- Banner -->
   ```

3. **Eliminar desde la línea 435 hasta la línea 436** (el div del banner antiguo)

4. **Buscar la línea 440** que dice:
   ```html
   <!-- MAPA -->
   ```

5. **Reemplazar TODO el bloque desde la línea 440 hasta la línea 541** con el nuevo código que está más abajo

### Opción 2: Usar el Archivo de Respaldo

He preparado los cambios necesarios. Para aplicarlos manualmente:

1. Los estilos CSS ya están actualizados ✅
2. Solo falta actualizar el HTML del área de mapa

## 🔧 Código HTML Nuevo para el Área de Mapa

Reemplaza desde `<!-- MAPA -->` hasta el cierre de ese `</div>` con:

```html
<!-- MAPA DE ASIENTOS - Diseño según imagen de referencia -->
<div class="tab-pane fade show active" id="tab-mapa">
    
    <!-- Banner Cyan Superior con Ruta -->
    <div id="route-banner" class="route-cyan-bar" style="display: none;">
        <span id="route-origin">LA PAZ</span>
        <i class="fas fa-arrow-right"></i>
        <span id="route-destination">COPACABANA</span>
        <i class="far fa-calendar-alt"></i>
        <span id="route-date">2025-12-21 07:40:00</span>
        <i class="far fa-clock"></i>
        <span id="route-time">07:40:00</span>
    </div>

    <!-- Leyenda Horizontal -->
    <div class="legend-bar">
        <div class="legend-item">
            <span class="dot-circle" style="background:#e0e0e0;"></span> Libre (Blanco)
        </div>
        <div class="legend-item">
            <span class="dot-circle" style="background:#ff9800;"></span> Reservado (Sin pagar)
        </div>
        <div class="legend-item">
            <span class="dot-circle" style="background:#4caf50;"></span> Vendido (Pagado)
        </div>
        <div class="legend-item">
            <span class="dot-circle" style="background:#f44336;"></span> No Disponible
        </div>
    </div>

    <div class="row">
        <!-- Columna Izquierda: Diagrama del Bus -->
        <div class="col-lg-8 mb-3">
            <div class="bus-container-wrapper">
                <!-- Tabs de Pisos (si hay 2 pisos) -->
                <div id="floor-tabs-container" class="floor-tabs-container" style="display: none;">
                    <div class="floor-tabs">
                        <div class="floor-tab active" data-floor="1" onclick="cambiarPiso(1)">
                            <i class="fas fa-layer-group"></i> Piso 1
                        </div>
                        <div class="floor-tab" data-floor="2" onclick="cambiarPiso(2)">
                            <i class="fas fa-layer-group"></i> Piso 2
                        </div>
                    </div>
                </div>

                <!-- Diagrama del Bus con Borde Azul -->
                <div class="bus-diagram-border">
                    <div id="contenedor-bus" class="bus-viewport">
                        <div class="text-center text-muted align-self-center">
                            <i class="fas fa-search-location fa-3x mb-3 opacity-25"></i>
                            <p class="font-weight-600">Seleccione una ruta para cargar el bus</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Panel de Control -->
        <div class="col-lg-4">
            <div class="control-panel-card">
                <!-- Header del Panel -->
                <div class="panel-header">
                    <h5>
                        <i class="fas fa-cog"></i> Panel de Control
                    </h5>
                </div>

                <!-- Body del Panel -->
                <div class="panel-body">
                    <form id="formVenta">
                        <input type="hidden" id="inputBoletoId">
                        <input type="hidden" id="inputAsiento">

                        <!-- Sección de Ruta de Viaje -->
                        <div class="route-info-section">
                            <label>Ruta de Viaje</label>
                            <div class="route-text">
                                <i class="fas fa-bus"></i>
                                <span id="selected-route-display">LA PAZ - COPACABANA | 07:40:00</span>
                            </div>
                        </div>

                        <!-- Selector de Ruta (Oculto visualmente pero funcional) -->
                        <select class="form-control-custom" id="select_viaje" style="display: none;">
                            <option value="">-- Seleccionar --</option>
                            <?php if (isset($data['viajesProgramados'])): ?>
                                <?php foreach ($data['viajesProgramados'] as $viaje): ?>
                                    <option value="<?php echo $viaje->id; ?>"
                                        data-precio="<?php echo $viaje->precio_base ?? 0; ?>"
                                        data-origen="<?php echo htmlspecialchars($viaje->origen ?? ''); ?>"
                                        data-destino="<?php echo htmlspecialchars($viaje->destino ?? ''); ?>"
                                        data-hora="<?php echo $viaje->hora_salida; ?>">
                                        <?php echo htmlspecialchars($viaje->origen . ' - ' . $viaje->destino . ' | ' . $viaje->hora_salida); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <!-- Display Grande de Asiento -->
                        <div class="seat-display-large">
                            <label>ASIENTO N°</label>
                            <div class="seat-number-display" id="displayAsiento">11</div>
                        </div>

                        <!-- Campo DNI / CI / Pasaporte -->
                        <div class="form-group-custom">
                            <div class="input-with-icon">
                                <input type="text" class="form-control-custom" id="inputDNI" placeholder="DNI / CI / Pasaporte">
                                <i class="fas fa-search search-icon" onclick="buscarCliente()"></i>
                            </div>
                        </div>

                        <!-- Campos de Nombre en dos columnas -->
                        <div class="name-fields-row">
                            <div class="form-group-custom">
                                <input type="text" class="form-control-custom" id="inputNombres" placeholder="Esteban">
                            </div>
                            <div class="form-group-custom">
                                <input type="text" class="form-control-custom" id="inputApellidos" placeholder="Arce">
                            </div>
                        </div>

                        <!-- Campo de Precio (Oculto) -->
                        <input type="hidden" id="inputPrecio" value="0.00">
                        <input type="hidden" id="precioBase">

                        <!-- Botones de Acción -->
                        <div class="action-buttons">
                            <button type="button" id="btnReservar" class="btn-reserve-yellow" onclick="procesarVenta(2)">
                                <i class="far fa-bookmark"></i> RESERVAR
                            </button>
                            <button type="button" id="btnEmitir" class="btn-sell-green" onclick="procesarVenta(1)">
                                <i class="fas fa-dollar-sign"></i> COBRAR Y EMITIR
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
```

## 🎯 Características del Nuevo Diseño

### Banner Cyan Superior
- Fondo degradado cyan (#00bcd4 → #00acc1)
- Muestra: Origen → Destino | Fecha | Hora
- Iconos de FontAwesome para mejor visualización

### Leyenda Horizontal
- Fondo gris claro (#f8f9fa)
- Círculos de colores para cada estado
- Espaciado uniforme y centrado

### Diagrama del Bus
- Contenedor blanco con sombra sutil
- Borde azul de 3px (#5b9bd5)
- Bordes redondeados de 12px
- Padding interno de 30px

### Panel de Control
- Header gris con icono de engranaje
- Sección de ruta con borde izquierdo cyan
- Display de asiento grande (3.5rem)
- Campos de entrada limpios
- Botones amarillo y verde con iconos

### Botones de Acción
- **RESERVAR**: Amarillo (#ffc107) con icono de bookmark
- **COBRAR Y EMITIR**: Verde (#28a745) con icono de dólar
- Efectos hover con elevación
- Sombras suaves para profundidad

## 🚀 Próximos Pasos

1. ✅ CSS ya está actualizado
2. ⏳ Aplicar cambios HTML manualmente
3. ⏳ Probar en Firefox
4. ⏳ Limpiar caché del navegador (Ctrl + F5)
5. ⏳ Verificar que todo funcione correctamente

## 📌 Notas Importantes

- El selector de ruta está oculto (`display: none`) pero sigue funcional
- La información de la ruta se muestra en el recuadro gris con borde cyan
- El número de asiento por defecto es "11" (como en la imagen)
- Los placeholders de nombre son "Esteban" y "Arce" (como en la imagen)

---

**Generado:** 22/12/2025  
**Compatibilidad:** Firefox, Chrome, Edge  
**Versión:** 2.0
