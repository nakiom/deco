@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;
@endphp
<div class="space-y-4 text-center">
    @if($url !== '')
        <div class="inline-block rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-200">
            {!! QrCode::size(220)->margin(1)->generate($url) !!}
        </div>
        <p class="text-xs break-all text-gray-700 dark:text-gray-300">{{ $url }}</p>
        <p class="text-xs text-gray-500">Imprimí o guardá este código; el enlace completo no debe compartirse sin el secreto del QR.</p>
    @else
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Usá «Regenerar código QR» para emitir un enlace nuevo. La URL completa solo se muestra unos minutos tras generarla o regenerarla.
        </p>
    @endif
</div>
