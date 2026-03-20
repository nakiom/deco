@php
    $stats = $this->getSalonStats();
    $calls = $this->getPendingTableCalls();
    $canDismissCall = auth()->user()?->can('tables.update');
    $canAssign = auth()->user()?->can('orders.update');
@endphp

<x-filament-panels::page>
    <div
        class="waiter-salon-ui mx-auto w-full max-w-[1600px] px-3 pb-10 pt-1 sm:px-5 lg:px-6 xl:px-8 [&_input]:text-slate-900 [&_input]:placeholder:text-slate-500 dark:[&_input]:text-white [&_select]:text-slate-900 dark:[&_select]:text-slate-100"
        wire:poll.8s
    >
        {{-- Barra superior: stats + búsqueda (desktop amplía) --}}
        <div class="waiter-salon-toolbar mb-4 space-y-3 lg:mb-6">
            <div class="grid grid-cols-2 gap-2 sm:gap-3 md:grid-cols-3 lg:grid-cols-5">
                <div class="rounded-2xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80 lg:p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Mis pedidos</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900 dark:text-white lg:text-3xl">{{ $stats['mine'] }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80 lg:p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Salón</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900 dark:text-white lg:text-3xl">{{ $stats['salon'] }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200/80 bg-amber-50/90 p-3 shadow-sm dark:border-amber-800/50 dark:bg-amber-950/40 lg:p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-800 dark:text-amber-200">Prioridad</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-amber-950 dark:text-amber-100 lg:text-3xl">{{ $stats['urgent'] }}</p>
                    <p class="mt-0.5 text-[10px] text-amber-800/80 dark:text-amber-200/80">Cocina lista</p>
                </div>
                <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/90 p-3 shadow-sm dark:border-emerald-800/50 dark:bg-emerald-950/40 lg:p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-200">Para retirar</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-950 dark:text-emerald-100 lg:text-3xl">{{ $stats['pickup'] }}</p>
                    <p class="mt-0.5 text-[10px] text-emerald-800/80 dark:text-emerald-200/80">Listos en barra</p>
                </div>
                <div class="col-span-2 rounded-2xl border border-violet-200/80 bg-violet-50/90 p-3 shadow-sm dark:border-violet-800/50 dark:bg-violet-950/40 md:col-span-1 lg:p-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-violet-800 dark:text-violet-200">Llamados mesa</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-violet-950 dark:text-violet-100 lg:text-3xl">{{ $stats['calls'] }}</p>
                    <p class="mt-0.5 text-[10px] text-violet-800/80 dark:text-violet-200/80">Desde carta QR</p>
                </div>
            </div>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-4">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    </span>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por número o nombre de mesa…"
                        aria-label="Buscar pedido por mesa"
                        autocomplete="off"
                        class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white lg:py-3.5 lg:text-base"
                    />
                </div>
                <div class="flex flex-wrap gap-2 lg:shrink-0">
                    <select
                        wire:model.live="filterPriority"
                        class="min-h-[44px] flex-1 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 lg:min-w-[200px] lg:flex-none lg:px-4 lg:text-base"
                    >
                        <option value="all">Todos los pedidos</option>
                        <option value="urgent">Solo prioridad (cocina)</option>
                        <option value="pickup">Solo para retirar</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="isolate lg:grid lg:grid-cols-[minmax(260px,320px)_minmax(0,1fr)] lg:items-start lg:gap-8 xl:grid-cols-[minmax(280px,360px)_minmax(0,1fr)] xl:gap-10">
            {{-- Sidebar desktop: sin sticky para evitar solaparse con el contenido --}}
            <aside class="mb-6 hidden lg:mb-0 lg:block lg:self-start">
                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-md dark:border-slate-700 dark:bg-slate-900/90">
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-3 dark:border-slate-700">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">Llamados de mesa</h2>
                        @if($stats['calls'] > 0)
                            <span class="rounded-full bg-violet-600 px-2 py-0.5 text-xs font-bold text-white">{{ $stats['calls'] }}</span>
                        @endif
                    </div>
                    @if($calls->isEmpty())
                        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Nadie llamó desde la carta QR.</p>
                    @else
                        <ul class="mt-3 max-h-[min(420px,50vh)] space-y-2 overflow-y-auto pr-1">
                            @foreach($calls as $t)
                                <li class="flex items-start justify-between gap-2 rounded-xl border border-violet-100 bg-violet-50/90 p-3 dark:border-violet-900/45 dark:bg-violet-950/40">
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white">Mesa {{ $t->display_name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $t->waiter_call_at?->diffForHumans() }}</p>
                                    </div>
                                    @if($canDismissCall)
                                        <button
                                            type="button"
                                            wire:click="dismissTableCall({{ $t->id }})"
                                            wire:loading.attr="disabled"
                                            class="shrink-0 rounded-lg bg-violet-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-violet-800"
                                        >
                                            Atendido
                                        </button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <p class="mt-3 text-center text-sm text-slate-500 dark:text-slate-400">Los clientes usan <strong>Llamar al mozo</strong> en la carta por QR.</p>
            </aside>

            {{-- Llamados móvil (compacto) --}}
            @if($stats['calls'] > 0)
                <div class="mb-4 rounded-2xl border border-violet-200 bg-violet-50 p-3 dark:border-violet-800 dark:bg-violet-950/50 lg:hidden">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-bold text-violet-950 dark:text-violet-100">{{ $stats['calls'] }} llamado(s) de mesa</p>
                    </div>
                    <ul class="mt-2 space-y-2">
                        @foreach($calls->take(4) as $t)
                            <li class="flex items-center justify-between gap-2 text-sm">
                                <span class="font-semibold text-slate-900 dark:text-white">Mesa {{ $t->display_name }}</span>
                                @if($canDismissCall)
                                    <button type="button" wire:click="dismissTableCall({{ $t->id }})" class="rounded-lg bg-violet-700 px-2 py-1 text-xs font-semibold text-white">OK</button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="relative z-0 min-w-0">
                {{-- Sin sticky: evita que las pestañas floten encima de las cards al hacer scroll --}}
                <div class="mb-4 rounded-2xl border border-slate-200 bg-slate-50/95 p-2 shadow-sm dark:border-slate-600 dark:bg-slate-800/90 sm:p-2">
                    <div class="grid grid-cols-2 gap-2 sm:gap-3">
                        <button
                            type="button"
                            wire:click="$set('tab', 'mine')"
                            @class([
                                'min-h-[48px] rounded-xl px-3 py-3 text-center text-sm font-bold transition sm:min-h-[52px] sm:text-base lg:py-4',
                                'bg-primary-600 text-white shadow-md ring-1 ring-primary-600/30 dark:bg-primary-500' => $this->tab === 'mine',
                                'border border-slate-200 bg-white text-slate-800 shadow-sm hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800' => $this->tab !== 'mine',
                            ])
                        >
                            Mis pedidos
                            <span class="mt-0.5 block text-[11px] font-normal opacity-90">Asignados a vos</span>
                        </button>
                        <button
                            type="button"
                            wire:click="$set('tab', 'salon')"
                            @class([
                                'min-h-[48px] rounded-xl px-3 py-3 text-center text-sm font-bold transition sm:min-h-[52px] sm:text-base lg:py-4',
                                'bg-primary-600 text-white shadow-md ring-1 ring-primary-600/30 dark:bg-primary-500' => $this->tab === 'salon',
                                'border border-slate-200 bg-white text-slate-800 shadow-sm hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800' => $this->tab !== 'salon',
                            ])
                        >
                            Todo el salón
                            <span class="mt-0.5 block text-[11px] font-normal opacity-90">Todos los mozos</span>
                        </button>
                    </div>
                </div>

                @if($this->tab === 'mine')
                    <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-2 lg:gap-6 xl:gap-8">
                        @forelse ($this->getMyOpenOrders() as $order)
                            @include('filament.pages.partials.waiter-order-card', [
                                'order' => $order,
                                'context' => 'mine',
                                'canAssign' => $canAssign,
                            ])
                        @empty
                            <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-20 text-center dark:border-slate-600 dark:bg-slate-800/40">
                                <p class="text-lg font-semibold text-slate-700 dark:text-slate-200">No tenés pedidos asignados</p>
                                <p class="mt-2 text-slate-500 dark:text-slate-400">Pasá a <strong>Todo el salón</strong> para tomar un pedido o abrí <strong>Nuevo pedido</strong> arriba.</p>
                            </div>
                        @endforelse
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-2 lg:gap-6 xl:gap-8">
                        @forelse ($this->getAllOpenOrders() as $order)
                            @include('filament.pages.partials.waiter-order-card', [
                                'order' => $order,
                                'context' => 'salon',
                                'canAssign' => $canAssign,
                            ])
                        @empty
                            <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-20 text-center dark:border-slate-600 dark:bg-slate-800/40">
                                <p class="text-lg font-semibold text-slate-700 dark:text-slate-200">No hay pedidos que coincidan</p>
                                <p class="mt-2 text-slate-500 dark:text-slate-400">Probá otro filtro o buscá otra mesa.</p>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
