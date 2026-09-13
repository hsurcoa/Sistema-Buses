/**
 * ============================================================================
 * BUS LAYOUT DESIGNER - Fabric.js Implementation
 * ============================================================================
 * Diseñador interactivo de distribución de asientos para buses
 * 
 * Características:
 * - Orientación Horizontal (Landscape)
 * - Soporte para 2 pisos con cambio dinámico
 * - Contador global de asientos
 * - Grid Snapping magnético (20px)
 * - Objetos: Asientos, Conductor, Escalera, Baño
 * 
 * @author Sistema de Transporte
 * @version 2.0
 * ============================================================================
 */

class BusLayoutDesigner {
    /**
     * Constructor
     * @param {string} canvasId - ID del elemento canvas
     * @param {object} options - Opciones de configuración
     */
    constructor(canvasId, options = {}) {
        // Configuración básica
        this.canvasId = canvasId;
        this.width = options.width || 900;
        this.height = options.height || 350;
        this.gridSize = 20; // Grid magnético de 20px

        // Estado del diseñador
        this.currentFloor = 1;
        this.globalSeatCounter = 0;

        // Contenedores de datos por piso
        this.layerPiso1 = [];
        this.layerPiso2 = [];

        // Colores y estilos
        this.colors = {
            chassis: '#34495e',
            chassisFill: '#ffffff',
            seat: '#ffffff',
            seatBorder: '#3498db',
            seatOccupied: '#2ecc71',
            seatText: '#2c3e50',
            driver: '#fff3cd',
            driverBorder: '#ffc107',
            stairs: '#e9ecef',
            stairsBorder: '#6c757d',
            bathroom: '#d1ecf1',
            bathroomBorder: '#0c5460',
            grid: '#dee2e6'
        };

        // Inicializar canvas
        this.init(canvasId);
    }

    /**
     * Inicializar el canvas de Fabric.js
     * @param {string} canvasId - ID del elemento canvas
     */
    init(canvasId) {
        // Crear elemento canvas si no existe
        const container = document.getElementById(canvasId);
        if (!container) {
            console.error(`Container with ID "${canvasId}" not found`);
            return;
        }

        // Limpiar contenedor
        container.innerHTML = '';

        // Crear canvas element
        const canvasElement = document.createElement('canvas');
        canvasElement.id = `${canvasId}-canvas`;
        canvasElement.width = this.width;
        canvasElement.height = this.height;
        container.appendChild(canvasElement);

        // Inicializar Fabric.js
        this.canvas = new fabric.Canvas(canvasElement.id, {
            width: this.width,
            height: this.height,
            backgroundColor: '#f8f9fa',
            selection: true,
            preserveObjectStacking: true
        });

        // Dibujar elementos base
        this.drawChassis();
        this.drawGrid();

        // Configurar eventos
        this.setupEvents();

        console.log('✅ BusLayoutDesigner inicializado correctamente');
    }

    /**
     * Dibujar el chasis del bus (horizontal)
     */
    drawChassis() {
        const padding = 30;
        const chassisWidth = this.width - (padding * 2);
        const chassisHeight = this.height - (padding * 2);

        // Contorno principal del bus
        const chassis = new fabric.Rect({
            left: padding,
            top: padding,
            width: chassisWidth,
            height: chassisHeight,
            fill: this.colors.chassisFill,
            stroke: this.colors.chassis,
            strokeWidth: 4,
            rx: 15,
            ry: 15,
            selectable: false,
            evented: false,
            shadow: new fabric.Shadow({
                color: 'rgba(0,0,0,0.2)',
                blur: 10,
                offsetX: 3,
                offsetY: 3
            })
        });

        this.canvas.add(chassis);
        this.canvas.sendToBack(chassis);

        // Línea divisoria frontal (zona del conductor)
        const frontLine = new fabric.Line(
            [padding + 120, padding + 10, padding + 120, padding + chassisHeight - 10],
            {
                stroke: this.colors.chassis,
                strokeWidth: 2,
                strokeDashArray: [10, 5],
                opacity: 0.4,
                selectable: false,
                evented: false
            }
        );

        this.canvas.add(frontLine);

        // Etiqueta "FRENTE"
        const labelFront = new fabric.Text('FRENTE', {
            left: padding + 40,
            top: padding + 15,
            fontSize: 12,
            fontFamily: 'Arial, sans-serif',
            fontWeight: 'bold',
            fill: this.colors.chassis,
            opacity: 0.3,
            selectable: false,
            evented: false
        });

        this.canvas.add(labelFront);

        // Etiqueta "MOTOR"
        const labelMotor = new fabric.Text('MOTOR', {
            left: padding + chassisWidth - 80,
            top: padding + chassisHeight - 30,
            fontSize: 12,
            fontFamily: 'Arial, sans-serif',
            fontWeight: 'bold',
            fill: this.colors.chassis,
            opacity: 0.3,
            selectable: false,
            evented: false
        });

        this.canvas.add(labelMotor);

        // Ruedas decorativas
        this.drawWheels(padding, chassisWidth, chassisHeight);
    }

    /**
     * Dibujar ruedas decorativas
     */
    drawWheels(padding, chassisWidth, chassisHeight) {
        const wheelRadius = 12;
        const wheelY = padding + chassisHeight + 5;

        // Rueda delantera
        const frontWheel = new fabric.Circle({
            left: padding + 150,
            top: wheelY,
            radius: wheelRadius,
            fill: '#34495e',
            stroke: '#2c3e50',
            strokeWidth: 2,
            opacity: 0.5,
            selectable: false,
            evented: false,
            originX: 'center',
            originY: 'center'
        });

        // Rueda trasera
        const rearWheel = new fabric.Circle({
            left: padding + chassisWidth - 100,
            top: wheelY,
            radius: wheelRadius,
            fill: '#34495e',
            stroke: '#2c3e50',
            strokeWidth: 2,
            opacity: 0.5,
            selectable: false,
            evented: false,
            originX: 'center',
            originY: 'center'
        });

        this.canvas.add(frontWheel, rearWheel);
    }

    /**
     * Dibujar grilla de referencia
     */
    drawGrid() {
        const padding = 30;
        const gridWidth = this.width - (padding * 2);
        const gridHeight = this.height - (padding * 2);

        const gridLines = [];

        // Líneas verticales
        for (let i = 0; i <= gridWidth; i += this.gridSize * 2) {
            const line = new fabric.Line(
                [padding + i, padding, padding + i, padding + gridHeight],
                {
                    stroke: this.colors.grid,
                    strokeWidth: 0.5,
                    opacity: 0.3,
                    selectable: false,
                    evented: false
                }
            );
            gridLines.push(line);
        }

        // Líneas horizontales
        for (let i = 0; i <= gridHeight; i += this.gridSize * 2) {
            const line = new fabric.Line(
                [padding, padding + i, padding + gridWidth, padding + i],
                {
                    stroke: this.colors.grid,
                    strokeWidth: 0.5,
                    opacity: 0.3,
                    selectable: false,
                    evented: false
                }
            );
            gridLines.push(line);
        }

        // Agregar todas las líneas
        gridLines.forEach(line => {
            this.canvas.add(line);
            this.canvas.sendToBack(line);
        });
    }

    /**
     * Configurar eventos del canvas
     */
    setupEvents() {
        // Evento: object:moving - Grid Snapping
        this.canvas.on('object:moving', (e) => {
            const obj = e.target;

            // Aplicar snap to grid
            obj.set({
                left: Math.round(obj.left / this.gridSize) * this.gridSize,
                top: Math.round(obj.top / this.gridSize) * this.gridSize
            });

            obj.setCoords();
        });

        // Evento: object:modified - Guardar cambios
        this.canvas.on('object:modified', () => {
            this.saveCurrentFloor();
        });

        // Evento: selection:created - Highlight
        this.canvas.on('selection:created', (e) => {
            const obj = e.selected[0];
            if (obj && obj.customData && obj.customData.type === 'seat') {
                obj.set('strokeWidth', 3);
                this.canvas.renderAll();
            }
        });

        // Evento: selection:cleared - Remove highlight
        this.canvas.on('selection:cleared', (e) => {
            if (e.deselected) {
                e.deselected.forEach(obj => {
                    if (obj.customData && obj.customData.type === 'seat') {
                        obj.set('strokeWidth', 2);
                    }
                });
                this.canvas.renderAll();
            }
        });
    }

    /**
     * Crear un asiento
     * @param {number} seatNumber - Número del asiento (opcional)
     * @param {number} x - Posición X
     * @param {number} y - Posición Y
     * @returns {fabric.Group} - Grupo del asiento
     */
    createSeat(seatNumber = null, x = 200, y = 150) {
        // Incrementar contador global si no se proporciona número
        if (seatNumber === null) {
            this.globalSeatCounter++;
            seatNumber = this.globalSeatCounter;
        } else {
            // Actualizar contador si el número es mayor
            if (seatNumber > this.globalSeatCounter) {
                this.globalSeatCounter = seatNumber;
            }
        }

        // Base del asiento (cuadrado redondeado)
        const base = new fabric.Rect({
            width: 40,
            height: 40,
            fill: this.colors.seat,
            stroke: this.colors.seatBorder,
            strokeWidth: 2,
            rx: 5,
            ry: 5
        });

        // Respaldo visual (rectángulo fino)
        const backrest = new fabric.Rect({
            width: 30,
            height: 6,
            fill: this.colors.seatBorder,
            left: 5,
            top: -8,
            rx: 2,
            ry: 2
        });

        // Número del asiento
        const text = new fabric.Text(seatNumber.toString(), {
            fontSize: 16,
            fontFamily: 'Arial, sans-serif',
            fontWeight: 'bold',
            fill: this.colors.seatText,
            left: 20,
            top: 20,
            originX: 'center',
            originY: 'center'
        });

        // Crear grupo
        const seatGroup = new fabric.Group([backrest, base, text], {
            left: x,
            top: y,
            selectable: true,
            hasControls: true,
            hasBorders: true,
            lockRotation: true,
            lockScalingX: true,
            lockScalingY: true,
            customData: {
                type: 'seat',
                number: seatNumber
            }
        });

        // Agregar al canvas
        this.canvas.add(seatGroup);
        this.canvas.setActiveObject(seatGroup);
        this.canvas.renderAll();

        // Guardar estado
        this.saveCurrentFloor();
        this.updateCapacity();

        return seatGroup;
    }

    /**
     * Crear área del conductor
     * @param {number} x - Posición X
     * @param {number} y - Posición Y
     * @returns {fabric.Group} - Grupo del conductor
     */
    createDriver(x = 60, y = 100) {
        // Solo visible en Piso 1
        if (this.currentFloor !== 1) {
            alert('El conductor solo puede estar en el Piso 1');
            return null;
        }

        // Verificar si ya existe un conductor
        const existingDriver = this.canvas.getObjects().find(obj =>
            obj.customData && obj.customData.type === 'driver'
        );

        if (existingDriver) {
            alert('Ya existe un área de conductor en este piso');
            return null;
        }

        // Área del conductor
        const driverArea = new fabric.Rect({
            width: 80,
            height: 60,
            fill: this.colors.driver,
            stroke: this.colors.driverBorder,
            strokeWidth: 2,
            rx: 5,
            ry: 5
        });

        // Volante (círculo)
        const wheel = new fabric.Circle({
            radius: 15,
            fill: 'transparent',
            stroke: this.colors.driverBorder,
            strokeWidth: 3,
            left: 40,
            top: 30,
            originX: 'center',
            originY: 'center'
        });

        // Cruz del volante (horizontal)
        const wheelCross1 = new fabric.Line([25, 30, 55, 30], {
            stroke: this.colors.driverBorder,
            strokeWidth: 2
        });

        // Cruz del volante (vertical)
        const wheelCross2 = new fabric.Line([40, 15, 40, 45], {
            stroke: this.colors.driverBorder,
            strokeWidth: 2
        });

        // Texto
        const text = new fabric.Text('CHOFER', {
            fontSize: 10,
            fontFamily: 'Arial, sans-serif',
            fontWeight: 'bold',
            fill: this.colors.driverBorder,
            left: 40,
            top: 50,
            originX: 'center',
            originY: 'center'
        });

        // Crear grupo
        const driverGroup = new fabric.Group(
            [driverArea, wheel, wheelCross1, wheelCross2, text],
            {
                left: x,
                top: y,
                selectable: true,
                hasControls: true,
                hasBorders: true,
                lockRotation: true,
                lockScalingX: true,
                lockScalingY: true,
                customData: {
                    type: 'driver'
                }
            }
        );

        this.canvas.add(driverGroup);
        this.canvas.setActiveObject(driverGroup);
        this.canvas.renderAll();

        this.saveCurrentFloor();

        return driverGroup;
    }

    /**
     * Crear escalera
     * @param {number} x - Posición X
     * @param {number} y - Posición Y
     * @returns {fabric.Group} - Grupo de la escalera
     */
    createStairs(x = 300, y = 150) {
        // Rectángulo base
        const base = new fabric.Rect({
            width: 60,
            height: 100,
            fill: this.colors.stairs,
            stroke: this.colors.stairsBorder,
            strokeWidth: 2,
            rx: 3,
            ry: 3
        });

        // Peldaños (líneas horizontales)
        const steps = [];
        for (let i = 0; i < 5; i++) {
            const step = new fabric.Line(
                [10, 15 + (i * 18), 50, 15 + (i * 18)],
                {
                    stroke: this.colors.stairsBorder,
                    strokeWidth: 2,
                    opacity: 0.5
                }
            );
            steps.push(step);
        }

        // Texto
        const text = new fabric.Text('ESCALERA', {
            fontSize: 10,
            fontFamily: 'Arial, sans-serif',
            fontWeight: 'bold',
            fill: this.colors.stairsBorder,
            left: 30,
            top: 85,
            originX: 'center',
            originY: 'center'
        });

        // Crear grupo
        const stairsGroup = new fabric.Group(
            [base, ...steps, text],
            {
                left: x,
                top: y,
                selectable: true,
                hasControls: true,
                hasBorders: true,
                lockRotation: true,
                lockScalingX: true,
                lockScalingY: true,
                customData: {
                    type: 'stairs'
                }
            }
        );

        this.canvas.add(stairsGroup);
        this.canvas.setActiveObject(stairsGroup);
        this.canvas.renderAll();

        this.saveCurrentFloor();

        return stairsGroup;
    }

    /**
     * Crear baño
     * @param {number} x - Posición X
     * @param {number} y - Posición Y
     * @returns {fabric.Group} - Grupo del baño
     */
    createBathroom(x = 400, y = 150) {
        // Rectángulo base
        const base = new fabric.Rect({
            width: 60,
            height: 80,
            fill: this.colors.bathroom,
            stroke: this.colors.bathroomBorder,
            strokeWidth: 2,
            rx: 3,
            ry: 3
        });

        // Texto "WC"
        const textWC = new fabric.Text('WC', {
            fontSize: 24,
            fontFamily: 'Arial, sans-serif',
            fontWeight: 'bold',
            fill: this.colors.bathroomBorder,
            left: 30,
            top: 30,
            originX: 'center',
            originY: 'center',
            opacity: 0.4
        });

        // Texto "BAÑO"
        const textBano = new fabric.Text('BAÑO', {
            fontSize: 10,
            fontFamily: 'Arial, sans-serif',
            fontWeight: 'bold',
            fill: this.colors.bathroomBorder,
            left: 30,
            top: 65,
            originX: 'center',
            originY: 'center'
        });

        // Crear grupo
        const bathroomGroup = new fabric.Group(
            [base, textWC, textBano],
            {
                left: x,
                top: y,
                selectable: true,
                hasControls: true,
                hasBorders: true,
                lockRotation: true,
                lockScalingX: true,
                lockScalingY: true,
                customData: {
                    type: 'toilet'
                }
            }
        );

        this.canvas.add(bathroomGroup);
        this.canvas.setActiveObject(bathroomGroup);
        this.canvas.renderAll();

        this.saveCurrentFloor();

        return bathroomGroup;
    }

    /**
     * Agregar asiento (API pública)
     * Busca una posición libre automáticamente
     */
    addSeat() {
        // Buscar posición libre
        const position = this.findFreePosition();
        this.createSeat(null, position.x, position.y);
    }

    /**
     * Agregar gadget (API pública)
     * @param {string} type - Tipo: 'stairs', 'toilet', 'driver'
     */
    addGadget(type) {
        const position = this.findFreePosition();

        switch (type) {
            case 'stairs':
                this.createStairs(position.x, position.y);
                break;
            case 'toilet':
                this.createBathroom(position.x, position.y);
                break;
            case 'driver':
                this.createDriver(position.x, position.y);
                break;
            default:
                console.warn(`Tipo de gadget desconocido: ${type}`);
        }
    }

    /**
     * Buscar posición libre en el canvas
     * @returns {object} - {x, y}
     */
    findFreePosition() {
        const padding = 50;
        const startX = 150;
        const startY = 80;
        const spacing = 60;

        // Obtener todas las posiciones ocupadas
        const occupiedPositions = this.canvas.getObjects()
            .filter(obj => obj.customData)
            .map(obj => ({ x: obj.left, y: obj.top }));

        // Buscar posición libre en una grilla
        for (let row = 0; row < 4; row++) {
            for (let col = 0; col < 10; col++) {
                const x = startX + (col * spacing);
                const y = startY + (row * spacing);

                // Verificar si la posición está libre
                const isFree = !occupiedPositions.some(pos =>
                    Math.abs(pos.x - x) < 40 && Math.abs(pos.y - y) < 40
                );

                if (isFree) {
                    return { x, y };
                }
            }
        }

        // Si no hay posición libre, usar una aleatoria
        return {
            x: startX + Math.random() * 400,
            y: startY + Math.random() * 150
        };
    }

    /**
     * Cambiar de piso
     * @param {number} floorNumber - Número de piso (1 o 2)
     */
    setFloor(floorNumber) {
        if (floorNumber !== 1 && floorNumber !== 2) {
            console.error('Número de piso inválido. Use 1 o 2.');
            return;
        }

        // Guardar estado del piso actual
        this.saveCurrentFloor();

        // Cambiar al nuevo piso
        this.currentFloor = floorNumber;

        // Limpiar canvas (excepto chasis y grilla)
        this.clearCanvas(false);

        // Cargar objetos del nuevo piso
        this.loadCurrentFloor();

        console.log(`✅ Cambiado a Piso ${floorNumber}`);
    }

    /**
     * Guardar estado del piso actual
     */
    saveCurrentFloor() {
        const objects = this.canvas.getObjects().filter(obj => obj.customData);

        const data = objects.map(obj => ({
            type: obj.customData.type,
            number: obj.customData.number || null,
            left: obj.left,
            top: obj.top,
            width: obj.width,
            height: obj.height
        }));

        if (this.currentFloor === 1) {
            this.layerPiso1 = data;
        } else {
            this.layerPiso2 = data;
        }

        console.log(`💾 Piso ${this.currentFloor} guardado:`, data.length, 'objetos');
    }

    /**
     * Cargar estado del piso actual
     */
    loadCurrentFloor() {
        const data = this.currentFloor === 1 ? this.layerPiso1 : this.layerPiso2;

        data.forEach(item => {
            switch (item.type) {
                case 'seat':
                    this.createSeat(item.number, item.left, item.top);
                    break;
                case 'driver':
                    this.createDriver(item.left, item.top);
                    break;
                case 'stairs':
                    this.createStairs(item.left, item.top);
                    break;
                case 'toilet':
                    this.createBathroom(item.left, item.top);
                    break;
            }
        });

        console.log(`📂 Piso ${this.currentFloor} cargado:`, data.length, 'objetos');
    }

    /**
     * Limpiar canvas
     * @param {boolean} clearAll - Si es true, limpia todo incluyendo chasis
     */
    clearCanvas(clearAll = true) {
        if (clearAll) {
            this.canvas.clear();
            this.canvas.backgroundColor = '#f8f9fa';
            this.drawChassis();
            this.drawGrid();
        } else {
            // Solo eliminar objetos con customData
            const objects = this.canvas.getObjects().filter(obj => obj.customData);
            objects.forEach(obj => this.canvas.remove(obj));
        }

        this.canvas.renderAll();
    }

    /**
     * Actualizar capacidad total
     */
    updateCapacity() {
        const totalSeats = this.getTotalSeats();

        // Callback para actualizar UI externa
        if (typeof window.actualizarCapacidad === 'function') {
            window.actualizarCapacidad(totalSeats);
        }

        // Actualizar input si existe
        const capacityInput = document.getElementById('capacidadTipoBus');
        if (capacityInput) {
            capacityInput.value = totalSeats;
        }
    }

    /**
     * Obtener total de asientos
     * @returns {number} - Total de asientos en ambos pisos
     */
    getTotalSeats() {
        const seats1 = this.layerPiso1.filter(obj => obj.type === 'seat').length;
        const seats2 = this.layerPiso2.filter(obj => obj.type === 'seat').length;
        return seats1 + seats2;
    }

    /**
     * Exportar configuración completa (API pública)
     * @returns {string} - JSON string con la configuración
     */
    exportConfiguration() {
        // Guardar piso actual antes de exportar
        this.saveCurrentFloor();

        const config = {
            capacity: this.getTotalSeats(),
            floors: 2,
            distribution: {
                floor1: this.layerPiso1,
                floor2: this.layerPiso2
            },
            metadata: {
                globalSeatCounter: this.globalSeatCounter,
                createdAt: new Date().toISOString(),
                version: '2.0'
            }
        };

        return JSON.stringify(config);
    }

    /**
     * Importar configuración
     * @param {string} jsonString - JSON string con la configuración
     */
    importConfiguration(jsonString) {
        try {
            const config = JSON.parse(jsonString);

            // Cargar datos
            this.layerPiso1 = config.distribution.floor1 || [];
            this.layerPiso2 = config.distribution.floor2 || [];
            this.globalSeatCounter = config.metadata?.globalSeatCounter || 0;

            // Limpiar y recargar
            this.clearCanvas(false);
            this.loadCurrentFloor();
            this.updateCapacity();

            console.log('✅ Configuración importada correctamente');
        } catch (error) {
            console.error('❌ Error al importar configuración:', error);
            alert('Error al cargar la configuración del bus');
        }
    }

    /**
     * Destruir instancia
     */
    destroy() {
        if (this.canvas) {
            this.canvas.dispose();
            this.canvas = null;
        }
        console.log('🗑️ BusLayoutDesigner destruido');
    }
}

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.BusLayoutDesigner = BusLayoutDesigner;
}
