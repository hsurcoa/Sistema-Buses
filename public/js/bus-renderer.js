/**
 * BUS RENDERER - Componente unificado para dibujar el mapa de asientos
 * Usado en: Venta de pasajes y vista previa de Tipos de Buses.
 *
 * v3: HTML/CSS en vez de canvas (Fabric.js). El bus se dibuja como un bus
 * visto desde arriba (frente con chofer y puerta, filas numeradas, pasillo,
 * asientos con respaldo, ruedas), escala con el ancho disponible y cada
 * asiento es un <button> accesible por teclado. Estilos: public/css/custom.css
 * (seccion "Mapa de asientos").
 *
 * Interfaz compatible con la v2 (canvas):
 *   new BusRenderer(id, { onSeatClick, readOnly })
 *   initCanvas(width, height)   -> prepara el contenedor (reemplaza el <canvas id> por un <div id>)
 *   renderBus(data, floor)
 *   highlightSeat(seat)         -> seat = { data: { n, s, id }, el }
 *   dispose()
 */
class BusRenderer {
    constructor(canvasId, options = {}) {
        this.canvasId = canvasId;
        this.canvas = null;
        this.root = null;
        this.seats = [];
        this.currentFloor = 1;
        this.totalFloors = 1;
        this.onSeatClick = options.onSeatClick || null;
        this.readOnly = options.readOnly || false;

        // Limites del tamaño de asiento (px); el tamaño real depende del ancho disponible
        this.SEAT_MIN = 38;
        this.SEAT_MAX = 60;
    }

    /**
     * Prepara el contenedor. Si el elemento con ese id es un <canvas> (marcado
     * de la v2), se reemplaza por un <div> con el mismo id para que el codigo
     * existente que lo muestra/oculta por id siga funcionando.
     */
    initCanvas(width) {
        let el = document.getElementById(this.canvasId);
        if (!el) {
            return null;
        }

        if (el.tagName === 'CANVAS') {
            const div = document.createElement('div');
            div.id = this.canvasId;
            div.style.cssText = el.style.cssText;
            el.replaceWith(div);
            el = div;
        }

        el.classList.add('bus-map');
        el.innerHTML = '';
        this.root = el;
        this.width = width || el.parentElement?.clientWidth || 400;
        this.seats = [];

        const self = this;
        this.canvas = {
            element: el,
            getObjects: () => self.seats,
            renderAll: () => {},
            dispose: () => self.dispose(),
        };

        return this.canvas;
    }

    /**
     * Dibuja el piso indicado.
     * @param {Object} data  Datos del viaje/tipo de bus (capacidad, layout_config, asientos_ocupados...)
     * @param {Number} floor Piso a dibujar (1 o 2)
     */
    renderBus(data, floor = 1) {
        if (!this.root) {
            this.initCanvas();
        }
        if (!this.root) {
            return null;
        }

        this.currentFloor = floor;

        const config = data.layout_config || {};
        const totalSeats = parseInt(data.asientos_total || data.capacidad || 40);
        const floors = parseInt(config.pisos || data.pisos || 1);
        const columns = Math.max(1, parseInt(config.columnas || 4));
        const aisle = Math.min(Math.max(0, parseInt(config.posicion_pasillo ?? 2)), columns);

        this.totalFloors = floors;

        const seatNumbers = this.calculateSeatsForFloor(totalSeats, floors, floor, config);
        const occupied = data.asientos_ocupados || [];
        const rows = Math.ceil(seatNumbers.length / columns);

        // Tamaño del asiento segun el ancho disponible (fila = numero + asientos + pasillo + margenes)
        const available = Math.max(this.width - 24, 260);
        const units = columns + 2.6;
        const seatSize = Math.round(Math.min(this.SEAT_MAX, Math.max(this.SEAT_MIN, available / (units * 1.18))));

        this.root.innerHTML = '';
        this.seats = [];

        const body = document.createElement('div');
        body.className = 'bus-body' + (floor > 1 ? ' bus-body--upper' : '');
        body.style.setProperty('--seat', seatSize + 'px');
        body.setAttribute('role', 'group');
        body.setAttribute('aria-label', floors > 1 ? `Mapa de asientos, piso ${floor}` : 'Mapa de asientos');

        body.appendChild(this.drawFront(floor, floors));

        const grid = document.createElement('div');
        grid.className = 'bus-grid';
        // Columnas: nro de fila | asientos izquierda | pasillo | asientos derecha
        const left = aisle;
        const right = columns - aisle;
        const templ = ['var(--row-label)'];
        if (left) templ.push(`repeat(${left}, var(--seat))`);
        if (right) templ.push('var(--aisle)', `repeat(${right}, var(--seat))`);
        grid.style.gridTemplateColumns = templ.join(' ');
        grid.style.gridTemplateRows = `repeat(${rows}, var(--seat))`;

        for (let r = 0; r < rows; r++) {
            const label = document.createElement('span');
            label.className = 'bus-row-label';
            label.textContent = r + 1;
            label.style.gridRow = r + 1;
            label.style.gridColumn = 1;
            grid.appendChild(label);
        }

        if (left && right) {
            const aisleLine = document.createElement('span');
            aisleLine.className = 'bus-aisle';
            aisleLine.style.gridColumn = left + 2;
            aisleLine.style.gridRow = `1 / ${rows + 1}`;
            aisleLine.setAttribute('aria-hidden', 'true');
            grid.appendChild(aisleLine);
        }

        seatNumbers.forEach((num, index) => {
            const col = index % columns;
            const row = Math.floor(index / columns);
            // +1 por la columna del numero de fila, +1 extra si ya paso el pasillo
            const gridCol = col + 2 + (right && col >= aisle ? 1 : 0);

            const o = occupied.find(x => parseInt(x.numero) === num);
            let status = 'libre';
            if (o) {
                status = (o.estado === 'vendido' || o.estado === '1') ? 'vendido' : 'reservado';
            }

            const seat = this.createSeat(num, status, o || null);
            seat.el.style.gridRow = row + 1;
            seat.el.style.gridColumn = gridCol;
            grid.appendChild(seat.el);
            this.seats.push(seat);
        });

        body.appendChild(grid);

        ['left', 'right'].forEach(side => {
            const wheel = document.createElement('span');
            wheel.className = `bus-wheel bus-wheel--${side}`;
            wheel.setAttribute('aria-hidden', 'true');
            body.appendChild(wheel);
        });

        this.root.appendChild(body);
        return this.canvas;
    }

    /** Frente del bus: chofer y puerta en el piso 1; escalera en el piso 2. */
    drawFront(floor, floors) {
        const front = document.createElement('div');
        front.className = 'bus-front';

        if (floor === 1) {
            front.innerHTML = `
                <span class="bus-front-item"><span class="bus-steering" aria-hidden="true"></span> Chofer</span>
                ${floors > 1 ? '<span class="bus-front-tag">Piso 1</span>' : ''}
                <span class="bus-front-item">Puerta <i class="fas fa-door-open" aria-hidden="true"></i></span>`;
        } else {
            front.innerHTML = `
                <span class="bus-front-item"><i class="fas fa-stairs" aria-hidden="true"></i> Escalera</span>
                <span class="bus-front-tag">Piso ${floor}</span>
                <span class="bus-front-item bus-front-item--muted">Frente</span>`;
        }

        return front;
    }

    /** Asientos que corresponden a cada piso (misma regla que la v2). */
    calculateSeatsForFloor(totalSeats, floors, currentFloor, config) {
        if (floors === 1) {
            return Array.from({ length: totalSeats }, (_, i) => i + 1);
        }

        const dist = config.distribucion || {};
        const piso1Seats = (dist.piso1?.normales || 0) + (dist.piso1?.premium || 0) || Math.ceil(totalSeats * 0.6);

        if (currentFloor === 1) {
            return Array.from({ length: Math.min(piso1Seats, totalSeats) }, (_, i) => i + 1);
        }

        const piso2Seats = Math.max(totalSeats - piso1Seats, 0);
        return Array.from({ length: piso2Seats }, (_, i) => piso1Seats + i + 1);
    }

    createSeat(num, status, occupiedInfo) {
        const interactive = !this.readOnly && this.onSeatClick;
        const el = document.createElement(interactive ? 'button' : 'div');
        el.className = `seat seat--${status}`;

        const labels = { libre: 'Libre', reservado: 'Reservado', vendido: 'Vendido' };
        el.title = `Asiento ${num} · ${labels[status]}`;
        el.setAttribute('aria-label', `Asiento ${num}, ${labels[status]}`);
        el.innerHTML = `<span class="seat-num">${num}</span>`;

        const seat = {
            el,
            data: { n: num, s: status, id: occupiedInfo ? occupiedInfo.id : null },
        };

        if (interactive) {
            el.type = 'button';
            if (status === 'vendido') {
                el.setAttribute('aria-disabled', 'true');
            }
            el.addEventListener('click', () => this.onSeatClick(seat));
        }

        return seat;
    }

    highlightSeat(seat) {
        this.seats.forEach(s => this.resetSeatStyle(s));
        if (seat && seat.el) {
            seat.el.classList.add('is-selected');
            seat.el.setAttribute('aria-pressed', 'true');
        }
    }

    resetSeatStyle(seat) {
        if (seat && seat.el) {
            seat.el.classList.remove('is-selected');
            seat.el.removeAttribute('aria-pressed');
        }
    }

    dispose() {
        if (this.root) {
            this.root.innerHTML = '';
        }
        this.seats = [];
        this.canvas = null;
    }
}

if (typeof window !== 'undefined') {
    window.BusRenderer = BusRenderer;
}
