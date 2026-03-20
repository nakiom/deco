<x-filament-panels::page>
    @php
        $currency = config('deco.currency_symbol', '$');
    @endphp

    <div class="mx-auto max-w-3xl space-y-6 pb-36">
        {{-- Mesa: solo botones --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Mesa</h2>
            @if($this->tables->isEmpty())
                <p class="text-sm text-gray-600 dark:text-gray-300">No hay mesas cargadas.</p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach($this->tables as $t)
                        <button
                            type="button"
                            wire:click="selectTable({{ $t->id }})"
                            wire:key="table-{{ $t->id }}"
                            @class([
                                'min-h-[52px] min-w-[72px] rounded-xl px-4 py-3 text-base font-semibold transition',
                                'bg-primary-600 text-white shadow-md ring-2 ring-primary-400 ring-offset-2 dark:ring-offset-gray-900' => (int) $tableId === (int) $t->id,
                                'bg-gray-100 text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700' => (int) $tableId !== (int) $t->id,
                            ])
                        >
                            {{ $t->display_name }}
                        </button>
                    @endforeach
                    @if($tableId)
                        <button
                            type="button"
                            wire:click="clearTableSelection"
                            class="min-h-[52px] rounded-xl border border-dashed border-gray-300 px-4 py-3 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-400"
                        >
                            Quitar
                        </button>
                    @endif
                </div>
            @endif
        </section>

        {{-- Saltos a categorías (toda la carta) --}}
        @if($this->categories->isNotEmpty())
            <nav class="sticky top-0 z-20 -mx-1 flex gap-2 overflow-x-auto rounded-xl border border-gray-200 bg-white/95 p-2 pb-3 shadow-sm backdrop-blur dark:border-gray-700 dark:bg-gray-900/95">
                @foreach($this->categories as $cat)
                    <a
                        href="#cat-{{ $cat->id }}"
                        class="shrink-0 rounded-full bg-gray-100 px-4 py-3 text-sm font-medium text-gray-900 hover:bg-primary-100 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700"
                    >
                        {{ $cat->name }}
                    </a>
                @endforeach
            </nav>

            @foreach($this->categories as $category)
                <section id="cat-{{ $category->id }}" class="scroll-mt-28 space-y-3" wire:key="cat-{{ $category->id }}">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $category->name }}</h3>
                    <div class="space-y-3">
                        @foreach($category->products as $product)
                            @php
                                $inCart = isset($cart[$product->id]);
                                $qty = (int) ($cart[$product->id]['qty'] ?? 0);
                                $canAdd = $product->status->value === 'available'
                                    && (! $product->stock_control || $product->current_stock > 0);
                                $maxQty = $product->stock_control ? (int) $product->current_stock : 999;
                            @endphp
                            <div
                                class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                                wire:key="product-{{ $product->id }}"
                            >
                                <div class="flex gap-3">
                                    @if($product->image)
                                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-gray-100">
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image) }}" alt="" class="h-full w-full object-cover">
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $product->name }}</p>
                                            <p class="text-lg font-bold text-primary-600 dark:text-primary-400">
                                                {{ $currency }}{{ number_format((float) $product->price, 0, ',', '.') }}
                                            </p>
                                        </div>
                                        @if($product->short_description)
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $product->short_description }}</p>
                                        @endif
                                        <p class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-400">
                                            {{ $this->stockLabel($product) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    <div class="flex items-center gap-0">
                                        <button
                                            type="button"
                                            wire:click="removeProduct({{ $product->id }})"
                                            @disabled($qty < 1)
                                            class="flex h-14 min-w-[56px] items-center justify-center rounded-l-xl border border-gray-300 bg-gray-50 text-2xl font-bold text-gray-800 disabled:opacity-40 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                        >
                                            −
                                        </button>
                                        <div class="flex h-14 min-w-[3rem] items-center justify-center border-y border-gray-300 bg-white px-3 text-xl font-bold dark:border-gray-600 dark:bg-gray-950 dark:text-white">
                                            {{ $qty }}
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="addProduct({{ $product->id }})"
                                            @disabled(! $canAdd || $qty >= $maxQty)
                                            class="flex h-14 min-w-[56px] items-center justify-center rounded-r-xl border border-gray-300 bg-gray-50 text-2xl font-bold text-gray-800 disabled:opacity-40 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>

                                @if($qty > 0)
                                    <label class="mt-3 block">
                                        <span class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Notas (opcional)</span>
                                        <textarea
                                            wire:model.blur="cart.{{ $product->id }}.notes"
                                            rows="2"
                                            placeholder="Ej: sin cebolla, bien cocido…"
                                            class="w-full rounded-xl border-gray-300 text-base dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                        ></textarea>
                                    </label>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @else
            <p class="text-center text-gray-600 dark:text-gray-400">No hay categorías con productos disponibles.</p>
        @endif
    </div>

    {{-- Barra fija resumen + confirmar --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white/95 p-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-lg backdrop-blur dark:border-gray-700 dark:bg-gray-900/95">
        <div class="mx-auto flex max-w-3xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $this->cartLineCount }}</span> ítems
                    · Total <span class="font-bold text-primary-600 dark:text-primary-400">{{ $currency }}{{ number_format($this->cartTotal, 0, ',', '.') }}</span>
                </p>
                @if(! $tableId)
                    <p class="text-xs text-amber-700 dark:text-amber-400">Elegí una mesa arriba</p>
                @endif
            </div>
            <button
                type="button"
                wire:click="submitOrder"
                wire:loading.attr="disabled"
                class="min-h-[52px] w-full rounded-xl bg-primary-600 px-6 py-4 text-base font-bold text-white shadow-md hover:bg-primary-500 disabled:opacity-50 sm:w-auto"
            >
                <span wire:loading.remove wire:target="submitOrder">Confirmar pedido</span>
                <span wire:loading wire:target="submitOrder">Guardando…</span>
            </button>
        </div>
    </div>
</x-filament-panels::page>
