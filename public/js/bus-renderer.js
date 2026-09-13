/**
 * BUS RENDERER - Componente Unificado para Renderizado de Buses
 * Usado en: Sistema de Ventas y Configuración de Tipos de Buses
 * Tecnología: Fabric.js Canvas
 * 
 * @author Sistema de Transporte
 * @version 2.0
 */

class BusRenderer {
    constructor(canvasId, options = {}) {
        this.canvasId = canvasId;
        this.canvas = null;
        this.currentFloor = 1;
        this.totalFloors = 1;
        this.onSeatClick = options.onSeatClick || null;
        this.readOnly = options.readOnly || false; // Para preview sin interacción
        
        // Configuración de diseño
        this.SEAT_WIDTH = 38;
        this.SEAT_HEIGHT = 38;
        this.SEAT_GAP = 8;
        this.AISLE_GAP = 30;
    }

    /**
     * Inicializar canvas
     */
    initCanvas(width, height) {
        if (this.canvas) {
            this.canvas.dispose();
        }

        this.canvas = new fabric.Canvas(this.canvasId, {
            backgroundColor: 'transparent',
            selection: false,
            width: width,
            height: height
        });

        return this.canvas;
    }

    /**
     * Renderizar bus completo
     * @param {Object} data - Datos del bus (capacidad, pisos, asientos ocupados, etc.)
     * @param {Number} floor - Piso a renderizar (1 o 2)
     */
    renderBus(data, floor = 1) {
        console.log("🚌 BusRenderer.renderBus() - Iniciando renderizado");
        console.log("  📦 Data recibida:", data);
        console.log("  🏢 Piso a renderizar:", floor);

        this.currentFloor = floor;

        // Extraer configuración
        const config = data.layout_config || {};
        const TOTAL_ASIENTOS = parseInt(data.asientos_total || data.capacidad || 40);
        const PISOS = parseInt(config.pisos || data.pisos || 1);
        const COLUMNAS = parseInt(config.columnas || 4);
        const POS_PASILLO = parseInt(config.posicion_pasillo || 2);

        this.totalFloors = PISOS;

        console.log("📊 BusRenderer - Configuración extraída:");
        console.log("  - config object:", config);
        console.log("  - TOTAL_ASIENTOS:", TOTAL_ASIENTOS, "(de:", data.asientos_total || data.capacidad || "default", ")");
        console.log("  - PISOS:", PISOS, "(de:", config.pisos || data.pisos || "default", ")");
        console.log("  - COLUMNAS:", COLUMNAS, "(de:", config.columnas || "default", ")");
        console.log("  - POS_PASILLO:", POS_PASILLO, "(de:", config.posicion_pasillo || "default", ")");
        console.log("  - distribucion:", config.distribucion);

        // Calcular asientos a renderizar
        const seatsToRender = this.calculateSeatsForFloor(TOTAL_ASIENTOS, PISOS, floor, config);
        console.log("🎯 Asientos calculados para piso", floor, ":", seatsToRender.length, "asientos");
        console.log("  - Rango:", seatsToRender[0], "-", seatsToRender[seatsToRender.length - 1]);

        // Limpiar canvas
        if (this.canvas) {
            this.canvas.clear();
        }

        // Calcular dimensiones
        const canvasWidth = this.canvas.width;
        const startY = floor === 1 ? 50 : 20;

        // Dibujar cabina (solo piso 1)
        if (floor === 1) {
            this.drawCabin(canvasWidth, COLUMNAS, startY);
        }

        // Dibujar asientos
        const occupied = data.asientos_ocupados || [];
        console.log("🔒 Asientos ocupados:", occupied.length);
        let finalHeight = this.drawSeats(seatsToRender, occupied, canvasWidth, startY, COLUMNAS, POS_PASILLO);

        // Ajustar altura del canvas
        this.canvas.setHeight(finalHeight);
        this.canvas.renderAll();

        console.log("✅ BusRenderer.renderBus() - Renderizado completado");
        return this.canvas;
    }

    /**
     * Calcular qué asientos mostrar según el piso
     */
    calculateSeatsForFloor(totalSeats, floors, currentFloor, config) {
        if (floors === 1) {
            // Un solo piso: todos los asientos
            return Array.from({ length: totalSeats }, (_, i) => i + 1);
        }

        // Dos pisos: distribuir
        const dist = config.distribucion || {};
        
        if (currentFloor === 1) {
            // Piso 1: calcular desde configuración o 60%
            const piso1Seats = (dist.piso1?.normales || 0) + (dist.piso1?.premium || 0) || Math.ceil(totalSeats * 0.6);
            return Array.from({ length: piso1Seats }, (_, i) => i + 1);
        } else {
            // Piso 2: asientos restantes
            const piso1Seats = (dist.piso1?.normales || 0) + (dist.piso1?.premium || 0) || Math.ceil(totalSeats * 0.6);
            const piso2Seats = totalSeats - piso1Seats;
            return Array.from({ length: piso2Seats }, (_, i) => piso1Seats + i + 1);
        }
    }

    /**
     * Dibujar cabina del conductor
     */
    drawCabin(canvasWidth, columns, startY) {
        const panelW = (columns * 50) + 80;
        const centerX = canvasWidth / 2;
        const startX = centerX - (panelW / 2);

        // Panel de cabina
        const cabina = new fabric.Rect({
            left: startX,
            top: 10,
            width: panelW,
            height: startY - 15,
            fill: '#e2e8f0',
            stroke: '#cbd5e1',
            strokeWidth: 1,
            rx: 8,
            ry: 8,
            selectable: false
        });

        // Texto "CABINA"
        const txtCabina = new fabric.Text("CABINA", {
            fontSize: 10,
            fill: '#64748b',
            fontWeight: '700',
            left: centerX,
            top: 22,
            originX: 'center',
            selectable: false
        });

        // Volante
        const wheel = new fabric.Circle({
            radius: 10,
            fill: 'transparent',
            stroke: '#94a3b8',
            strokeWidth: 2,
            left: startX + panelW - 35,
            top: 20,
            selectable: false
        });

        this.canvas.add(cabina, txtCabina, wheel);
    }

    /**
     * Dibujar todos los asientos
     */
    drawSeats(seatsToRender, occupied, canvasWidth, startY, columns, aislePosition) {
        let finalHeight = startY;

        seatsToRender.forEach((seatNum, index) => {
            const colIndex = index % columns;
            const rowIndex = Math.floor(index / columns);

            // Calcular posición
            const blockW = (columns * (this.SEAT_WIDTH + this.SEAT_GAP)) + this.AISLE_GAP - this.SEAT_GAP;
            const offsetX = (canvasWidth - blockW) / 2;

            let marginX = colIndex >= aislePosition ? this.AISLE_GAP : 0;
            let left = offsetX + (colIndex * (this.SEAT_WIDTH + this.SEAT_GAP)) + marginX;
            let top = startY + (rowIndex * (this.SEAT_HEIGHT + this.SEAT_GAP));

            // Determinar estado
            let status = 'libre';
            let occupiedInfo = null;

            const o = occupied.find(x => parseInt(x.numero) === seatNum);
            if (o) {
                status = (o.estado === 'vendido' || o.estado === '1') ? 'vendido' : 'reservado';
                occupiedInfo = o;
            }

            // Crear asiento
            const seatObj = this.createSeat(left, top, seatNum, status, occupiedInfo);
            
            // Agregar evento click si no es read-only
            if (!this.readOnly && this.onSeatClick) {
                seatObj.on('mousedown', () => this.onSeatClick(seatObj));
            }

            this.canvas.add(seatObj);
            finalHeight = top + this.SEAT_HEIGHT + 30;
        });

        return finalHeight;
    }

    /**
     * Crear objeto de asiento
     */
    createSeat(x, y, num, status, occupiedInfo) {
        let fill = '#ffffff';
        let stroke = '#cbd5e1';
        let txtColor = '#64748b';

        if (status === 'vendido') {
            fill = '#d1fae5'; // Verde claro
            stroke = '#10b981'; // Verde
            txtColor = '#065f46'; // Verde oscuro
        } else if (status === 'reservado') {
            fill = '#fcd34d'; // Amarillo
            stroke = '#f59e0b'; // Naranja
            txtColor = '#78350f'; // Marrón
        }

        // Rectángulo del asiento
        const rect = new fabric.Rect({
            width: this.SEAT_WIDTH,
            height: this.SEAT_HEIGHT,
            rx: 8,
            ry: 8,
            fill: fill,
            stroke: stroke,
            strokeWidth: 2,
            shadow: new fabric.Shadow({
                color: 'rgba(0,0,0,0.1)',
                blur: 4,
                offsetX: 2,
                offsetY: 2
            }),
            originX: 'center',
            originY: 'center'
        });

        // Número del asiento
        const text = new fabric.Text(String(num), {
            fontSize: 14,
            fontFamily: 'Arial',
            fontWeight: 'bold',
            fill: txtColor,
            originX: 'center',
            originY: 'center'
        });

        // Grupo (asiento completo)
        return new fabric.Group([rect, text], {
            left: x,
            top: y,
            hasControls: false,
            lockMovementX: true,
            lockMovementY: true,
            hoverCursor: this.readOnly ? 'default' : 'pointer',
            selectable: !this.readOnly,
            data: {
                n: num,
                s: status,
                id: occupiedInfo ? occupiedInfo.id : null
            }
        });
    }

    /**
     * Resaltar asiento seleccionado
     */
    highlightSeat(seatGroup) {
        // Resetear todos los asientos
        this.canvas.getObjects().forEach(o => {
            if (o.type === 'group' && o.data) {
                this.resetSeatStyle(o);
            }
        });

        // Resaltar el seleccionado
        if (seatGroup && seatGroup.data) {
            seatGroup.item(0).set({
                fill: '#10b981',
                stroke: '#059669',
                shadow: new fabric.Shadow({
                    color: 'rgba(16, 185, 129, 0.4)',
                    blur: 8,
                    offsetX: 0,
                    offsetY: 4
                })
            });
            seatGroup.item(1).set({
                fill: '#ffffff'
            });
        }

        this.canvas.renderAll();
    }

    /**
     * Resetear estilo de asiento a su estado original
     */
    resetSeatStyle(seatGroup) {
        const status = seatGroup.data.s;
        let fill = '#ffffff';
        let stroke = '#cbd5e1';
        let txtColor = '#64748b';

        if (status === 'vendido') {
            fill = '#d1fae5';
            stroke = '#10b981';
            txtColor = '#065f46';
        } else if (status === 'reservado') {
            fill = '#fcd34d';
            stroke = '#f59e0b';
            txtColor = '#78350f';
        }

        seatGroup.item(0).set({
            fill: fill,
            stroke: stroke,
            shadow: new fabric.Shadow({
                color: 'rgba(0,0,0,0.1)',
                blur: 4,
                offsetX: 2,
                offsetY: 2
            })
        });
        seatGroup.item(1).set({
            fill: txtColor
        });
    }

    /**
     * Destruir canvas
     */
    dispose() {
        if (this.canvas) {
            this.canvas.dispose();
            this.canvas = null;
        }
    }
}

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.BusRenderer = BusRenderer;
}
