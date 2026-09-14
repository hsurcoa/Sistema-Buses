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
 *   renderBus(data, floor, { maxHeight })   -> un piso
 *   renderAllFloors(data, { maxHeight })    -> todos los pisos lado a lado
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
        this.SEAT_MIN = 30;
        this.SEAT_MAX = 52;
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
     * Dibuja UN piso (vista con pestañas, p. ej. la vista previa de Tipos de Buses).
     * @param {Object} data  Datos del viaje/tipo de bus (capacidad, layout_config, asientos_ocupados...)
     * @param {Number} floor Piso a dibujar (1 o 2)
     * @param {Object} opts  { maxHeight } alto maximo en px para el bus (opcional)
     */
    renderBus(data, floor = 1, opts = {}) {
        return this.render(data, [floor], opts);
    }

    /**
     * Dibuja TODOS los pisos lado a lado en una sola vista (venta de pasajes).
     * Si no caben a lo ancho con un tamaño de asiento legible, se apilan.
     */
    renderAllFloors(data, opts = {}) {
        const layout = this.parseLayout(data);
        return this.render(data, Array.from({ length: layout.floors }, (_, i) => i + 1), opts);
    }

    render(data, floorsToShow, opts = {}) {
        if (!this.root) {
            this.initCanvas();
        }
        if (!this.root) {
            return null;
        }

        const layout = this.parseLayout(data);
        this.totalFloors = layout.floors;
        this.currentFloor = floorsToShow[0];

        const perFloor = floorsToShow.map(floor => {
            const numbers = this.calculateSeatsForFloor(layout.totalSeats, layout.floors, floor, layout.config);
            return { floor, numbers, rows: Math.ceil(numbers.length / layout.columns) };
        });

        let seatSize = this.fitSeatSize(layout, perFloor, opts.maxHeight);
        const draw = size => {
            this.root.innerHTML = '';
            this.seats = [];

            const wrap = document.createElement('div');
            wrap.className = 'bus-floors' + (perFloor.length > 1 ? ' bus-floors--multi' : '');
            wrap.style.setProperty('--seat', size + 'px');
            perFloor.forEach(f => wrap.appendChild(this.buildFloor(data, layout, f)));
            this.root.appendChild(wrap);
            return wrap;
        };

        let wrap = draw(seatSize);

        // La estimacion de fitSeatSize es aproximada (bordes, textos del frente,
        // tipografia): se mide lo realmente dibujado y se corrige hasta 4 veces
        // para que los pisos quepan lado a lado y dentro del alto disponible.
        for (let i = 0; i < 4; i++) {
            const corrected = this.correctSeatSize(wrap, seatSize, opts.maxHeight);
            if (!corrected || corrected === seatSize) {
                break;
            }
            seatSize = corrected;
            wrap = draw(seatSize);
        }

        return this.canvas;
    }

    correctSeatSize(wrap, seatSize, maxHeight) {
        const bodies = [...wrap.children];
        if (!bodies.length || !this.root.isConnected) {
            return null;
        }

        const available = this.root.clientWidth || this.width;
        const gap = parseFloat(getComputedStyle(wrap).columnGap) || 0;
        const totalWidth = bodies.reduce((sum, b) => sum + b.offsetWidth, 0) + gap * (bodies.length - 1);
        const tallest = Math.max(...bodies.map(b => b.offsetHeight));

        let scale = (available - 2) / totalWidth;
        if (maxHeight) {
            scale = Math.min(scale, maxHeight / tallest);
        }

        const target = Math.floor(seatSize * Math.min(scale, 1.25));
        // Lado a lado ilegible: se deja que se apilen con el tamaño por ancho de un solo bus
        if (bodies.length > 1 && target < this.SEAT_MIN) {
            const single = Math.floor(seatSize * available / Math.max(...bodies.map(b => b.offsetWidth)));
            return Math.max(this.SEAT_MIN, Math.min(this.SEAT_MAX, single));
        }

        const clamped = Math.max(this.SEAT_MIN, Math.min(this.SEAT_MAX, target));
        // Evita redibujar por diferencias de 1px
        return Math.abs(clamped - seatSize) >= 2 || totalWidth > available ? clamped : seatSize;
    }

    parseLayout(data) {
        const config = data.layout_config || {};
        const columns = Math.max(1, parseInt(config.columnas || 4));
        return {
            config,
            totalSeats: parseInt(data.asientos_total || data.capacidad || 40),
            floors: Math.max(1, parseInt(config.pisos || data.pisos || 1)),
            columns,
            aisle: Math.min(Math.max(0, parseInt(config.posicion_pasillo ?? 2)), columns),
        };
    }

    /**
     * Tamaño de asiento que hace caber el bus en el ancho del contenedor y,
     * si se indica, en el alto disponible (para no tener que desplazarse).
     * Medidas en unidades de asiento, segun los estilos de custom.css:
     *   ancho de un bus = s * (1.2 * columnas + 2.15)
     *   alto de un bus  = s * (1.2 * filas + 1.0) + ~40px (frente)
     */
    fitSeatSize(layout, perFloor, maxHeight) {
        const available = Math.max((this.width || 400) - 8, 240);
        const gapBetween = 16;
        const busUnitsW = 1.2 * layout.columns + 2.15;
        const maxRows = Math.max(...perFloor.map(f => f.rows), 1);

        const byWidthSideBySide = (available - gapBetween * (perFloor.length - 1)) / perFloor.length / busUnitsW;
        // Si lado a lado quedarian demasiado chicos, se apilan: el ancho deja de ser la limitante
        const sideBySide = byWidthSideBySide >= this.SEAT_MIN;
        let size = sideBySide ? byWidthSideBySide : available / busUnitsW;

        if (maxHeight && sideBySide) {
            size = Math.min(size, (maxHeight - 40) / (1.2 * maxRows + 1.0));
        }

        return Math.round(Math.min(this.SEAT_MAX, Math.max(this.SEAT_MIN, size)));
    }

    buildFloor(data, layout, f) {
        const { columns, aisle, floors } = layout;
        const occupied = data.asientos_ocupados || [];

        const body = document.createElement('div');
        body.className = 'bus-body' + (f.floor > 1 ? ' bus-body--upper' : '');
        body.setAttribute('role', 'group');
        const range = f.numbers.length ? ` (asientos ${f.numbers[0]} a ${f.numbers[f.numbers.length - 1]})` : '';
        body.setAttribute('aria-label', floors > 1 ? `Piso ${f.floor}${range}` : 'Mapa de asientos');

        body.appendChild(this.drawFront(f.floor, floors, f.numbers));

        const grid = document.createElement('div');
        grid.className = 'bus-grid';
        // Columnas: nro de fila | asientos izquierda | pasillo | asientos derecha
        const left = aisle;
        const right = columns - aisle;
        const templ = ['var(--row-label)'];
        if (left) templ.push(`repeat(${left}, var(--seat))`);
        if (right) templ.push('var(--aisle)', `repeat(${right}, var(--seat))`);
        grid.style.gridTemplateColumns = templ.join(' ');
        grid.style.gridTemplateRows = `repeat(${f.rows}, var(--seat))`;

        for (let r = 0; r < f.rows; r++) {
            const label = document.createElement('span');
            label.className = 'bus-row-label';
            label.textContent = r + 1;
            label.style.gridRow = r + 1;
            label.style.gridColumn = 1;
            grid.appendChild(label);
        }

        if (left && right && f.rows) {
            const aisleLine = document.createElement('span');
            aisleLine.className = 'bus-aisle';
            aisleLine.style.gridColumn = left + 2;
            aisleLine.style.gridRow = `1 / ${f.rows + 1}`;
            aisleLine.setAttribute('aria-hidden', 'true');
            grid.appendChild(aisleLine);
        }

        f.numbers.forEach((num, index) => {
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

        if (f.floor === 1) {
            ['left', 'right'].forEach(side => {
                const wheel = document.createElement('span');
                wheel.className = `bus-wheel bus-wheel--${side}`;
                wheel.setAttribute('aria-hidden', 'true');
                body.appendChild(wheel);
            });
        }

        return body;
    }

    /** Selecciona un asiento por numero (p. ej. para conservar la seleccion al redibujar). */
    findSeat(num) {
        return this.seats.find(s => s.data.n === parseInt(num)) || null;
    }

    /** Frente del bus: chofer y puerta en el piso 1; escalera en el piso 2. */
    drawFront(floor, floors, numbers = []) {
        const front = document.createElement('div');
        front.className = 'bus-front';

        const range = numbers.length ? `${numbers[0]}–${numbers[numbers.length - 1]}` : '';
        const tag = floors > 1
            ? `<span class="bus-front-tag" title="Asientos ${range}">Piso ${floor}${range ? ` · ${range}` : ''}</span>`
            : '';

        if (floor === 1) {
            front.innerHTML = `
                <span class="bus-front-item"><span class="bus-steering" aria-hidden="true"></span> Chofer</span>
                ${tag}
                <span class="bus-front-item">Puerta <i class="fas fa-door-open" aria-hidden="true"></i></span>`;
        } else {
            front.innerHTML = `
                <span class="bus-front-item"><i class="fas fa-stairs" aria-hidden="true"></i> Escalera</span>
                ${tag}
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
        el.title = `Asiento ${num} · ${labels[status]}` + (occupiedInfo && occupiedInfo.tramo ? ` · ${occupiedInfo.tramo}` : '');
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
