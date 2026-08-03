@props([
    // 'tinta' — la acción principal de la página.
    // 'contorno' — acción secundaria, sin peso de color.
    'variante' => 'tinta',
    // Si se pasa `href`, se renderiza como enlace en vez de <button>.
    'href' => null,
])

@php
    $etiqueta = 'inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold '
        .'transition-colors disabled:opacity-50 disabled:pointer-events-none';

    $variantes = [
        'tinta' => 'bg-tinta text-papel hover:bg-tinta-suave',
        'contorno' => 'border border-tinta text-tinta hover:bg-menta/50',
    ];

    $clases = $etiqueta . ' ' . ($variantes[$variante] ?? $variantes['tinta']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($clases) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class($clases)->merge(['type' => 'button']) }}>{{ $slot }}</button>
@endif
