@php
    $theme = $restaurant->resolvedMenuTheme();
    $accent = $theme['accent_color'] ?? '#c2410c';
    $bg = $theme['background'] ?? 'parchment';
    $isDarkBg = in_array($bg, ['dark_bistro', 'chalk'], true);
    $cardBorder = $isDarkBg ? 'border-stone-700' : 'border-stone-200/80';
    $cardBg = $isDarkBg ? 'bg-stone-900/75' : 'bg-white/85';
    $textMain = $isDarkBg ? 'text-stone-100' : 'text-stone-900';
    $textMuted = $isDarkBg ? 'text-stone-400' : 'text-stone-600';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $restaurant->name }} — Acceso</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>:root { --menu-accent: {{ $accent }}; }</style>
</head>
<body class="menu-bg-{{ $bg }} min-h-screen antialiased {{ $textMain }} flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md rounded-2xl border {{ $cardBorder }} {{ $cardBg }} backdrop-blur p-6 sm:p-8 shadow-lg">
        <h1 class="text-xl font-semibold text-center" style="color: var(--menu-accent);">{{ $restaurant->name }}</h1>
        <p class="mt-2 text-center text-sm {{ $textMuted }}">Mesa {{ $table->display_name ?? $table->number }}</p>
        <p class="mt-4 text-sm {{ $textMuted }} text-center">Este local requiere una contraseña para ver la carta desde el QR.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-800 dark:text-red-200">
                {{ $errors->first('password') }}
            </div>
        @endif

        <form method="post" action="{{ $qrUuid && $secret ? route('menu.unlock', ['qrUuid' => $qrUuid, 'secret' => $secret]) : route('menu.unlock.legacy', ['legacyToken' => $legacyToken]) }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="password" class="block text-sm font-medium {{ $textMuted }} mb-1">Contraseña</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-xl border {{ $cardBorder }} bg-white/90 dark:bg-stone-950/80 px-3 py-2.5 text-stone-900 dark:text-stone-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-[color:var(--menu-accent)]"
                />
            </div>
            <button
                type="submit"
                class="w-full rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:brightness-110"
                style="background: var(--menu-accent);"
            >
                Ver carta
            </button>
        </form>
    </div>
</body>
</html>
