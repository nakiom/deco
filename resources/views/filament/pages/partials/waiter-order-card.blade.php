@php
    use App\Enums\OrderItemStatus;
    use App\Filament\Resources\Orders\OrderResource;
    /** @var \App\Models\Order $order */
    $context = $context ?? 'mine';
    $canAssign = $canAssign ?? false;
    $items = $order->items;
    $readyOrServed = $items->filter(fn ($i) => in_array($i->status, [
        OrderItemStatus::Ready,
        OrderItemStatus::Delivered,
    ], true))->count();
    $cancelled = $items->filter(fn ($i) => $i->status === OrderItemStatus::Cancelled)->count();
    $total = $items->count();
    $active = $total - $cancelled;
    $hasReadyToServe = $items->contains(fn ($i) => $i->status === OrderItemStatus::Ready);
    $showAssign = $context === 'salon' && $canAssign && (int) $order->waiter_id !== (int) auth()->id();
@endphp

<article class="waiter-order-card flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:ring-white/10 sm:rounded-2xl lg:rounded-3xl lg:shadow-lg xl:transition xl:hover:shadow-xl">
    <div class="border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white px-3 py-3 text-slate-900 dark:border-slate-600 dark:from-slate-800 dark:to-slate-900 sm:px-5 sm:py-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2 gap-y-1.5">
                    <span class="text-lg font-extrabold tracking-tight text-primary-600 dark:text-primary-400 sm:text-xl lg:text-2xl">
                        Mesa {{ $order->table?->display_name ?? $order->table?->number ?? '—' }}
                    </span>
                    <span
                        class="inline-flex max-w-full shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold sm:text-xs
                            {{ match ($order->status->value) {
                                'pending' => 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-200',
                                'sent', 'in_progress' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
                                'kitchen_done' => 'bg-violet-100 text-violet-900 dark:bg-violet-900/45 dark:text-violet-200',
                                'ready' => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-200',
                                'delivered' => 'bg-sky-100 text-sky-900 dark:bg-sky-900/40 dark:text-sky-200',
                                'closed', 'cancelled' => 'bg-slate-300 text-slate-800 dark:bg-slate-600 dark:text-slate-100',
                                default => 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100',
                            } }}"
                    >
                        {{ $order->status->label() }}
                    </span>
                </div>
                @if($order->waiter)
                    <p class="mt-1.5 truncate text-xs text-slate-500 dark:text-slate-400 lg:text-sm">
                        Mozo: <span class="font-medium text-slate-700 dark:text-slate-300">{{ $order->waiter->name }}</span>
                    </p>
                @endif
            </div>
            <div class="flex shrink-0 flex-row items-baseline justify-between gap-4 text-right sm:flex-col sm:items-end sm:gap-0.5">
                <p class="text-xl font-bold tabular-nums text-slate-900 dark:text-white lg:text-2xl">
                    {{ config('deco.currency_symbol') }}{{ number_format((float) $order->total, 0, ',', '.') }}
                </p>
                @if($order->opened_at)
                    <p class="text-[11px] font-medium text-slate-500 lg:text-xs">{{ $order->opened_at->format('H:i') }}</p>
                @endif
            </div>
        </div>

        @if($order->kitchen_completed_at || $order->status->value === 'kitchen_done')
            <div class="mt-3 flex items-center gap-2 rounded-xl border border-emerald-200/80 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-900 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-50 lg:text-sm">
                <x-heroicon-o-check-circle class="h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-300" />
                Cocina terminó — retirá y llevá a la mesa
            </div>
        @endif

        @if($active > 0)
            <p class="mt-2 text-xs text-slate-600 dark:text-slate-400 lg:text-sm">
                Listos / servidos: <span class="font-bold text-slate-900 dark:text-white">{{ $readyOrServed }}</span> / {{ $active }}
                @if($cancelled > 0)
                    <span class="text-slate-400">({{ $cancelled }} anul.)</span>
                @endif
            </p>
        @endif
    </div>

    <ul class="divide-y divide-slate-100 dark:divide-slate-700/80">
        @foreach ($items as $item)
            @php
                $st = $item->status;
                $isDone = in_array($st, [OrderItemStatus::Ready, OrderItemStatus::Delivered], true);
                $station = $item->target_station === 'kitchen' ? 'Cocina' : 'Bar';
                $rowClass = $isDone
                    ? 'bg-emerald-50/40 dark:bg-emerald-950/15'
                    : ($st === OrderItemStatus::Preparing || $st === OrderItemStatus::Sent
                        ? 'bg-amber-50/35 dark:bg-amber-950/12'
                        : '');
            @endphp
            <li class="flex gap-3 px-3 py-3 sm:px-5 lg:py-3.5 {{ $rowClass !== '' ? $rowClass : 'bg-white dark:bg-slate-900/80' }}">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold leading-snug text-slate-900 dark:text-slate-50 lg:text-base">
                        {{ $item->product?->name ?? 'Producto' }}
                        <span class="font-normal text-slate-500 dark:text-slate-400">×{{ $item->quantity }}</span>
                    </p>
                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                        <span class="rounded-md bg-slate-200/90 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                            {{ $station }}
                        </span>
                        <span
                            class="rounded-full px-2 py-0.5 text-[11px] font-semibold
                                {{ match ($st->value) {
                                    'pending' => 'bg-slate-200 text-slate-800 dark:bg-slate-600 dark:text-slate-100',
                                    'sent' => 'bg-blue-100 text-blue-900 dark:bg-blue-900/50 dark:text-blue-200',
                                    'preparing' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/50 dark:text-amber-200',
                                    'ready' => 'bg-emerald-200 text-emerald-900 dark:bg-emerald-900/50 dark:text-emerald-200',
                                    'delivered' => 'bg-sky-100 text-sky-900 dark:bg-sky-900/50 dark:text-sky-200',
                                    'cancelled' => 'bg-slate-300 text-slate-700 line-through dark:bg-slate-700 dark:text-slate-200',
                                    default => 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100',
                                } }}"
                        >
                            {{ $st->label() }}
                        </span>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>

    <div class="mt-auto grid gap-2 border-t border-slate-100 bg-slate-50/50 p-3 dark:border-slate-600 dark:bg-slate-900/80 sm:p-4 lg:grid-cols-2 lg:gap-3 lg:p-5">
        <a
            href="{{ OrderResource::getUrl('edit', ['record' => $order]) }}"
            class="flex min-h-[44px] items-center justify-center rounded-2xl bg-slate-900 px-4 text-sm font-bold text-white shadow-md transition hover:bg-slate-800 active:scale-[0.99] dark:bg-primary-600 dark:text-white dark:hover:bg-primary-500 lg:min-h-[48px] lg:text-base"
        >
            Ver / editar pedido
        </a>
        @if($showAssign)
            <button
                type="button"
                wire:click="takeOrder({{ $order->id }})"
                wire:loading.attr="disabled"
                class="flex min-h-[44px] items-center justify-center rounded-2xl border-2 border-primary-500 bg-white px-4 text-sm font-bold text-primary-700 transition hover:bg-primary-50 dark:border-primary-400 dark:bg-slate-900 dark:text-primary-300 dark:hover:bg-slate-800 lg:min-h-[48px] lg:text-base"
            >
                Asignarme este pedido
            </button>
        @endif
        @if($canAssign && $hasReadyToServe)
            <button
                type="button"
                wire:click="deliverReadyItems({{ $order->id }})"
                wire:loading.attr="disabled"
                class="flex min-h-[44px] items-center justify-center rounded-2xl bg-emerald-600 px-4 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700 active:scale-[0.99] lg:col-span-2 lg:min-h-[48px] lg:text-base"
            >
                Marcar “listos” como entregados en mesa
            </button>
        @endif
    </div>
</article>
