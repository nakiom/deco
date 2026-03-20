@php
    use Illuminate\Support\Facades\Storage;
    $theme = $restaurant->resolvedMenuTheme();
    $accent = $theme['accent_color'] ?? '#c2410c';
    $bg = $theme['background'] ?? 'parchment';
    $fontPair = $theme['font_pair'] ?? 'classic';
    $cols = (int) ($theme['layout_columns'] ?? 2);
    $cols = $cols === 1 ? 1 : 2;
    $ribbonPreset = $theme['ribbon_preset'] ?? 'accent';
    $showCatImg = (bool) ($theme['show_category_images'] ?? true);

    $fontStacks = [
        'classic' => ['display' => "'Playfair Display', Georgia, serif", 'body' => "'DM Sans', system-ui, sans-serif"],
        'modern' => ['display' => "'Outfit', system-ui, sans-serif", 'body' => "'DM Sans', system-ui, sans-serif"],
        'elegant' => ['display' => "'Cormorant Garamond', Georgia, serif", 'body' => "'Lato', system-ui, sans-serif"],
    ];
    $fonts = $fontStacks[$fontPair] ?? $fontStacks['classic'];

    $dietary = config('deco.menu.dietary_tags', []);

    $logoUrl = $restaurant->menu_logo
        ? Storage::disk('public')->url($restaurant->menu_logo)
        : null;
    $headerUrl = $restaurant->menu_header_image
        ? Storage::disk('public')->url($restaurant->menu_header_image)
        : null;

    $productRibbonClasses = [
        'accent' => 'from-[color:var(--menu-accent)] to-orange-900',
        'gold' => 'from-amber-400 to-amber-700',
        'emerald' => 'from-emerald-500 to-emerald-800',
        'wine' => 'from-rose-900 to-rose-950',
        'slate' => 'from-slate-500 to-slate-800',
    ];
    $productRibbonGradient = $productRibbonClasses[$ribbonPreset] ?? $productRibbonClasses['accent'];

    $isDarkBg = in_array($bg, ['dark_bistro', 'chalk'], true);
    $cardBorder = $isDarkBg ? 'border-stone-700' : 'border-stone-200/80';
    $cardBg = $isDarkBg ? 'bg-stone-900/75' : 'bg-white/85';
    $tagPill = $isDarkBg ? 'bg-stone-800 text-stone-200' : 'bg-stone-100 text-stone-700';
    $tagPillLoose = $isDarkBg ? 'bg-stone-800 text-stone-300' : 'bg-stone-100 text-stone-600';
    $tableBanner = $isDarkBg ? 'border-stone-600 bg-stone-900/80' : 'border-stone-200/60 bg-white/70';
    $footerBorder = $isDarkBg ? 'border-stone-700' : 'border-stone-200/80';

    $menuOrderingEnabled = $menuOrderingEnabled ?? false;
    $menuOrderSubmitUrl = $menuOrderSubmitUrl ?? null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $restaurant->name }} — Carta</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;1,9..40,400&family=Lato:wght@400;700&family=Outfit:wght@500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    {{-- Sin @vite: la carta debe verse sin depender de public/build/manifest.json (npm run build). --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --menu-accent: {{ $accent }};
            --font-display: {!! $fonts['display'] !!};
            --font-body: {!! $fonts['body'] !!};
        }
        .menu-bg-parchment {
            background-color: #f5f0e6;
            background-image:
                radial-gradient(ellipse 120% 80% at 50% -20%, rgba(255,255,255,.85), transparent 55%),
                url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.06'/%3E%3C/svg%3E");
        }
        .menu-bg-linen {
            background-color: #ebe6dc;
            background-image:
                repeating-linear-gradient(90deg, rgba(0,0,0,.02) 0px, transparent 1px, transparent 3px),
                repeating-linear-gradient(0deg, rgba(0,0,0,.02) 0px, transparent 1px, transparent 3px);
        }
        .menu-bg-marble {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 45%, #f1f5f9 100%);
            background-image:
                radial-gradient(circle at 20% 30%, rgba(255,255,255,.9) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(148,163,184,.15) 0%, transparent 45%);
        }
        .menu-bg-dark_bistro {
            background-color: #1c1917;
            background-image:
                radial-gradient(ellipse at top, rgba(251,191,36,.08), transparent 50%),
                url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.05'/%3E%3C/svg%3E");
        }
        .menu-bg-cork {
            background-color: #c4a574;
            background-image:
                radial-gradient(circle at 30% 40%, rgba(255,255,255,.12) 0%, transparent 25%),
                radial-gradient(circle at 70% 60%, rgba(0,0,0,.06) 0%, transparent 30%);
        }
        .menu-bg-chalk {
            background-color: #292524;
            background-image:
                repeating-linear-gradient(0deg, rgba(255,255,255,.03) 0px, transparent 2px, transparent 4px);
        }
        .menu-bg-minimal {
            background: linear-gradient(180deg, #fafafa 0%, #f4f4f5 100%);
        }
        .menu-text-main { color: #1c1917; }
        .menu-bg-dark_bistro .menu-text-main,
        .menu-bg-chalk .menu-text-main { color: #fafaf9; }
        .menu-text-muted { color: #57534e; }
        .menu-bg-dark_bistro .menu-text-muted,
        .menu-bg-chalk .menu-text-muted { color: #a8a29e; }
        .menu-price { font-variant-numeric: tabular-nums; }
        .ribbon-corner {
            position: absolute; top: 0; right: 0; width: 5rem; height: 5rem; overflow: hidden; pointer-events: none;
        }
        .ribbon-corner span {
            position: absolute; display: block; width: 9rem; padding: 0.35rem 0;
            text-align: center; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
            right: -2.2rem; top: 1rem; transform: rotate(45deg);
            box-shadow: 0 2px 8px rgba(0,0,0,.12);
        }
    </style>
</head>
<body class="menu-bg-{{ $bg }} min-h-screen antialiased menu-text-main" style="font-family: var(--font-body);">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
        @if(isset($table))
            <div class="mb-6 rounded-2xl border backdrop-blur px-4 py-3 text-center text-sm {{ $tableBanner }}">
                <span class="menu-text-muted">Estás viendo la carta de</span>
                <span class="font-semibold" style="font-family: var(--font-display);">{{ $restaurant->name }}</span>
                <span class="menu-text-muted"> · Mesa</span>
                <span class="font-semibold">{{ $table->display_name ?? $table->number }}</span>
            </div>
        @endif

        @if($menuOrderingEnabled && $menuOrderSubmitUrl && isset($table))
            <div class="mb-5 rounded-2xl border px-4 py-3 text-sm {{ $tableBanner }}">
                <label for="deco-participant-name" class="block text-left font-medium menu-text-muted mb-1">Tu nombre (para dividir la cuenta)</label>
                <input
                    type="text"
                    id="deco-participant-name"
                    maxlength="120"
                    placeholder="Ej. Ana"
                    class="w-full rounded-xl border {{ $cardBorder }} bg-white/90 dark:bg-stone-950/50 px-3 py-2 text-stone-900 dark:text-stone-100"
                />
                <p class="mt-1.5 text-xs menu-text-muted text-left">Se guarda en este dispositivo. Agregá platos al pedido y enviá todo junto a cocina cuando confirmes.</p>
            </div>
        @endif

        @if(isset($table) && !empty($menuQrKitchenStatusUrl))
            @php
                $ksUrl = $menuQrKitchenStatusUrl;
            @endphp
            <div
                id="deco-kitchen-ready-banner"
                class="mb-6 hidden rounded-2xl border border-emerald-600/30 bg-emerald-500/15 px-4 py-3 text-center text-sm text-emerald-950 shadow-sm dark:border-emerald-500/25 dark:bg-emerald-950/50 dark:text-emerald-50"
                role="status"
            >
                <p class="font-semibold" style="font-family: var(--font-display);">¡Tu pedido ya está listo en cocina!</p>
                <p class="mt-1 text-xs opacity-90">El mozo puede traerlo en cualquier momento.</p>
                <button type="button" id="deco-kitchen-ready-dismiss" class="mt-2 text-xs font-semibold underline underline-offset-2 text-emerald-900 dark:text-emerald-200">
                    Entendido
                </button>
            </div>
            <script>
                (function () {
                    const url = @json($ksUrl);
                    let activeOrderId = @json($kitchenReadyOrderId ?? null);
                    const banner = document.getElementById('deco-kitchen-ready-banner');
                    const dismissBtn = document.getElementById('deco-kitchen-ready-dismiss');
                    function key() {
                        return activeOrderId ? 'deco_kitchen_ready_dismiss_' + activeOrderId : null;
                    }
                    function isDismissed() {
                        return key() && localStorage.getItem(key());
                    }
                    function showBanner() {
                        if (!banner || isDismissed()) return;
                        banner.classList.remove('hidden');
                    }
                    function hideBanner() {
                        banner?.classList.add('hidden');
                    }
                    if (@json($kitchenReadyInitial ?? false)) {
                        showBanner();
                    }
                    dismissBtn?.addEventListener('click', function () {
                        if (activeOrderId) {
                            localStorage.setItem('deco_kitchen_ready_dismiss_' + activeOrderId, '1');
                        }
                        hideBanner();
                    });
                    setInterval(async function () {
                        try {
                            const r = await fetch(url, { headers: { Accept: 'application/json' } });
                            const j = await r.json();
                            if (!j.kitchen_ready) {
                                hideBanner();
                                return;
                            }
                            if (j.order_id) {
                                activeOrderId = j.order_id;
                            }
                            showBanner();
                        } catch (e) {}
                    }, 12000);
                })();
            </script>
        @endif

        @if(isset($table) && session('waiter_call_feedback'))
            <div class="mb-4 rounded-2xl border px-4 py-3 text-center text-sm {{ $isDarkBg ? 'border-amber-500/40 bg-amber-950/50 text-amber-100' : 'border-amber-200 bg-amber-50 text-amber-950' }}">
                {{ session('waiter_call_feedback') }}
            </div>
        @endif

        @if(isset($table) && ((!empty($menuQrCallWaiterUrl)) || ($menuOrderingEnabled && $menuOrderSubmitUrl)))
            <div
                class="fixed bottom-0 left-0 right-0 z-50 flex flex-col border-black/10 shadow-[0_-8px_30px_rgba(0,0,0,.08)] backdrop-blur-md dark:border-white/10"
                style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom, 0px));"
            >
                @if($menuOrderingEnabled && $menuOrderSubmitUrl)
                    <div
                        id="deco-cart-dock"
                        class="hidden border-t border-black/10 bg-white/98 px-4 py-3 dark:border-white/10 dark:bg-stone-900/98 sm:px-6"
                    >
                        <div class="mx-auto max-w-lg">
                            <p class="text-xs font-semibold text-stone-600 dark:text-stone-300">
                                Pedido pendiente de envío · <span id="deco-cart-count">0</span> ítem(s)
                            </p>
                            <ul id="deco-cart-lines" class="mt-2 max-h-36 space-y-2 overflow-y-auto text-sm text-stone-800 dark:text-stone-200"></ul>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    id="deco-cart-clear"
                                    class="rounded-xl border border-stone-300 px-3 py-2 text-xs font-semibold text-stone-700 dark:border-stone-600 dark:text-stone-200"
                                >
                                    Vaciar
                                </button>
                                <button
                                    type="button"
                                    id="deco-cart-submit"
                                    class="min-w-0 flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:brightness-110"
                                    style="background: var(--menu-accent);"
                                >
                                    Enviar pedido a cocina
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                @if(!empty($menuQrCallWaiterUrl))
                    <div class="border-t border-black/10 bg-white/95 px-4 py-3 dark:border-white/10 dark:bg-stone-900/95 sm:px-6">
                        <form method="post" action="{{ $menuQrCallWaiterUrl }}" class="mx-auto flex max-w-lg justify-center">
                            @csrf
                            <button
                                type="submit"
                                class="flex w-full touch-manipulation items-center justify-center gap-2 rounded-2xl px-4 py-3.5 text-sm font-semibold text-white shadow-lg transition hover:brightness-110 active:scale-[0.99] sm:text-base"
                                style="background: var(--menu-accent);"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 opacity-95" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                                Llamar al mozo
                            </button>
                        </form>
                    </div>
                @endif
            </div>
            <div class="h-40 shrink-0" aria-hidden="true"></div>
        @endif

        @if($menuOrderingEnabled && $menuOrderSubmitUrl && isset($table))
            <dialog id="deco-menu-order-dialog" class="max-w-md w-[calc(100%-2rem)] rounded-2xl border border-stone-200 bg-white p-0 shadow-2xl backdrop:bg-black/40">
                <form method="dialog" class="border-b border-stone-100 px-4 py-3 flex justify-between items-center">
                    <h2 id="deco-order-dialog-title" class="text-base font-semibold text-stone-900" style="font-family: var(--font-display);">Agregar al pedido</h2>
                    <button type="submit" value="cancel" class="text-stone-500 text-xl leading-none" aria-label="Cerrar">×</button>
                </form>
                <div class="px-4 py-3 space-y-3 text-sm text-stone-800">
                    <p class="text-stone-600 text-xs" id="deco-order-product-title"></p>
                    <div class="block">
                        <span class="text-xs font-medium text-stone-600">Cantidad</span>
                        <div class="mt-1 flex max-w-[12rem] items-center gap-2">
                            <button
                                type="button"
                                id="deco-qty-minus"
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-stone-300 text-lg font-semibold text-stone-700 active:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:active:bg-stone-800"
                                aria-label="Menos"
                            >−</button>
                            <input
                                type="number"
                                id="deco-order-qty"
                                inputmode="numeric"
                                min="1"
                                max="99"
                                value="1"
                                class="min-w-0 flex-1 rounded-lg border border-stone-200 px-2 py-2 text-center text-base font-semibold tabular-nums dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100"
                            />
                            <button
                                type="button"
                                id="deco-qty-plus"
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-stone-300 text-lg font-semibold text-stone-700 active:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:active:bg-stone-800"
                                aria-label="Más"
                            >+</button>
                        </div>
                    </div>
                    <label class="block">
                        <span class="text-xs font-medium text-stone-600">Notas para cocina o barra <span class="font-normal text-stone-400">(opcional)</span></span>
                        <textarea
                            id="deco-order-notes"
                            rows="2"
                            maxlength="500"
                            placeholder="Ej. sin cebolla, bien cocido…"
                            class="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2 text-sm placeholder:text-stone-400 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100"
                        ></textarea>
                    </label>
                    <fieldset class="space-y-2">
                        <legend class="text-xs font-medium text-stone-600">Tipo</legend>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="deco-split-mode" value="individual" checked class="accent-orange-600" />
                            <span>Para mí (nombre abajo)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="deco-split-mode" value="shared_equal" class="accent-orange-600" />
                            <span>Compartir en partes iguales</span>
                        </label>
                    </fieldset>
                    <label class="block" id="deco-order-participant-wrap">
                        <span class="text-xs font-medium text-stone-600">Nombre (este consumo)</span>
                        <input type="text" id="deco-order-participant" maxlength="120" class="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2" />
                    </label>
                    <label class="block hidden" id="deco-order-shared-wrap">
                        <span class="text-xs font-medium text-stone-600">Entre quiénes se divide (coma o salto de línea, mínimo 2)</span>
                        <textarea id="deco-order-shared" rows="3" class="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2" placeholder="Juan, María, Pedro"></textarea>
                    </label>
                </div>
                <div class="px-4 py-3 border-t border-stone-100 flex gap-2 justify-end">
                    <button type="button" id="deco-order-cancel" class="rounded-xl px-4 py-2 text-sm font-medium text-stone-600 border border-stone-200">Cancelar</button>
                    <button type="button" id="deco-order-add-cart" class="rounded-xl px-4 py-2 text-sm font-semibold text-white" style="background: var(--menu-accent);">Agregar al pedido</button>
                </div>
            </dialog>
            <div id="deco-menu-toast" class="fixed bottom-44 left-1/2 z-[60] hidden max-w-sm -translate-x-1/2 rounded-xl border border-emerald-600/30 bg-emerald-50 px-4 py-3 text-sm text-emerald-950 shadow-lg" role="status"></div>
            <script>
                (function () {
                    const submitUrl = @json($menuOrderSubmitUrl);
                    const storageKey = 'deco_menu_participant_{{ $table->id }}';
                    const nameInput = document.getElementById('deco-participant-name');
                    const dialog = document.getElementById('deco-menu-order-dialog');
                    const toast = document.getElementById('deco-menu-toast');
                    const cartDock = document.getElementById('deco-cart-dock');
                    const cartLinesEl = document.getElementById('deco-cart-lines');
                    const cartCountEl = document.getElementById('deco-cart-count');
                    if (!submitUrl || !dialog || !nameInput) return;

                    /** @type {Array<{id:string,product_id:number,product_name:string,quantity:number,split_mode:string,participant_label:string|null,shared_with_labels:string[],notes:string}>} */
                    let decoCart = [];

                    function clampQty(n) {
                        n = parseInt(String(n), 10);
                        if (Number.isNaN(n) || n < 1) return 1;
                        if (n > 99) return 99;
                        return n;
                    }

                    function bindQtySteppers() {
                        const inp = document.getElementById('deco-order-qty');
                        const minus = document.getElementById('deco-qty-minus');
                        const plus = document.getElementById('deco-qty-plus');
                        if (!inp || !minus || !plus) return;
                        minus.addEventListener('click', function () {
                            inp.value = String(clampQty((parseInt(inp.value, 10) || 1) - 1));
                        });
                        plus.addEventListener('click', function () {
                            inp.value = String(clampQty((parseInt(inp.value, 10) || 1) + 1));
                        });
                        inp.addEventListener('change', function () {
                            inp.value = String(clampQty(inp.value));
                        });
                    }
                    bindQtySteppers();

                    function cartLineCount() {
                        return decoCart.reduce(function (n, l) { return n + l.quantity; }, 0);
                    }

                    function renderCart() {
                        if (!cartDock || !cartLinesEl || !cartCountEl) return;
                        const n = cartLineCount();
                        cartCountEl.textContent = String(n);
                        if (decoCart.length === 0) {
                            cartDock.classList.add('hidden');
                            cartLinesEl.innerHTML = '';
                            return;
                        }
                        cartDock.classList.remove('hidden');
                        cartLinesEl.innerHTML = '';
                        decoCart.forEach(function (line) {
                            const li = document.createElement('li');
                            li.className = 'flex items-start justify-between gap-2 rounded-lg border border-stone-200/80 bg-white/60 px-2 py-1.5 dark:border-stone-600 dark:bg-stone-800/60';
                            const left = document.createElement('div');
                            left.className = 'min-w-0 flex-1';
                            const title = document.createElement('div');
                            title.className = 'font-medium text-stone-900 dark:text-stone-100';
                            title.textContent = line.product_name + ' × ' + line.quantity;
                            const sub = document.createElement('div');
                            sub.className = 'text-xs text-stone-500 dark:text-stone-400';
                            if (line.split_mode === 'shared_equal') {
                                sub.textContent = 'Compartido: ' + (line.shared_with_labels || []).join(', ');
                            } else {
                                sub.textContent = line.participant_label || '—';
                            }
                            left.appendChild(title);
                            left.appendChild(sub);
                            if (line.notes && line.notes.trim() !== '') {
                                const noteEl = document.createElement('div');
                                noteEl.className = 'mt-1 text-xs italic text-stone-600 dark:text-stone-400';
                                noteEl.textContent = 'Nota: ' + line.notes.trim();
                                left.appendChild(noteEl);
                            }
                            const rm = document.createElement('button');
                            rm.type = 'button';
                            rm.className = 'shrink-0 text-xs font-semibold text-red-600 dark:text-red-400';
                            rm.textContent = 'Quitar';
                            rm.setAttribute('data-cart-id', line.id);
                            rm.addEventListener('click', function () {
                                decoCart = decoCart.filter(function (x) { return x.id !== line.id; });
                                renderCart();
                            });
                            li.appendChild(left);
                            li.appendChild(rm);
                            cartLinesEl.appendChild(li);
                        });
                    }

                    try {
                        const saved = localStorage.getItem(storageKey);
                        if (saved) nameInput.value = saved;
                    } catch (e) {}
                    nameInput.addEventListener('change', function () {
                        try { localStorage.setItem(storageKey, nameInput.value.trim()); } catch (e) {}
                    });

                    let currentProductId = null;
                    document.querySelectorAll('.menu-deco-add').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            currentProductId = parseInt(btn.getAttribute('data-product-id'), 10);
                            const title = btn.getAttribute('data-product-name') || '';
                            document.getElementById('deco-order-product-title').textContent = title;
                            document.getElementById('deco-order-qty').value = '1';
                            const notesEl = document.getElementById('deco-order-notes');
                            if (notesEl) notesEl.value = '';
                            document.querySelector('input[name="deco-split-mode"][value="individual"]').checked = true;
                            document.getElementById('deco-order-participant').value = nameInput.value.trim();
                            document.getElementById('deco-order-shared').value = '';
                            document.getElementById('deco-order-participant-wrap').classList.remove('hidden');
                            document.getElementById('deco-order-shared-wrap').classList.add('hidden');
                            dialog.showModal();
                        });
                    });
                    document.querySelectorAll('input[name="deco-split-mode"]').forEach(function (r) {
                        r.addEventListener('change', function () {
                            const shared = document.querySelector('input[name="deco-split-mode"]:checked').value === 'shared_equal';
                            document.getElementById('deco-order-participant-wrap').classList.toggle('hidden', shared);
                            document.getElementById('deco-order-shared-wrap').classList.toggle('hidden', !shared);
                        });
                    });
                    document.getElementById('deco-order-cancel').addEventListener('click', function () { dialog.close(); });
                    document.getElementById('deco-order-add-cart').addEventListener('click', function () {
                        const qty = clampQty(document.getElementById('deco-order-qty').value);
                        document.getElementById('deco-order-qty').value = String(qty);
                        const notesRaw = (document.getElementById('deco-order-notes') && document.getElementById('deco-order-notes').value) || '';
                        const notesTrim = notesRaw.trim().slice(0, 500);
                        const mode = document.querySelector('input[name="deco-split-mode"]:checked').value;
                        const participantField = document.getElementById('deco-order-participant').value.trim();
                        const topName = nameInput.value.trim();
                        let sharedLabels = [];
                        if (mode === 'shared_equal') {
                            const raw = document.getElementById('deco-order-shared').value;
                            sharedLabels = raw.split(/[\n,;]+/).map(function (s) { return s.trim(); }).filter(Boolean);
                        }
                        if (mode === 'individual' && !participantField) {
                            alert('Indicá un nombre para este consumo.');
                            return;
                        }
                        if (mode === 'shared_equal' && sharedLabels.length < 2) {
                            alert('Para compartir, escribí al menos dos nombres.');
                            return;
                        }
                        const productName = document.getElementById('deco-order-product-title').textContent || 'Producto';
                        decoCart.push({
                            id: 'c' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
                            product_id: currentProductId,
                            product_name: productName,
                            quantity: qty,
                            split_mode: mode,
                            participant_label: mode === 'individual' ? participantField : (topName || null),
                            shared_with_labels: mode === 'shared_equal' ? sharedLabels : [],
                            notes: notesTrim
                        });
                        try { localStorage.setItem(storageKey, nameInput.value.trim()); } catch (e) {}
                        renderCart();
                        dialog.close();
                    });

                    document.getElementById('deco-cart-clear')?.addEventListener('click', function () {
                        if (decoCart.length === 0) return;
                        if (confirm('¿Vaciar el pedido?')) {
                            decoCart = [];
                            renderCart();
                        }
                    });

                    document.getElementById('deco-cart-submit')?.addEventListener('click', async function () {
                        if (decoCart.length === 0) {
                            alert('Agregá algo al pedido primero.');
                            return;
                        }
                        const lines = decoCart.map(function (l) {
                            return {
                                product_id: l.product_id,
                                quantity: l.quantity,
                                split_mode: l.split_mode,
                                participant_label: l.participant_label,
                                shared_with_labels: l.shared_with_labels,
                                notes: l.notes && l.notes.trim() !== '' ? l.notes.trim() : null
                            };
                        });
                        try {
                            const res = await fetch(submitUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({ lines: lines })
                            });
                            const data = await res.json().catch(function () { return {}; });
                            if (!res.ok) {
                                alert(data.message || 'No se pudo enviar.');
                                return;
                            }
                            decoCart = [];
                            renderCart();
                            if (toast) {
                                toast.textContent = data.message || 'Pedido enviado.';
                                toast.classList.remove('hidden');
                                setTimeout(function () { toast.classList.add('hidden'); }, 5000);
                            }
                        } catch (e) {
                            alert('Error de red. Intentá de nuevo.');
                        }
                    });
                })();
            </script>
        @endif

        <header class="text-center mb-10 sm:mb-14">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $restaurant->name }}" class="mx-auto h-16 sm:h-20 w-auto object-contain mb-6 drop-shadow-sm">
            @endif
            @if($headerUrl)
                <div class="rounded-2xl overflow-hidden shadow-lg ring-1 ring-black/5 mb-8 max-h-56 sm:max-h-72">
                    <img src="{{ $headerUrl }}" alt="" class="w-full h-full object-cover max-h-56 sm:max-h-72">
                </div>
            @endif
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-semibold tracking-tight" style="font-family: var(--font-display); color: var(--menu-accent);">
                {{ $restaurant->name }}
            </h1>
            @if(!empty($theme['tagline']))
                <p class="mt-3 text-lg sm:text-xl menu-text-muted max-w-2xl mx-auto leading-relaxed" style="font-family: var(--font-display);">
                    {{ $theme['tagline'] }}
                </p>
            @endif
            @if($restaurant->address)
                <p class="mt-2 text-sm menu-text-muted">{{ $restaurant->address }}</p>
            @endif
        </header>

        <div class="space-y-14 sm:space-y-16">
            @foreach($categories as $category)
                <section class="scroll-mt-8">
                    <div class="flex flex-col sm:flex-row sm:items-end gap-4 mb-6 border-b pb-3" style="border-color: color-mix(in srgb, var(--menu-accent) 35%, transparent);">
                        @if($showCatImg && $category->image)
                            <img src="{{ Storage::disk('public')->url($category->image) }}" alt="" class="w-full sm:w-28 h-36 sm:h-24 object-cover rounded-xl shadow-md shrink-0">
                        @endif
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl sm:text-3xl font-semibold" style="font-family: var(--font-display);">
                                {{ $category->name }}
                            </h2>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:gap-5 {{ $cols === 2 ? 'sm:grid-cols-2' : 'grid-cols-1' }}">
                        @foreach($category->products as $product)
                            @php
                                $tags = $product->tags ?? [];
                                $promoStyle = $product->promo_style ?: $ribbonPreset;
                                $prGrad = $productRibbonClasses[$promoStyle] ?? $productRibbonGradient;
                            @endphp
                            <article class="relative rounded-2xl border {{ $cardBorder }} {{ $cardBg }} backdrop-blur-sm shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                                @if($product->promo_label)
                                    <div class="ribbon-corner">
                                        <span class="bg-gradient-to-br {{ $prGrad }}">{{ $product->promo_label }}</span>
                                    </div>
                                @endif
                                <div class="flex gap-4 p-4 sm:p-5">
                                    @if($product->image)
                                        <div class="shrink-0 w-24 h-24 sm:w-28 sm:h-28 rounded-xl overflow-hidden bg-stone-100 ring-1 ring-black/5">
                                            <img src="{{ Storage::disk('public')->url($product->image) }}" alt="" class="w-full h-full object-cover">
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0 flex flex-col">
                                        <div class="flex flex-wrap items-start justify-between gap-2 gap-y-1">
                                            <h3 class="text-lg sm:text-xl font-semibold leading-snug pr-6" style="font-family: var(--font-display);">
                                                {{ $product->name }}
                                            </h3>
                                            <span class="text-lg font-semibold shrink-0 menu-price" style="color: var(--menu-accent);">
                                                {{ config('deco.currency_symbol') }}{{ number_format((float) $product->price, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        @if($product->stock_control && $product->current_stock > 0 && $product->current_stock < 5)
                                            <p class="mt-1.5 text-xs font-extrabold uppercase tracking-widest {{ $isDarkBg ? 'text-amber-400' : 'text-amber-600' }}">
                                                últimas!!!
                                            </p>
                                        @endif
                                        @if($product->short_description)
                                            <p class="mt-1 text-sm leading-relaxed menu-text-muted">{{ $product->short_description }}</p>
                                        @endif
                                        @if($product->menu_note)
                                            <p class="mt-2 text-sm italic menu-text-muted border-l-2 pl-3" style="border-color: var(--menu-accent);">{{ $product->menu_note }}</p>
                                        @endif
                                        @if(count($tags))
                                            <ul class="mt-3 flex flex-wrap gap-2">
                                                @foreach($tags as $tag)
                                                    @if(isset($dietary[$tag]))
                                                        <li class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tagPill }}">
                                                            @include('menu.partials.dietary-icon', ['tag' => $tag])
                                                            <span>{{ $dietary[$tag] }}</span>
                                                        </li>
                                                    @else
                                                        <li class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tagPillLoose }}">{{ $tag }}</li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if($menuOrderingEnabled && $menuOrderSubmitUrl)
                                            <button
                                                type="button"
                                                class="menu-deco-add mt-3 w-full sm:w-auto rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:brightness-110 touch-manipulation"
                                                style="background: var(--menu-accent);"
                                                data-product-id="{{ $product->id }}"
                                                data-product-name="{{ $product->name }}"
                                            >
                                                Agregar al pedido
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        @if(!empty($theme['footer_note']))
            <footer class="mt-16 pt-8 border-t text-center text-sm menu-text-muted {{ $footerBorder }}">
                {{ $theme['footer_note'] }}
            </footer>
        @endif

        <p class="mt-10 text-center text-xs menu-text-muted opacity-80">
            Carta actualizada — {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
