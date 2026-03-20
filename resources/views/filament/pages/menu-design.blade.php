<x-filament-panels::page>
    <div class="space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-400 max-w-2xl">
            Cada local tiene su propia URL pública. Editá logo, fondos y textos en <strong>Restaurantes → Editar</strong> (sección «Carta digital»). Los platos y ofertas se gestionan en <strong>Productos</strong>.
        </p>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($this->getRestaurants() as $restaurant)
                <div
                    class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm flex flex-col gap-4"
                >
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $restaurant->name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">/carta/{{ $restaurant->slug }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-auto">
                        <a
                            href="{{ route('carta.show', ['slug' => $restaurant->slug]) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-primary fi-btn-size-sm fi-color-custom inline-flex gap-1.5 px-3 py-2 text-sm"
                        >
                            Ver carta
                        </a>
                        <a
                            href="{{ \App\Filament\Resources\Restaurants\RestaurantResource::getUrl('edit', ['record' => $restaurant]) }}"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-gray fi-btn-size-sm inline-flex gap-1.5 px-3 py-2 text-sm"
                        >
                            Diseño y datos
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        @if($this->getRestaurants()->isEmpty())
            <p class="text-sm text-gray-500">No hay restaurantes cargados.</p>
        @endif
    </div>
</x-filament-panels::page>
