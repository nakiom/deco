<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Deslizá cada fila hacia la derecha para marcar como atendido, o usá el botón Listo. Podés deshacer la última acción si te equivocás.
            </p>
            <button
                type="button"
                wire:click="undoLast"
                wire:loading.attr="disabled"
                class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-x-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition duration-75 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                <span wire:loading.remove wire:target="undoLast">Deshacer último</span>
                <span wire:loading wire:target="undoLast">…</span>
            </button>
        </div>

        <div class="space-y-3">
            @foreach ($this->getBarItems() as $item)
                <div
                    wire:key="bar-item-{{ $item->id }}"
                    x-data="{
                        startX: 0,
                        startY: 0,
                        x: 0,
                        dragging: false,
                        threshold: 72,
                        reset() { this.x = 0; this.dragging = false; },
                    }"
                    @touchstart.passive="startX = $event.touches[0].clientX; startY = $event.touches[0].clientY; dragging = true; x = 0"
                    @touchmove="if (! dragging) return;
                        const dx = $event.touches[0].clientX - startX;
                        const dy = $event.touches[0].clientY - startY;
                        if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 8) { $event.preventDefault(); }
                        x = Math.max(0, dx);"
                    @touchend="if (dragging && x > threshold) { $event.preventDefault(); $event.stopPropagation(); $wire.markReady({{ $item->id }}) } reset()"
                    @touchcancel="reset()"
                    class="relative select-none"
                    style="touch-action: pan-y; overscroll-behavior-x: contain;"
                >
                    <div
                        class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 transition-[transform,box-shadow] dark:border-gray-700 dark:bg-gray-800/50"
                        :style="`transform: translateX(${Math.min(x, 96)}px)`"
                        :class="x > 40 ? 'ring-2 ring-emerald-500/40 shadow-md' : ''"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-lg font-bold text-amber-600 dark:text-amber-400">
                                    Mesa {{ $item->order?->table?->number ?? '-' }}
                                </span>
                                <span class="text-lg font-semibold">{{ $item->product->name }}</span>
                                <span class="text-gray-500 dark:text-gray-400">×{{ $item->quantity }}</span>
                            </div>
                            @if($item->notes)
                                <p class="mt-1 text-sm text-amber-600 dark:text-amber-400">Nota: {{ $item->notes }}</p>
                            @endif
                            <p class="mt-1 text-xs text-gray-500">{{ $item->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-2">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
                                    {{ $item->status->value === 'pending' ? 'bg-gray-200 dark:bg-gray-700' : '' }}
                                    {{ $item->status->value === 'sent' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                    {{ $item->status->value === 'preparing' ? 'bg-amber-100 text-amber-900 dark:bg-amber-900/30 dark:text-amber-200' : '' }}"
                            >
                                {{ $item->status->label() }}
                            </span>
                            <button
                                type="button"
                                wire:click="markReady({{ $item->id }})"
                                wire:loading.attr="disabled"
                                class="inline-flex rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 dark:bg-emerald-600"
                            >
                                Listo
                            </button>
                        </div>
                    </div>
                    <p class="mt-1 text-center text-[10px] uppercase tracking-wide text-gray-400 sm:hidden">
                        Deslizá → para atendido
                    </p>
                </div>
            @endforeach
        </div>

        @if($this->getBarItems()->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 py-14 text-center text-gray-500 dark:border-gray-600 dark:text-gray-400">
                <x-heroicon-o-beaker class="mx-auto mb-3 h-14 w-14 opacity-40" />
                <p class="text-lg font-medium">No hay pedidos en barra</p>
            </div>
        @endif

        <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Historial reciente (listo)</h3>
            <ul class="space-y-2 text-sm">
                @forelse ($this->getRecentHistoryBar() as $action)
                    <li class="flex flex-wrap items-baseline justify-between gap-2 rounded-lg bg-gray-100/80 px-3 py-2 dark:bg-gray-800/80">
                        <span class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $action->orderItem?->product?->name ?? 'Ítem' }}
                            <span class="font-normal text-gray-500">×{{ $action->orderItem?->quantity ?? 1 }}</span>
                        </span>
                        <span class="text-xs text-gray-500">
                            Mesa {{ $action->orderItem?->order?->table?->number ?? '—' }}
                            · {{ $action->created_at->format('H:i') }}
                            @if($action->user)
                                · {{ $action->user->name }}
                            @endif
                        </span>
                    </li>
                @empty
                    <li class="text-gray-500 dark:text-gray-400">Todavía no hay ítems marcados en esta sesión.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-filament-panels::page>
