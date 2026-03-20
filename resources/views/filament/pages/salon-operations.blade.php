@php
    use App\Enums\PaymentMethod;
@endphp

<x-filament-panels::page>
    @php
        $payload = $this->getOperationalPayload();
        $canBill = auth()->user()?->can('orders.update');
        $canFreeTable = auth()->user()?->can('tables.update');
    @endphp

    <div class="ops-wrap" wire:poll.5s>
        <div class="ops-header">
            <div>
                <p class="ops-kicker">Operación en vivo</p>
                <h2 class="ops-title">
                    {{ $payload['restaurant']['name'] ?? 'Sin restaurante' }}
                </h2>
                @if($payload['floor'])
                    <p class="ops-subtitle">
                        {{ $payload['floor']['name'] }} · versión {{ $payload['floor']['version'] }} activa
                    </p>
                @endif
            </div>
            <span class="ops-live-badge">Actualiza cada 5s</span>
        </div>

        @if(!$payload['floor'])
            <div class="ops-empty">
                No hay un plano activo publicado.
            </div>
        @else
            <div class="ops-canvas-shell">
                <div class="ops-canvas" style="width: {{ (int) $payload['floor']['width'] }}px; height: {{ (int) $payload['floor']['height'] }}px;">
                    @foreach($payload['tables'] as $table)
                        @php
                            $statusClass = match($table['status']) {
                                'free' => 'is-free',
                                'occupied' => 'is-occupied',
                                'reserved' => 'is-reserved',
                                'pending_payment' => 'is-pending',
                                'cleaning' => 'is-cleaning',
                                'blocked' => 'is-blocked',
                                default => 'is-default',
                            };
                            $shapeClass = match($table['shape']) {
                                'round' => 'shape-round',
                                'oval' => 'shape-oval',
                                'square' => 'shape-square',
                                default => 'shape-rectangle',
                            };
                            $hasOpen = ($table['open_count'] ?? 0) > 0;
                        @endphp
                        <button
                            type="button"
                            wire:click="selectTable({{ $table['id'] }})"
                            class="ops-table {{ $statusClass }} {{ $shapeClass }}"
                            style="left: {{ $table['x'] }}px; top: {{ $table['y'] }}px; width: {{ $table['width'] }}px; height: {{ $table['height'] }}px; transform: rotate({{ $table['rotation'] }}deg);"
                        >
                            <span class="ops-number">{{ $table['number'] }}</span>
                            @if($hasOpen)
                                <span class="ops-ticket">${{ number_format($table['open_total'], 0, ',', '.') }}</span>
                            @endif
                            <span class="ops-status">{{ $table['status_label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="ops-legend">
                <span class="legend-item"><i class="dot is-free"></i>Libre</span>
                <span class="legend-item"><i class="dot is-occupied"></i>Ocupada</span>
                <span class="legend-item"><i class="dot is-reserved"></i>Reservada</span>
                <span class="legend-item"><i class="dot is-pending"></i>Pendiente cobro</span>
                <span class="legend-item"><i class="dot is-cleaning"></i>Limpieza</span>
                <span class="legend-item"><i class="dot is-blocked"></i>Bloqueada</span>
            </div>
            <p class="ops-hint">Tocá una mesa para ver la cuenta, cobrar y liberar.</p>
        @endif
    </div>

    @if($this->selectedTableId)
        @php
            $bill = $this->getSelectedTableBillPayload();
        @endphp
        @if($bill)
            <div
                class="bill-overlay"
                wire:click="closeBillPanel"
                wire:key="bill-overlay-{{ $this->selectedTableId }}"
            >
                <div
                    class="bill-panel"
                    wire:click.stop
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="bill-panel-title"
                >
                    <div class="bill-panel-head">
                        <div>
                            <p class="bill-kicker">Mesa {{ $bill['table']['number'] }}</p>
                            <h3 id="bill-panel-title" class="bill-title">Cuenta y cobro</h3>
                            <p class="bill-meta">{{ $bill['table']['status_label'] }}
                                @if($bill['orders_count'] > 0)
                                    · {{ $bill['orders_count'] }} pedido(s) abierto(s)
                                @endif
                            </p>
                        </div>
                        <button type="button" wire:click="closeBillPanel" class="bill-close" aria-label="Cerrar">×</button>
                    </div>

                    @if($bill['has_open_orders'])
                        <div class="bill-lines">
                            <p class="bill-section-label">Consumos</p>
                            <ul class="bill-line-list">
                                @foreach($bill['lines'] as $line)
                                    <li class="bill-line">
                                        <span class="bill-line-name">{{ $line['product_name'] }} × {{ $line['quantity'] }}</span>
                                        <span class="bill-line-price">${{ number_format($line['line_total'], 2, ',', '.') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="bill-subtotal">
                                <span>Subtotal</span>
                                <span>${{ number_format($bill['subtotal'], 2, ',', '.') }}</span>
                            </div>
                        </div>

                        @if($canBill)
                            <div class="bill-form">
                                <label class="bill-field">
                                    <span>Descuento ($)</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model.live="billDiscount"
                                        class="bill-input"
                                    />
                                </label>
                                <label class="bill-field">
                                    <span>Forma de pago</span>
                                    <select wire:model="billPaymentMethod" class="bill-input">
                                        <option value="">Elegir…</option>
                                        @foreach(PaymentMethod::options() as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                @php
                                    $disc = (float) ($this->billDiscount ?: 0);
                                    $net = max(0, round($bill['subtotal'] - $disc, 2));
                                @endphp
                                <div class="bill-net">
                                    <span>Total a cobrar</span>
                                    <strong>${{ number_format($net, 2, ',', '.') }}</strong>
                                </div>
                                <button type="button" wire:click="submitBill" class="bill-primary">
                                    Emitir comprobante y facturar
                                </button>
                            </div>
                        @else
                            <p class="bill-no-perm">No tenés permiso para registrar cobros.</p>
                        @endif
                    @else
                        <div class="bill-empty">
                            <p>No hay pedidos abiertos en esta mesa.</p>
                            @if($canFreeTable)
                                <button type="button" wire:click="freeTableOnly" class="bill-secondary">
                                    Marcar mesa como libre
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    <style>
        .ops-wrap { display: grid; gap: 1rem; }
        .ops-header {
            border-radius: 16px;
            border: 1px solid rgb(226 232 240);
            padding: 1rem;
            background: linear-gradient(160deg, rgb(255 255 255), rgb(248 250 252));
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ops-kicker { font-size: .72rem; text-transform: uppercase; letter-spacing: .12em; color: rgb(100 116 139); font-weight: 700; }
        .ops-title { font-size: 1.2rem; font-weight: 800; color: rgb(15 23 42); }
        .ops-subtitle { margin-top: .2rem; font-size: .84rem; color: rgb(71 85 105); }
        .ops-hint { font-size: .8rem; color: rgb(100 116 139); margin: 0; }
        .ops-live-badge {
            border-radius: 999px;
            background: rgb(220 252 231);
            color: rgb(21 128 61);
            font-size: .74rem;
            font-weight: 700;
            padding: .26rem .65rem;
        }
        .ops-empty {
            border-radius: 14px;
            border: 1px dashed rgb(203 213 225);
            padding: 1.3rem;
            color: rgb(100 116 139);
            text-align: center;
        }
        .ops-canvas-shell {
            overflow: auto;
            border-radius: 16px;
            border: 1px solid rgb(203 213 225);
            background: linear-gradient(155deg, rgb(248 250 252), rgb(241 245 249));
            padding: 1rem;
        }
        .ops-canvas {
            position: relative;
            border: 2px solid rgb(148 163 184);
            border-radius: 14px;
            background:
                linear-gradient(to right, rgb(148 163 184 / .2) 1px, transparent 1px),
                linear-gradient(to bottom, rgb(148 163 184 / .2) 1px, transparent 1px),
                linear-gradient(145deg, rgb(255 255 255), rgb(248 250 252));
            background-size: 20px 20px, 20px 20px, auto;
        }
        .ops-table {
            position: absolute;
            border: 2px solid rgb(51 65 85 / .8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .15rem;
            color: rgb(15 23 42);
            box-shadow: 0 12px 24px -20px rgb(15 23 42 / .8), inset 0 1px 0 rgb(255 255 255 / .7);
            cursor: pointer;
            font: inherit;
            padding: .2rem;
        }
        .ops-table:focus-visible { outline: 3px solid rgb(245 158 11); outline-offset: 2px; }
        .shape-square,.shape-rectangle { border-radius: 12px; }
        .shape-round,.shape-oval { border-radius: 999px; }
        .ops-number { font-size: 1rem; font-weight: 800; line-height: 1; }
        .ops-ticket { font-size: .62rem; font-weight: 800; color: rgb(21 128 61); background: rgb(255 255 255 / .85); padding: .1rem .35rem; border-radius: 6px; }
        .ops-status { font-size: .58rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 700; opacity: .86; }
        .is-free { background: linear-gradient(180deg, rgb(220 252 231), rgb(134 239 172)); border-color: rgb(22 163 74); }
        .is-occupied { background: linear-gradient(180deg, rgb(254 243 199), rgb(253 186 116)); border-color: rgb(217 119 6); }
        .is-reserved { background: linear-gradient(180deg, rgb(219 234 254), rgb(147 197 253)); border-color: rgb(37 99 235); }
        .is-pending { background: linear-gradient(180deg, rgb(243 232 255), rgb(196 181 253)); border-color: rgb(109 40 217); }
        .is-cleaning { background: linear-gradient(180deg, rgb(224 242 254), rgb(125 211 252)); border-color: rgb(2 132 199); }
        .is-blocked { background: linear-gradient(180deg, rgb(254 226 226), rgb(252 165 165)); border-color: rgb(220 38 38); opacity: .9; }
        .is-default { background: rgb(226 232 240); border-color: rgb(100 116 139); }
        .ops-legend { display: flex; flex-wrap: wrap; gap: .8rem; font-size: .78rem; color: rgb(51 65 85); }
        .legend-item { display: inline-flex; gap: .36rem; align-items: center; }
        .dot { width: .72rem; height: .72rem; border-radius: 999px; border: 1px solid rgb(148 163 184); display: inline-block; }

        .bill-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding: 1rem;
            background: rgb(15 23 42 / .45);
            backdrop-filter: blur(4px);
        }
        @media (min-width: 640px) {
            .bill-overlay { align-items: center; }
        }
        .bill-panel {
            width: 100%;
            max-width: 420px;
            max-height: min(90vh, 640px);
            overflow: auto;
            border-radius: 18px;
            background: rgb(255 255 255);
            border: 1px solid rgb(226 232 240);
            box-shadow: 0 24px 48px -24px rgb(15 23 42 / .5);
        }
        .dark .bill-panel {
            background: rgb(30 41 59);
            border-color: rgb(51 65 85);
        }
        .bill-panel-head {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            padding: 1rem 1rem .75rem;
            border-bottom: 1px solid rgb(241 245 249);
        }
        .dark .bill-panel-head { border-color: rgb(51 65 85); }
        .bill-kicker { font-size: .68rem; text-transform: uppercase; letter-spacing: .1em; color: rgb(100 116 139); font-weight: 700; margin: 0; }
        .bill-title { font-size: 1.15rem; font-weight: 800; color: rgb(15 23 42); margin: .15rem 0 0; }
        .dark .bill-title { color: rgb(248 250 252); }
        .bill-meta { font-size: .8rem; color: rgb(71 85 105); margin: .25rem 0 0; }
        .bill-close {
            flex-shrink: 0;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 10px;
            border: 1px solid rgb(226 232 240);
            background: rgb(248 250 252);
            font-size: 1.35rem;
            line-height: 1;
            cursor: pointer;
            color: rgb(51 65 85);
        }
        .dark .bill-close { background: rgb(51 65 85); border-color: rgb(71 85 105); color: rgb(226 232 240); }
        .bill-lines { padding: .75rem 1rem; }
        .bill-section-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: rgb(100 116 139); margin: 0 0 .5rem; }
        .bill-line-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .45rem; }
        .bill-line { display: flex; justify-content: space-between; gap: .75rem; font-size: .88rem; color: rgb(30 41 59); }
        .dark .bill-line { color: rgb(226 232 240); }
        .bill-line-name { flex: 1; text-align: left; }
        .bill-line-price { font-weight: 600; font-variant-numeric: tabular-nums; }
        .bill-subtotal {
            display: flex;
            justify-content: space-between;
            margin-top: .75rem;
            padding-top: .65rem;
            border-top: 1px dashed rgb(203 213 225);
            font-weight: 700;
            font-size: .95rem;
        }
        .bill-form { padding: 0 1rem 1rem; display: grid; gap: .75rem; }
        .bill-field { display: grid; gap: .3rem; font-size: .78rem; font-weight: 600; color: rgb(71 85 105); }
        .bill-input {
            border-radius: 10px;
            border: 1px solid rgb(203 213 225);
            padding: .55rem .65rem;
            font-size: 1rem;
            width: 100%;
            background: rgb(255 255 255);
            color: rgb(15 23 42);
        }
        .dark .bill-input { background: rgb(15 23 42); border-color: rgb(71 85 105); color: rgb(248 250 252); }
        .bill-net {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .65rem .75rem;
            border-radius: 12px;
            background: rgb(254 252 232);
            border: 1px solid rgb(253 224 71);
            font-size: .9rem;
        }
        .dark .bill-net { background: rgb(66 32 6); border-color: rgb(202 138 4); color: rgb(254 249 195); }
        .bill-net strong { font-size: 1.15rem; font-variant-numeric: tabular-nums; }
        .bill-primary {
            border: none;
            border-radius: 12px;
            padding: .85rem 1rem;
            font-weight: 700;
            font-size: .95rem;
            cursor: pointer;
            background: rgb(245 158 11);
            color: rgb(15 23 42);
        }
        .bill-primary:hover { filter: brightness(1.05); }
        .bill-secondary {
            margin-top: .5rem;
            border-radius: 10px;
            border: 1px solid rgb(203 213 225);
            padding: .65rem 1rem;
            font-weight: 600;
            font-size: .88rem;
            cursor: pointer;
            background: rgb(248 250 252);
            color: rgb(51 65 85);
        }
        .bill-empty { padding: 1rem 1.25rem 1.25rem; text-align: center; color: rgb(71 85 105); font-size: .9rem; }
        .bill-no-perm { padding: 0 1rem 1rem; font-size: .85rem; color: rgb(180 83 9); }
    </style>
</x-filament-panels::page>
