<x-filament-panels::page>
    <div
        class="deco-floor-editor"
        wire:key="salon-map-editor-{{ $this->selectedFloorPlanId }}"
        x-data="salonMapDesigner({
            initialPayload: @js($this->getEditorPayload()),
            editable: @js($editMode),
        })"
        x-init="init()"
    >
        <div class="editor-grid">
            <aside class="toolbox card-shell">
                <div>
                    <p class="section-kicker">Herramientas</p>
                    <h2 class="section-title">Mapa del salón</h2>
                </div>

                <div class="version-list">
                    <p class="section-kicker">Versiones</p>
                    <template x-for="version in versions" :key="version.id">
                        <button
                            type="button"
                            class="version-btn"
                            :class="{ 'is-current': floor.id === version.id }"
                            @click="$wire.selectVersion(version.id)"
                        >
                            <span x-text="`v${version.version} · ${version.name}`"></span>
                            <span class="version-pill" :class="version.is_active ? 'is-active' : 'is-draft'" x-text="version.is_active ? 'Activa' : 'Borrador'"></span>
                        </button>
                    </template>
                </div>

                <div class="divider"></div>

                <div class="tool-buttons">
                    <button type="button" class="tool-btn" @click="addTable('square')" :disabled="!editable">
                        <x-heroicon-o-stop class="w-4 h-4" />
                        Mesa cuadrada
                    </button>
                    <button type="button" class="tool-btn" @click="addTable('rectangle')" :disabled="!editable">
                        <x-heroicon-o-rectangle-stack class="w-4 h-4" />
                        Mesa rectangular
                    </button>
                    <button type="button" class="tool-btn" @click="addTable('round')" :disabled="!editable">
                        <x-heroicon-o-circle-stack class="w-4 h-4" />
                        Mesa redonda
                    </button>
                    <button type="button" class="tool-btn" @click="addTable('oval')" :disabled="!editable">
                        <x-heroicon-o-adjustments-horizontal class="w-4 h-4" />
                        Mesa ovalada
                    </button>
                </div>

                <div class="divider"></div>

                <div class="tool-buttons">
                    <button type="button" class="tool-btn" @click="toggleGrid()">
                        <x-heroicon-o-view-columns class="w-4 h-4" />
                        <span x-text="showGrid ? 'Ocultar grilla' : 'Mostrar grilla'"></span>
                    </button>
                    <button type="button" class="tool-btn" @click="zoomOut()">
                        <x-heroicon-o-magnifying-glass-minus class="w-4 h-4" />
                        Zoom -
                    </button>
                    <button type="button" class="tool-btn" @click="zoomIn()">
                        <x-heroicon-o-magnifying-glass-plus class="w-4 h-4" />
                        Zoom +
                    </button>
                    <button type="button" class="tool-btn" @click="centerView()">
                        <x-heroicon-o-arrows-pointing-in class="w-4 h-4" />
                        Centrar
                    </button>
                    <button type="button" class="tool-btn" @click="resetView()">
                        <x-heroicon-o-arrow-path-rounded-square class="w-4 h-4" />
                        Reset vista
                    </button>
                </div>

                <div class="divider"></div>

                <div class="stats-box">
                    <div class="stat-row">
                        <span>Mesas</span>
                        <span x-text="tables.length"></span>
                    </div>
                    <div class="stat-row">
                        <span>Zoom</span>
                        <span x-text="Math.round(zoom * 100) + '%'"></span>
                    </div>
                    <div class="stat-row">
                        <span>Choques</span>
                        <span x-text="collisionIds.size"></span>
                    </div>
                </div>

                <button type="button" class="save-btn" @click="saveLayout()" :disabled="saving || !editable">
                    <x-heroicon-o-check-circle class="w-5 h-5" />
                    <span x-text="saving ? 'Guardando...' : 'Guardar layout'"></span>
                </button>
            </aside>

            <section class="canvas-shell card-shell">
                <div class="canvas-header">
                    <div>
                        <p class="section-kicker">Diseñador visual</p>
                        <h3 class="section-title" x-text="floor.name"></h3>
                    </div>
                    <span class="pill" x-text="editable ? 'Edición activa' : 'Edición bloqueada'"></span>
                </div>

                <div class="canvas-stage" x-ref="viewport" @wheel.prevent="handleWheel($event)">
                    <div
                        class="canvas-transform"
                        x-ref="transform"
                        :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom});`"
                    >
                        <div
                            class="floor-board"
                            x-ref="floorBoard"
                            :class="{ 'grid-on': showGrid }"
                            :style="floorStyle()"
                        >
                            <template x-for="table in tables" :key="table.client_id">
                                <div
                                    class="floor-table"
                                    :data-id="table.client_id"
                                    :class="tableClasses(table)"
                                    :style="tableStyle(table)"
                                    @click.stop="selectTable(table.client_id)"
                                >
                                    <div class="table-number" x-text="table.number"></div>
                                    <div class="table-capacity" x-text="`${table.capacity} pax`"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <p class="feedback-message" x-show="hasDuplicateNumbers()">
                    Hay números de mesa duplicados. Corregí antes de guardar.
                </p>
            </section>

            <aside class="properties card-shell">
                <div>
                    <p class="section-kicker">Propiedades</p>
                    <h2 class="section-title">Elemento seleccionado</h2>
                </div>

                <template x-if="selectedTable()">
                    <div class="form-grid">
                        <label class="form-label">
                            Número de mesa
                            <input type="number" class="form-input" x-model.number="selectedTable().number" min="1" max="999">
                        </label>
                        <label class="form-label">
                            Nombre
                            <input type="text" class="form-input" x-model="selectedTable().name" maxlength="120">
                        </label>
                        <label class="form-label">
                            Capacidad
                            <input type="number" class="form-input" x-model.number="selectedTable().capacity" min="1" max="24">
                        </label>
                        <label class="form-label">
                            Forma
                            <select class="form-input" x-model="selectedTable().shape">
                                <option value="square">Cuadrada</option>
                                <option value="rectangle">Rectangular</option>
                                <option value="round">Redonda</option>
                                <option value="oval">Ovalada</option>
                            </select>
                        </label>
                        <label class="form-label">
                            Ancho
                            <input type="number" class="form-input" x-model.number="selectedTable().width" min="40" max="280">
                        </label>
                        <label class="form-label">
                            Alto
                            <input type="number" class="form-input" x-model.number="selectedTable().height" min="40" max="280">
                        </label>
                        <label class="form-label">
                            X
                            <input type="number" class="form-input" x-model.number="selectedTable().x" step="1">
                        </label>
                        <label class="form-label">
                            Y
                            <input type="number" class="form-input" x-model.number="selectedTable().y" step="1">
                        </label>
                        <label class="form-label form-col-span">
                            Rotación
                            <input type="range" min="-180" max="180" class="form-range" x-model.number="selectedTable().rotation">
                            <span class="subtle-text" x-text="`${Math.round(selectedTable().rotation)}°`"></span>
                        </label>
                        <label class="form-label form-col-span">
                            Notas internas
                            <textarea class="form-input h-20" x-model="selectedTable().notes" maxlength="600"></textarea>
                        </label>
                    </div>
                </template>

                <template x-if="!selectedTable()">
                    <div class="empty-state">
                        Seleccioná una mesa para editar sus propiedades.
                    </div>
                </template>

                <div class="divider"></div>

                <h4 class="section-kicker">Configuración del salón</h4>
                <div class="form-grid">
                    <label class="form-label form-col-span">
                        Nombre del layout
                        <input type="text" class="form-input" x-model="floor.name" maxlength="120">
                    </label>
                    <label class="form-label">
                        Ancho del salón
                        <input type="number" class="form-input" x-model.number="floor.width" min="420" max="3000">
                    </label>
                    <label class="form-label">
                        Alto del salón
                        <input type="number" class="form-input" x-model.number="floor.height" min="280" max="2200">
                    </label>
                    <label class="form-label">
                        Tamaño de grilla
                        <input type="number" class="form-input" x-model.number="floor.grid_size" min="8" max="80">
                    </label>
                    <label class="form-label">
                        Snap a grilla
                        <button type="button" class="tool-btn" style="justify-content:center;" @click="gridSnap = !gridSnap">
                            <span x-text="gridSnap ? 'Activo' : 'Libre'"></span>
                        </button>
                    </label>
                </div>

                <button
                    type="button"
                    class="danger-btn"
                    @click="removeSelected()"
                    :disabled="!selectedTable() || !editable"
                >
                    <x-heroicon-o-trash class="w-4 h-4" />
                    Eliminar mesa seleccionada
                </button>
            </aside>
        </div>
    </div>

    @once
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
        @endpush
    @endonce

    @script
    <script>
        Alpine.data('salonMapDesigner', ({ initialPayload, editable }) => ({
            editable,
            saving: false,
            zoom: 0.82,
            panX: 0,
            panY: 0,
            floor: {
                id: null,
                name: 'Salón principal',
                version: 1,
                is_active: true,
                width: 1000,
                height: 640,
                shape: 'rectangle',
                grid_size: 20,
                show_grid: true,
                zones: [],
                fixed_elements: [],
            },
            versions: [],
            tables: [],
            selectedId: null,
            localIdSequence: 1,
            showGrid: true,
            gridSnap: true,
            collisionIds: new Set(),
            dragContext: null,

            init() {
                this.floor = { ...this.floor, ...(initialPayload.floor ?? {}) };
                this.versions = initialPayload.versions ?? [];
                this.showGrid = Boolean(this.floor.show_grid);
                this.tables = (initialPayload.tables ?? []).map((table) => this.normalizeTable({
                    ...table,
                    client_id: `srv-${table.id}`,
                }));
                this.localIdSequence = this.tables.length + 1;
                this.$watch('tables', () => this.recalculateCollisions(), { deep: true });
                this.$watch('floor.width', () => this.recalculateCollisions());
                this.$watch('floor.height', () => this.recalculateCollisions());
                this.$nextTick(() => {
                    this.centerView();
                    this.mountInteractions();
                    this.recalculateCollisions();
                });
            },

            floorStyle() {
                const grid = Math.max(8, Number(this.floor.grid_size || 20));

                return `
                    width: ${this.floor.width}px;
                    height: ${this.floor.height}px;
                    --grid-size: ${grid}px;
                `;
            },

            shapeDefaults(shape) {
                switch (shape) {
                    case 'square':
                        return { width: 78, height: 78 };
                    case 'round':
                        return { width: 88, height: 88 };
                    case 'oval':
                        return { width: 124, height: 78 };
                    default:
                        return { width: 108, height: 70 };
                }
            },

            normalizeTable(table) {
                const defaults = this.shapeDefaults(table.shape ?? 'rectangle');

                return {
                    id: table.id ?? null,
                    client_id: table.client_id ?? `local-${this.localIdSequence++}`,
                    number: Number(table.number ?? this.nextTableNumber()),
                    name: table.name ?? '',
                    capacity: Number(table.capacity ?? 4),
                    shape: table.shape ?? 'rectangle',
                    x: Number(table.x ?? 40),
                    y: Number(table.y ?? 40),
                    width: Number(table.width ?? defaults.width),
                    height: Number(table.height ?? defaults.height),
                    rotation: Number(table.rotation ?? 0),
                    status: table.status ?? 'free',
                    notes: table.notes ?? '',
                    layout_meta: table.layout_meta ?? {},
                };
            },

            addTable(shape = 'rectangle') {
                if (!this.editable) {
                    return;
                }

                const defaults = this.shapeDefaults(shape);
                const table = this.normalizeTable({
                    shape,
                    x: 60 + (this.tables.length % 4) * 50,
                    y: 60 + Math.floor(this.tables.length / 4) * 50,
                    width: defaults.width,
                    height: defaults.height,
                    status: 'free',
                    client_id: `local-${Date.now()}-${this.localIdSequence++}`,
                });

                this.tables.push(table);
                this.selectedId = table.client_id;
                this.$nextTick(() => this.mountInteractions());
            },

            removeSelected() {
                if (!this.editable || !this.selectedId) {
                    return;
                }

                this.tables = this.tables.filter((table) => table.client_id !== this.selectedId);
                this.selectedId = null;
                this.recalculateCollisions();
            },

            selectedTable() {
                return this.tables.find((table) => table.client_id === this.selectedId) ?? null;
            },

            selectTable(clientId) {
                this.selectedId = clientId;
            },

            tableStyle(table) {
                return `
                    left: ${table.x}px;
                    top: ${table.y}px;
                    width: ${table.width}px;
                    height: ${table.height}px;
                    transform: rotate(${table.rotation}deg);
                `;
            },

            tableClasses(table) {
                const shapeClassMap = {
                    square: 'table-shape-square',
                    rectangle: 'table-shape-rectangle',
                    round: 'table-shape-round',
                    oval: 'table-shape-oval',
                };

                return {
                    [shapeClassMap[table.shape] ?? shapeClassMap.rectangle]: true,
                    'is-selected': this.selectedId === table.client_id,
                    'is-outside': this.isPartiallyOutside(table),
                    'is-collision': this.collisionIds.has(table.client_id),
                    'is-readonly': !this.editable,
                };
            },

            isPartiallyOutside(table) {
                return table.x < 0
                    || table.y < 0
                    || (table.x + table.width) > Number(this.floor.width)
                    || (table.y + table.height) > Number(this.floor.height);
            },

            recalculateCollisions() {
                const ids = new Set();

                for (let i = 0; i < this.tables.length; i += 1) {
                    for (let j = i + 1; j < this.tables.length; j += 1) {
                        const a = this.tables[i];
                        const b = this.tables[j];
                        const overlap = a.x < (b.x + b.width)
                            && (a.x + a.width) > b.x
                            && a.y < (b.y + b.height)
                            && (a.y + a.height) > b.y;

                        if (overlap) {
                            ids.add(a.client_id);
                            ids.add(b.client_id);
                        }
                    }
                }

                this.collisionIds = ids;
            },

            nextTableNumber() {
                if (this.tables.length === 0) {
                    return 1;
                }

                return Math.max(...this.tables.map((table) => Number(table.number || 0))) + 1;
            },

            hasDuplicateNumbers() {
                const seen = new Set();
                for (const table of this.tables) {
                    const number = Number(table.number || 0);
                    if (seen.has(number)) {
                        return true;
                    }
                    seen.add(number);
                }
                return false;
            },

            snap(value) {
                if (!this.gridSnap) {
                    return Math.round(value);
                }

                const grid = Math.max(8, Number(this.floor.grid_size || 20));
                return Math.round(value / grid) * grid;
            },

            getPointerClient(event) {
                if (typeof event.clientX === 'number' && typeof event.clientY === 'number') {
                    return { x: event.clientX, y: event.clientY };
                }

                const maybeClient = event?.interaction?.coords?.cur?.client;
                if (maybeClient && typeof maybeClient.x === 'number' && typeof maybeClient.y === 'number') {
                    return { x: maybeClient.x, y: maybeClient.y };
                }

                return null;
            },

            pointerToBoard(clientX, clientY) {
                const board = this.$refs.floorBoard;
                if (!board) {
                    return null;
                }

                const rect = board.getBoundingClientRect();
                return {
                    x: (clientX - rect.left) / this.zoom,
                    y: (clientY - rect.top) / this.zoom,
                };
            },

            clampTableBounds(table) {
                table.width = Math.max(40, Math.min(280, Number(table.width)));
                table.height = Math.max(40, Math.min(280, Number(table.height)));
                table.x = Math.min(Number(this.floor.width) - 10, Math.max(-table.width + 10, Number(table.x)));
                table.y = Math.min(Number(this.floor.height) - 10, Math.max(-table.height + 10, Number(table.y)));
                table.rotation = Math.max(-180, Math.min(180, Number(table.rotation)));
                table.capacity = Math.max(1, Math.min(24, Number(table.capacity || 1)));
            },

            mountInteractions() {
                if (typeof interact === 'undefined') {
                    return;
                }

                // interact.js aporta drag + resize de forma estable con buen rendimiento.
                interact('.floor-table').unset();

                interact('.floor-table')
                    .draggable({
                        listeners: {
                            start: (event) => {
                                if (!this.editable) {
                                    return;
                                }

                                const id = event.target.dataset.id;
                                const table = this.tables.find((item) => item.client_id === id);
                                const pointer = this.getPointerClient(event);
                                if (!table || !pointer) {
                                    this.dragContext = null;
                                    return;
                                }

                                const pointerOnBoard = this.pointerToBoard(pointer.x, pointer.y);
                                if (!pointerOnBoard) {
                                    this.dragContext = null;
                                    return;
                                }

                                this.dragContext = {
                                    id,
                                    offsetX: pointerOnBoard.x - table.x,
                                    offsetY: pointerOnBoard.y - table.y,
                                };
                                this.selectTable(id);
                            },
                            move: (event) => {
                                if (!this.editable) {
                                    return;
                                }

                                const id = event.target.dataset.id;
                                const table = this.tables.find((item) => item.client_id === id);
                                if (!table) {
                                    return;
                                }

                                const pointer = this.getPointerClient(event);
                                const pointerOnBoard = pointer ? this.pointerToBoard(pointer.x, pointer.y) : null;

                                if (pointerOnBoard && this.dragContext?.id === id) {
                                    table.x = pointerOnBoard.x - this.dragContext.offsetX;
                                    table.y = pointerOnBoard.y - this.dragContext.offsetY;
                                } else {
                                    table.x = table.x + (event.dx / this.zoom);
                                    table.y = table.y + (event.dy / this.zoom);
                                }

                                this.clampTableBounds(table);
                            },
                            end: (event) => {
                                const id = event.target.dataset.id;
                                const table = this.tables.find((item) => item.client_id === id);
                                if (table) {
                                    table.x = this.snap(table.x);
                                    table.y = this.snap(table.y);
                                    this.clampTableBounds(table);
                                }

                                this.dragContext = null;
                                this.recalculateCollisions();
                            },
                        },
                    })
                    .resizable({
                        edges: { left: true, right: true, bottom: true, top: true },
                        listeners: {
                            move: (event) => {
                                if (!this.editable) {
                                    return;
                                }

                                const id = event.target.dataset.id;
                                const table = this.tables.find((item) => item.client_id === id);
                                if (!table) {
                                    return;
                                }

                                table.width = this.snap(event.rect.width / this.zoom);
                                table.height = this.snap(event.rect.height / this.zoom);
                                table.x = this.snap(table.x + (event.deltaRect.left / this.zoom));
                                table.y = this.snap(table.y + (event.deltaRect.top / this.zoom));
                                this.clampTableBounds(table);
                            },
                            end: () => this.recalculateCollisions(),
                        },
                    });
            },

            toggleGrid() {
                this.showGrid = !this.showGrid;
                this.floor.show_grid = this.showGrid;
            },

            zoomIn() {
                this.zoom = Math.min(1.8, Number((this.zoom + 0.1).toFixed(2)));
                this.$nextTick(() => this.mountInteractions());
            },

            zoomOut() {
                this.zoom = Math.max(0.45, Number((this.zoom - 0.1).toFixed(2)));
                this.$nextTick(() => this.mountInteractions());
            },

            handleWheel(event) {
                if (event.deltaY < 0) {
                    this.zoomIn();
                    return;
                }

                this.zoomOut();
            },

            centerView() {
                const viewport = this.$refs.viewport;
                if (!viewport) {
                    return;
                }

                const width = Number(this.floor.width) * this.zoom;
                const height = Number(this.floor.height) * this.zoom;
                this.panX = Math.max(16, (viewport.clientWidth - width) / 2);
                this.panY = Math.max(16, (viewport.clientHeight - height) / 2);
            },

            resetView() {
                this.zoom = 0.82;
                this.panX = 0;
                this.panY = 0;
                this.$nextTick(() => {
                    this.centerView();
                    this.mountInteractions();
                });
            },

            exportPayload() {
                this.tables.forEach((table) => this.clampTableBounds(table));
                this.recalculateCollisions();

                return {
                    floor: {
                        id: this.floor.id ?? null,
                        name: this.floor.name,
                        version: Number(this.floor.version || 1),
                        width: Number(this.floor.width),
                        height: Number(this.floor.height),
                        shape: 'rectangle',
                        grid_size: Number(this.floor.grid_size || 20),
                        show_grid: Boolean(this.showGrid),
                        zones: this.floor.zones ?? [],
                        fixed_elements: this.floor.fixed_elements ?? [],
                    },
                    tables: this.tables.map((table) => ({
                        id: table.id,
                        number: Number(table.number),
                        name: table.name || null,
                        capacity: Number(table.capacity),
                        shape: table.shape,
                        x: Number(table.x),
                        y: Number(table.y),
                        width: Number(table.width),
                        height: Number(table.height),
                        rotation: Number(table.rotation),
                        status: table.status ?? 'free',
                        notes: table.notes || null,
                        layout_meta: {
                            collision_warning: this.collisionIds.has(table.client_id),
                            partial_outside: this.isPartiallyOutside(table),
                        },
                    })),
                };
            },

            async saveLayout() {
                if (!this.editable || this.saving) {
                    return;
                }

                this.saving = true;

                try {
                    await this.$wire.saveLayout(this.exportPayload());
                } catch (error) {
                    // Livewire/Filament ya muestran notificación de error de validación.
                    console.error(error);
                } finally {
                    this.saving = false;
                    this.$nextTick(() => this.mountInteractions());
                }
            },
        }));
    </script>
    @endscript

    <style>
        .fi-main,
        .fi-main-ctn,
        .fi-page {
            max-width: 100% !important;
        }
        .deco-floor-editor { color: rgb(15 23 42); }
        .editor-grid { display: grid; grid-template-columns: 132px minmax(0, 1fr) 171px; gap: .55rem; }
        .card-shell {
            border-radius: 18px; border: 1px solid rgb(226 232 240 / 0.95);
            background: linear-gradient(175deg, rgb(255 255 255 / 0.96), rgb(248 250 252 / 0.96));
            box-shadow: 0 18px 50px -28px rgb(2 6 23 / 0.35); padding: 1rem;
        }
        .toolbox.card-shell { padding: .52rem; }
        .properties.card-shell { padding: .58rem; }
        .section-kicker { font-size: .6rem; letter-spacing: .11em; text-transform: uppercase; color: rgb(100 116 139); font-weight: 700; }
        .section-title { margin-top: .12rem; font-size: .78rem; font-weight: 700; color: rgb(15 23 42); line-height: 1.18; }
        .divider { border-top: 1px solid rgb(226 232 240); margin: .5rem 0; }
        .tool-buttons { display: grid; gap: .35rem; }
        .version-list { display: grid; gap: .34rem; }
        .version-btn {
            border-radius: 11px; border: 1px solid rgb(203 213 225);
            padding: .34rem .36rem; display: flex; align-items: center; justify-content: space-between;
            background: rgb(255 255 255 / .85); font-size: .62rem; color: rgb(30 41 59); transition: .18s ease;
            gap: .28rem;
        }
        .version-btn:hover { border-color: rgb(148 163 184); transform: translateY(-1px); }
        .version-btn.is-current { border-color: rgb(245 158 11); box-shadow: 0 0 0 2px rgb(251 191 36 / .24); }
        .version-pill { border-radius: 999px; padding: .07rem .34rem; font-size: .54rem; font-weight: 700; white-space: nowrap; }
        .version-pill.is-active { background: rgb(220 252 231); color: rgb(21 128 61); }
        .version-pill.is-draft { background: rgb(241 245 249); color: rgb(71 85 105); }
        .tool-btn {
            border-radius: 10px; padding: .32rem .34rem; border: 1px solid rgb(203 213 225);
            background: rgb(255 255 255 / 0.85); display: flex; align-items: center; gap: .55rem;
            font-size: .62rem; font-weight: 600; transition: .2s ease;
            line-height: 1.15;
        }
        .tool-btn:hover { border-color: rgb(148 163 184); transform: translateY(-1px); }
        .tool-btn:disabled { opacity: .52; cursor: not-allowed; transform: none; }
        .stats-box { background: rgb(241 245 249 / 0.85); border-radius: 11px; padding: .42rem; display: grid; gap: .2rem; }
        .stat-row { display: flex; justify-content: space-between; font-size: .62rem; color: rgb(51 65 85); }
        .save-btn, .danger-btn {
            margin-top: .48rem; width: 100%; border-radius: 10px; padding: .38rem .4rem; display: inline-flex;
            align-items: center; justify-content: center; gap: .3rem; font-size: .64rem; font-weight: 700;
        }
        .save-btn { background: linear-gradient(135deg, rgb(245 158 11), rgb(217 119 6)); color: white; }
        .save-btn:disabled { opacity: .65; cursor: progress; }
        .danger-btn { border: 1px solid rgb(248 113 113); color: rgb(185 28 28); background: rgb(254 242 242); }
        .canvas-shell { padding: .65rem; min-height: 80vh; display: flex; flex-direction: column; }
        .canvas-header { display: flex; justify-content: space-between; align-items: center; padding: .25rem .45rem .75rem; }
        .pill { border-radius: 999px; padding: .24rem .65rem; font-size: .72rem; font-weight: 700; background: rgb(255 237 213); color: rgb(154 52 18); }
        .canvas-stage {
            flex: 1; border-radius: 16px; border: 1px solid rgb(203 213 225 / 0.95);
            background:
                radial-gradient(circle at 12% 14%, rgb(255 255 255 / .85), transparent 38%),
                linear-gradient(160deg, rgb(15 23 42 / .06), rgb(15 23 42 / .01));
            position: relative; overflow: auto; min-height: 480px;
        }
        .canvas-transform { position: relative; transform-origin: top left; width: max-content; height: max-content; transition: transform .12s ease; }
        .floor-board {
            margin: 0; position: relative; border-radius: 16px; border: 2px solid rgb(148 163 184);
            background:
                linear-gradient(145deg, rgb(249 250 251), rgb(241 245 249)),
                repeating-linear-gradient(45deg, rgb(148 163 184 / .03) 0 12px, transparent 12px 24px);
            box-shadow: inset 0 0 0 1px rgb(226 232 240), 0 20px 40px -22px rgb(15 23 42 / 0.5);
        }
        .floor-board.grid-on::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background-image:
                linear-gradient(to right, rgb(148 163 184 / .24) 1px, transparent 1px),
                linear-gradient(to bottom, rgb(148 163 184 / .24) 1px, transparent 1px);
            background-size: var(--grid-size) var(--grid-size);
            border-radius: 14px;
        }
        .floor-table {
            position: absolute; display: flex; flex-direction: column; align-items: center; justify-content: center;
            border: 2px solid rgb(51 65 85 / .78); background: linear-gradient(180deg, rgb(254 243 199), rgb(252 211 77));
            box-shadow: 0 14px 22px -16px rgb(2 6 23 / .8), inset 0 1px 0 rgb(255 255 255 / .7);
            transition: box-shadow .15s ease, border-color .15s ease, transform .06s linear; user-select: none; cursor: move;
        }
        .floor-table.is-readonly { cursor: default; }
        .floor-table.is-selected { border-color: rgb(37 99 235); box-shadow: 0 0 0 3px rgb(59 130 246 / .22), 0 15px 28px -17px rgb(30 64 175 / .6); z-index: 9; }
        .floor-table.is-collision { border-color: rgb(245 158 11); box-shadow: 0 0 0 3px rgb(251 191 36 / .25), 0 14px 22px -16px rgb(146 64 14 / .65); }
        .floor-table.is-outside { border-color: rgb(239 68 68); box-shadow: 0 0 0 3px rgb(248 113 113 / .2), 0 14px 22px -16px rgb(127 29 29 / .8); }
        .table-shape-square { border-radius: 12px; }
        .table-shape-rectangle { border-radius: 12px; }
        .table-shape-round { border-radius: 999px; }
        .table-shape-oval { border-radius: 999px; }
        .table-number { font-size: 1.03rem; font-weight: 800; color: rgb(15 23 42); line-height: 1; }
        .table-capacity { font-size: .62rem; letter-spacing: .08em; text-transform: uppercase; color: rgb(30 41 59 / .75); margin-top: .22rem; }
        .feedback-message { margin-top: .7rem; font-size: .79rem; color: rgb(220 38 38); font-weight: 600; }
        .form-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: .35rem; }
        .form-col-span { grid-column: span 1 / span 1; }
        .form-label { font-size: .62rem; color: rgb(71 85 105); display: grid; gap: .18rem; font-weight: 650; }
        .form-input {
            border-radius: 10px; border: 1px solid rgb(203 213 225); background: rgb(255 255 255 / .9);
            padding: .26rem .33rem; font-size: .64rem; color: rgb(15 23 42);
        }
        .form-range { width: 100%; accent-color: rgb(217 119 6); height: 14px; }
        .subtle-text { color: rgb(100 116 139); font-size: .58rem; font-weight: 600; }
        .empty-state {
            border: 1px dashed rgb(203 213 225); border-radius: 12px; padding: .9rem; text-align: center;
            color: rgb(100 116 139); font-size: .66rem;
        }
        @media (max-width: 1420px) {
            .editor-grid { grid-template-columns: 1fr; }
            .canvas-shell { min-height: 60vh; }
        }
    </style>
</x-filament-panels::page>
