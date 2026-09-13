/**
 * ============================================
 * BUS DESIGNER - Konva.js Canvas Designer
 * ============================================
 * Diseñador visual de distribución de asientos para buses
 * Estilo: Plano arquitectónico técnico
 */

class BusDesigner {
    constructor(containerId, options = {}) {
        // Configuración
        this.containerId = containerId;
        this.width = options.width || 700;
        this.height = options.height || 500;
        this.gridSize = 10; // Tamaño de la grilla magnética

        // Estado
        this.pisoActual = 1;
        this.contadorAsientos = 0;
        this.jsonPiso1 = [];
        this.jsonPiso2 = [];

        // Colores del tema (estilo arquitectónico)
        this.colors = {
            background: '#f5f7fa',
            busFrame: '#2c3e50',
            seat: '#ffffff',
            seatBorder: '#bdc3c7',
            seatHover: '#3498db',
            seatText: '#2c3e50',
            bathroom: '#ecf0f1',
            bathroomBorder: '#95a5a6',
            escalera: '#e8f4f8',
            escaleraAccent: '#3498db',
            conductor: '#fff9e6',
            conductorAccent: '#f39c12',
            grid: '#e0e0e0'
        };

        // Inicializar Konva
        this.initKonva();
    }

    /**
     * Inicializar el Stage y Layer de Konva
     */
    initKonva() {
        const container = document.getElementById(this.containerId);

        // Crear Stage
        this.stage = new Konva.Stage({
            container: this.containerId,
            width: this.width,
            height: this.height
        });

        // Crear capas
        this.backgroundLayer = new Konva.Layer(); // Fondo y chasis
        this.gridLayer = new Konva.Layer(); // Grilla de referencia
        this.mainLayer = new Konva.Layer(); // Elementos principales

        this.stage.add(this.backgroundLayer);
        this.stage.add(this.gridLayer);
        this.stage.add(this.mainLayer);

        // Dibujar elementos base
        this.dibujarFondo();
        this.dibujarChasis();
        this.dibujarGrilla();
    }

    /**
     * Dibujar fondo del canvas
     */
    dibujarFondo() {
        const fondo = new Konva.Rect({
            x: 0,
            y: 0,
            width: this.width,
            height: this.height,
            fill: this.colors.background
        });
        this.backgroundLayer.add(fondo);
        this.backgroundLayer.draw();
    }

    /**
     * Dibujar grilla de referencia (opcional, puede ocultarse)
     */
    dibujarGrilla() {
        const padding = 50;
        const gridWidth = this.width - (padding * 2);
        const gridHeight = this.height - (padding * 2);

        // Líneas verticales
        for (let i = 0; i <= gridWidth; i += this.gridSize * 5) {
            const line = new Konva.Line({
                points: [padding + i, padding, padding + i, padding + gridHeight],
                stroke: this.colors.grid,
                strokeWidth: 0.5,
                opacity: 0.3
            });
            this.gridLayer.add(line);
        }

        // Líneas horizontales
        for (let i = 0; i <= gridHeight; i += this.gridSize * 5) {
            const line = new Konva.Line({
                points: [padding, padding + i, padding + gridWidth, padding + i],
                stroke: this.colors.grid,
                strokeWidth: 0.5,
                opacity: 0.3
            });
            this.gridLayer.add(line);
        }

        this.gridLayer.draw();
    }

    /**
     * Dibujar contorno del chasis del bus (guía visual mejorada)
     * Este método crea un plano arquitectónico detallado del bus
     */
    dibujarChasis() {
        const padding = 50;
        const busWidth = 600;
        const busHeight = 400;
        const busX = padding;
        const busY = padding;

        // ========== CONTORNO PRINCIPAL DEL BUS ==========
        const chasisPrincipal = new Konva.Rect({
            x: busX,
            y: busY,
            width: busWidth,
            height: busHeight,
            fill: '#ffffff',
            stroke: this.colors.busFrame,
            strokeWidth: 4,
            cornerRadius: 20,
            shadowColor: 'rgba(0,0,0,0.15)',
            shadowBlur: 10,
            shadowOffset: { x: 3, y: 3 },
            listening: false // No interactivo
        });

        // ========== ZONA DEL CONDUCTOR (Parte frontal) ==========
        const conductorZona = new Konva.Rect({
            x: busX + 10,
            y: busY + 10,
            width: 100,
            height: 80,
            fill: '#fff9e6',
            stroke: this.colors.conductorAccent,
            strokeWidth: 2,
            cornerRadius: 8,
            opacity: 0.3,
            listening: false
        });

        // Volante del conductor (icono)
        const volante = new Konva.Circle({
            x: busX + 60,
            y: busY + 50,
            radius: 20,
            stroke: this.colors.conductorAccent,
            strokeWidth: 3,
            opacity: 0.4,
            listening: false
        });

        const volanteCruz1 = new Konva.Line({
            points: [busX + 60, busY + 30, busX + 60, busY + 70],
            stroke: this.colors.conductorAccent,
            strokeWidth: 2,
            opacity: 0.4,
            listening: false
        });

        const volanteCruz2 = new Konva.Line({
            points: [busX + 40, busY + 50, busX + 80, busY + 50],
            stroke: this.colors.conductorAccent,
            strokeWidth: 2,
            opacity: 0.4,
            listening: false
        });

        // Texto "CONDUCTOR"
        const textoConductor = new Konva.Text({
            x: busX + 10,
            y: busY + 70,
            width: 100,
            text: 'CONDUCTOR',
            fontSize: 10,
            fontFamily: 'Arial, sans-serif',
            fontStyle: 'bold',
            fill: this.colors.conductorAccent,
            align: 'center',
            opacity: 0.5,
            listening: false
        });

        // ========== LÍNEA DE SEPARACIÓN FRONTAL ==========
        const lineaFrontal = new Konva.Line({
            points: [busX + 120, busY + 10, busX + 120, busY + busHeight - 10],
            stroke: this.colors.busFrame,
            strokeWidth: 2,
            dash: [8, 4],
            opacity: 0.4,
            listening: false
        });

        // ========== PUERTAS DE ENTRADA/SALIDA ==========
        // Puerta delantera
        const puertaDelantera = new Konva.Rect({
            x: busX + 130,
            y: busY + busHeight - 90,
            width: 50,
            height: 80,
            stroke: '#e74c3c',
            strokeWidth: 2,
            dash: [5, 3],
            cornerRadius: 5,
            opacity: 0.5,
            listening: false
        });

        const textoPuertaDelantera = new Konva.Text({
            x: busX + 130,
            y: busY + busHeight - 50,
            width: 50,
            text: 'PUERTA',
            fontSize: 8,
            fontFamily: 'Arial',
            fill: '#e74c3c',
            align: 'center',
            opacity: 0.6,
            listening: false
        });

        // Puerta trasera (opcional)
        const puertaTrasera = new Konva.Rect({
            x: busX + busWidth - 60,
            y: busY + busHeight - 90,
            width: 50,
            height: 80,
            stroke: '#e74c3c',
            strokeWidth: 2,
            dash: [5, 3],
            cornerRadius: 5,
            opacity: 0.5,
            listening: false
        });

        const textoPuertaTrasera = new Konva.Text({
            x: busX + busWidth - 60,
            y: busY + busHeight - 50,
            width: 50,
            text: 'SALIDA',
            fontSize: 8,
            fontFamily: 'Arial',
            fill: '#e74c3c',
            align: 'center',
            opacity: 0.6,
            listening: false
        });

        // ========== VENTANAS LATERALES (Decorativas) ==========
        // Ventanas superiores (lado izquierdo)
        for (let i = 0; i < 8; i++) {
            const ventana = new Konva.Rect({
                x: busX + 140 + (i * 60),
                y: busY + 15,
                width: 50,
                height: 30,
                stroke: '#3498db',
                strokeWidth: 1,
                cornerRadius: 3,
                opacity: 0.2,
                listening: false
            });
            this.backgroundLayer.add(ventana);
        }

        // Ventanas inferiores (lado derecho)
        for (let i = 0; i < 8; i++) {
            const ventana = new Konva.Rect({
                x: busX + 140 + (i * 60),
                y: busY + busHeight - 45,
                width: 50,
                height: 30,
                stroke: '#3498db',
                strokeWidth: 1,
                cornerRadius: 3,
                opacity: 0.2,
                listening: false
            });
            this.backgroundLayer.add(ventana);
        }

        // ========== RUEDAS (Decorativas) ==========
        // Rueda delantera
        const ruedaDelantera = new Konva.Circle({
            x: busX + 150,
            y: busY + busHeight + 5,
            radius: 15,
            fill: '#34495e',
            stroke: '#2c3e50',
            strokeWidth: 2,
            opacity: 0.4,
            listening: false
        });

        // Rueda trasera
        const ruedaTrasera = new Konva.Circle({
            x: busX + busWidth - 100,
            y: busY + busHeight + 5,
            radius: 15,
            fill: '#34495e',
            stroke: '#2c3e50',
            strokeWidth: 2,
            opacity: 0.4,
            listening: false
        });

        // ========== TEXTO DE GUÍA ==========
        const textoGuia = new Konva.Text({
            x: busX + busWidth / 2 - 100,
            y: busY + 20,
            width: 200,
            text: 'ÁREA DE DISEÑO',
            fontSize: 14,
            fontFamily: 'Arial, sans-serif',
            fontStyle: 'bold',
            fill: this.colors.busFrame,
            align: 'center',
            opacity: 0.15,
            listening: false
        });

        // ========== AGREGAR TODOS LOS ELEMENTOS ==========
        this.backgroundLayer.add(chasisPrincipal);
        this.backgroundLayer.add(conductorZona);
        this.backgroundLayer.add(volante);
        this.backgroundLayer.add(volanteCruz1);
        this.backgroundLayer.add(volanteCruz2);
        this.backgroundLayer.add(textoConductor);
        this.backgroundLayer.add(lineaFrontal);
        this.backgroundLayer.add(puertaDelantera);
        this.backgroundLayer.add(textoPuertaDelantera);
        this.backgroundLayer.add(puertaTrasera);
        this.backgroundLayer.add(textoPuertaTrasera);
        this.backgroundLayer.add(ruedaDelantera);
        this.backgroundLayer.add(ruedaTrasera);
        this.backgroundLayer.add(textoGuia);

        this.backgroundLayer.draw();
    }

    /**
     * ==========================================
     * CREAR ASIENTO (Estilo arquitectónico)
     * ==========================================
     */
    crearAsiento(id, x = 100, y = 100) {
        const grupo = new Konva.Group({
            x: x,
            y: y,
            draggable: true,
            id: `asiento-${id}`
        });

        // Base del asiento (rectángulo principal)
        const base = new Konva.Rect({
            width: 40,
            height: 40,
            fill: this.colors.seat,
            stroke: this.colors.seatBorder,
            strokeWidth: 2,
            cornerRadius: 3,
            shadowColor: 'rgba(0,0,0,0.1)',
            shadowBlur: 4,
            shadowOffset: { x: 2, y: 2 }
        });

        // Respaldo del asiento (detalle superior)
        const respaldo = new Konva.Rect({
            x: 5,
            y: -5,
            width: 30,
            height: 8,
            fill: this.colors.seatBorder,
            cornerRadius: 2
        });

        // Número del asiento
        const numero = new Konva.Text({
            text: id.toString(),
            fontSize: 16,
            fontFamily: 'Arial, sans-serif',
            fontStyle: 'bold',
            fill: this.colors.seatText,
            width: 40,
            height: 40,
            align: 'center',
            verticalAlign: 'middle'
        });

        // Agregar elementos al grupo
        grupo.add(respaldo);
        grupo.add(base);
        grupo.add(numero);

        // Efectos hover
        grupo.on('mouseenter', () => {
            base.stroke(this.colors.seatHover);
            base.strokeWidth(3);
            this.stage.container().style.cursor = 'move';
            this.mainLayer.draw();
        });

        grupo.on('mouseleave', () => {
            base.stroke(this.colors.seatBorder);
            base.strokeWidth(2);
            this.stage.container().style.cursor = 'default';
            this.mainLayer.draw();
        });

        // Snap to grid al soltar
        grupo.on('dragend', () => {
            this.snapToGrid(grupo);
            this.guardarEstadoPiso();
        });

        // Doble clic para eliminar
        grupo.on('dblclick', () => {
            this.eliminarElemento(grupo);
        });

        this.mainLayer.add(grupo);
        this.mainLayer.draw();

        return grupo;
    }

    /**
     * ==========================================
     * CREAR BAÑO
     * ==========================================
     */
    crearBano(x = 150, y = 150) {
        const grupo = new Konva.Group({
            x: x,
            y: y,
            draggable: true,
            id: `bano-${Date.now()}`
        });

        // Rectángulo del baño
        const rect = new Konva.Rect({
            width: 60,
            height: 100,
            fill: this.colors.bathroom,
            stroke: this.colors.bathroomBorder,
            strokeWidth: 2,
            cornerRadius: 3
        });

        // Texto "BAÑO" (vertical)
        const texto = new Konva.Text({
            text: 'BAÑO',
            fontSize: 14,
            fontFamily: 'Arial, sans-serif',
            fontStyle: 'bold',
            fill: this.colors.bathroomBorder,
            width: 100,
            height: 60,
            align: 'center',
            verticalAlign: 'middle',
            rotation: 90,
            x: 30,
            y: 0
        });

        // Icono WC
        const icono = new Konva.Text({
            text: 'WC',
            fontSize: 20,
            fontFamily: 'Arial, sans-serif',
            fontStyle: 'bold',
            fill: this.colors.bathroomBorder,
            width: 60,
            height: 100,
            align: 'center',
            verticalAlign: 'middle',
            opacity: 0.3
        });

        grupo.add(rect);
        grupo.add(icono);
        grupo.add(texto);

        this.agregarInteracciones(grupo);

        this.mainLayer.add(grupo);
        this.mainLayer.draw();

        return grupo;
    }

    /**
     * ==========================================
     * CREAR ESCALERA
     * ==========================================
     */
    crearEscalera(x = 200, y = 200) {
        const grupo = new Konva.Group({
            x: x,
            y: y,
            draggable: true,
            id: `escalera-${Date.now()}`
        });

        // Rectángulo base
        const rect = new Konva.Rect({
            width: 80,
            height: 120,
            fill: this.colors.escalera,
            stroke: this.colors.escaleraAccent,
            strokeWidth: 2,
            cornerRadius: 3
        });

        // Dibujar escalones (efecto visual)
        for (let i = 0; i < 5; i++) {
            const escalon = new Konva.Line({
                points: [10, 20 + (i * 20), 70, 20 + (i * 20)],
                stroke: this.colors.escaleraAccent,
                strokeWidth: 2,
                opacity: 0.4
            });
            grupo.add(escalon);
        }

        // Texto
        const texto = new Konva.Text({
            text: 'ESCALERA',
            fontSize: 12,
            fontFamily: 'Arial, sans-serif',
            fontStyle: 'bold',
            fill: this.colors.escaleraAccent,
            width: 80,
            height: 120,
            align: 'center',
            verticalAlign: 'bottom',
            padding: 10
        });

        grupo.add(rect);
        grupo.add(texto);

        this.agregarInteracciones(grupo);

        this.mainLayer.add(grupo);
        this.mainLayer.draw();

        return grupo;
    }

    /**
     * ==========================================
     * CREAR CONDUCTOR
     * ==========================================
     */
    crearConductor(x = 250, y = 80) {
        const grupo = new Konva.Group({
            x: x,
            y: y,
            draggable: true,
            id: `conductor-${Date.now()}`
        });

        // Área del conductor
        const rect = new Konva.Rect({
            width: 90,
            height: 70,
            fill: this.colors.conductor,
            stroke: this.colors.conductorAccent,
            strokeWidth: 2,
            cornerRadius: 5
        });

        // Volante (círculo)
        const volante = new Konva.Circle({
            x: 45,
            y: 35,
            radius: 15,
            stroke: this.colors.conductorAccent,
            strokeWidth: 3,
            fill: 'transparent'
        });

        // Cruz del volante
        const cruz1 = new Konva.Line({
            points: [45, 20, 45, 50],
            stroke: this.colors.conductorAccent,
            strokeWidth: 2
        });

        const cruz2 = new Konva.Line({
            points: [30, 35, 60, 35],
            stroke: this.colors.conductorAccent,
            strokeWidth: 2
        });

        // Texto
        const texto = new Konva.Text({
            text: 'CONDUCTOR',
            fontSize: 10,
            fontFamily: 'Arial, sans-serif',
            fontStyle: 'bold',
            fill: this.colors.conductorAccent,
            width: 90,
            height: 20,
            align: 'center',
            y: 52
        });

        grupo.add(rect);
        grupo.add(volante);
        grupo.add(cruz1);
        grupo.add(cruz2);
        grupo.add(texto);

        this.agregarInteracciones(grupo);

        this.mainLayer.add(grupo);
        this.mainLayer.draw();

        return grupo;
    }

    /**
     * ==========================================
     * AGREGAR INTERACCIONES COMUNES
     * ==========================================
     */
    agregarInteracciones(grupo) {
        // Hover effect
        grupo.on('mouseenter', () => {
            grupo.opacity(0.8);
            this.stage.container().style.cursor = 'move';
            this.mainLayer.draw();
        });

        grupo.on('mouseleave', () => {
            grupo.opacity(1);
            this.stage.container().style.cursor = 'default';
            this.mainLayer.draw();
        });

        // Snap to grid
        grupo.on('dragend', () => {
            this.snapToGrid(grupo);
            this.guardarEstadoPiso();
        });

        // Doble clic para eliminar
        grupo.on('dblclick', () => {
            this.eliminarElemento(grupo);
        });
    }

    /**
     * ==========================================
     * SNAP TO GRID (Grilla Magnética)
     * ==========================================
     */
    snapToGrid(elemento) {
        const x = Math.round(elemento.x() / this.gridSize) * this.gridSize;
        const y = Math.round(elemento.y() / this.gridSize) * this.gridSize;

        elemento.position({ x, y });
        this.mainLayer.draw();
    }

    /**
     * ==========================================
     * ELIMINAR ELEMENTO
     * ==========================================
     */
    eliminarElemento(elemento) {
        if (confirm('¿Eliminar este elemento?')) {
            elemento.destroy();
            this.mainLayer.draw();
            this.guardarEstadoPiso();

            // Recalcular contador de asientos si es un asiento
            if (elemento.id().startsWith('asiento-')) {
                this.recalcularAsientos();
            }
        }
    }

    /**
     * ==========================================
     * AGREGAR ASIENTO (Botón)
     * ==========================================
     */
    agregarAsiento() {
        this.contadorAsientos++;
        const x = 100 + (Math.random() * 400);
        const y = 100 + (Math.random() * 300);
        this.crearAsiento(this.contadorAsientos, x, y);
        this.guardarEstadoPiso();
    }

    /**
     * ==========================================
     * AGREGAR BAÑO (Botón)
     * ==========================================
     */
    agregarBano() {
        const x = 100 + (Math.random() * 400);
        const y = 100 + (Math.random() * 300);
        this.crearBano(x, y);
        this.guardarEstadoPiso();
    }

    /**
     * ==========================================
     * AGREGAR ESCALERA (Botón)
     * ==========================================
     */
    agregarEscalera() {
        const x = 100 + (Math.random() * 400);
        const y = 100 + (Math.random() * 300);
        this.crearEscalera(x, y);
        this.guardarEstadoPiso();
    }

    /**
     * ==========================================
     * AGREGAR CONDUCTOR (Botón)
     * ==========================================
     */
    agregarConductor() {
        // Solo permitir un conductor por piso
        const conductorExistente = this.mainLayer.find('.conductor');
        if (conductorExistente.length > 0) {
            alert('Ya existe un área de conductor en este piso');
            return;
        }

        this.crearConductor(100, 80);
        this.guardarEstadoPiso();
    }

    /**
     * ==========================================
     * LIMPIAR TODO EL CANVAS
     * ==========================================
     */
    limpiarTodo() {
        if (confirm('¿Está seguro de eliminar todos los elementos del piso actual?')) {
            this.mainLayer.destroyChildren();
            this.mainLayer.draw();
            this.contadorAsientos = 0;
            this.guardarEstadoPiso();
        }
    }

    /**
     * ==========================================
     * CAMBIAR DE PISO
     * ==========================================
     */
    cambiarPiso(numeroPiso) {
        // Guardar estado del piso actual
        this.guardarEstadoPiso();

        // Cambiar al nuevo piso
        this.pisoActual = numeroPiso;

        // Limpiar canvas
        this.mainLayer.destroyChildren();

        // Cargar estado del nuevo piso
        this.cargarEstadoPiso();

        this.mainLayer.draw();
    }

    /**
     * ==========================================
     * GUARDAR ESTADO DEL PISO ACTUAL
     * ==========================================
     */
    guardarEstadoPiso() {
        const elementos = [];

        this.mainLayer.children.forEach(child => {
            const data = {
                id: child.id(),
                tipo: this.obtenerTipoElemento(child.id()),
                x: child.x(),
                y: child.y()
            };

            // Si es asiento, guardar el número
            if (data.tipo === 'asiento') {
                const texto = child.findOne('Text');
                if (texto) {
                    data.numero = parseInt(texto.text());
                }
            }

            elementos.push(data);
        });

        if (this.pisoActual === 1) {
            this.jsonPiso1 = elementos;
        } else {
            this.jsonPiso2 = elementos;
        }

        console.log(`Piso ${this.pisoActual} guardado:`, elementos);
    }

    /**
     * ==========================================
     * CARGAR ESTADO DEL PISO
     * ==========================================
     */
    cargarEstadoPiso() {
        const datos = this.pisoActual === 1 ? this.jsonPiso1 : this.jsonPiso2;

        datos.forEach(item => {
            switch (item.tipo) {
                case 'asiento':
                    this.crearAsiento(item.numero, item.x, item.y);
                    break;
                case 'bano':
                    this.crearBano(item.x, item.y);
                    break;
                case 'escalera':
                    this.crearEscalera(item.x, item.y);
                    break;
                case 'conductor':
                    this.crearConductor(item.x, item.y);
                    break;
            }
        });
    }

    /**
     * ==========================================
     * OBTENER TIPO DE ELEMENTO
     * ==========================================
     */
    obtenerTipoElemento(id) {
        if (id.startsWith('asiento-')) return 'asiento';
        if (id.startsWith('bano-')) return 'bano';
        if (id.startsWith('escalera-')) return 'escalera';
        if (id.startsWith('conductor-')) return 'conductor';
        return 'desconocido';
    }

    /**
     * ==========================================
     * RECALCULAR ASIENTOS
     * ==========================================
     */
    recalcularAsientos() {
        const asientos = this.mainLayer.find(node => {
            return node.id() && node.id().startsWith('asiento-');
        });

        // Actualizar capacidad en el formulario
        if (typeof actualizarCapacidad === 'function') {
            actualizarCapacidad(asientos.length);
        }
    }

    /**
     * ==========================================
     * EXPORTAR CONFIGURACIÓN COMPLETA (JSON)
     * ==========================================
     * Este JSON se enviará a la base de datos
     */
    exportarConfiguracion() {
        // Asegurar que el piso actual esté guardado
        this.guardarEstadoPiso();

        const configuracion = {
            piso1: this.jsonPiso1,
            piso2: this.jsonPiso2,
            totalAsientos: this.contarAsientosTotales(),
            metadata: {
                fechaCreacion: new Date().toISOString(),
                version: '1.0'
            }
        };

        return JSON.stringify(configuracion);
    }

    /**
     * ==========================================
     * IMPORTAR CONFIGURACIÓN (Cargar desde BD)
     * ==========================================
     */
    importarConfiguracion(jsonString) {
        try {
            const config = JSON.parse(jsonString);

            this.jsonPiso1 = config.piso1 || [];
            this.jsonPiso2 = config.piso2 || [];

            // Cargar el piso actual
            this.mainLayer.destroyChildren();
            this.cargarEstadoPiso();
            this.mainLayer.draw();

            console.log('Configuración importada correctamente');
        } catch (error) {
            console.error('Error al importar configuración:', error);
            alert('Error al cargar la configuración del bus');
        }
    }

    /**
     * ==========================================
     * CONTAR ASIENTOS TOTALES
     * ==========================================
     */
    contarAsientosTotales() {
        const asientosPiso1 = this.jsonPiso1.filter(e => e.tipo === 'asiento').length;
        const asientosPiso2 = this.jsonPiso2.filter(e => e.tipo === 'asiento').length;
        return asientosPiso1 + asientosPiso2;
    }

    /**
     * ==========================================
     * DESTRUIR INSTANCIA
     * ==========================================
     */
    destruir() {
        this.stage.destroy();
    }
}

// Exportar para uso global
window.BusDesigner = BusDesigner;
