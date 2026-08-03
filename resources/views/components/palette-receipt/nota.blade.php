@props([
    // 'aviso' — usa Rojo Sello, reservado a alertas y urgencia.
    // 'exito' — Menta Pálida, para confirmar algo que ya ocurrió.
    // 'neutra' — apunte al margen, sin color de estado.
    'variante' => 'neutra',
])

@php
    $variantes = [
        'aviso' => 'border-sello/40 bg-sello/8 text-sello',
        'exito' => 'border-tinta/30 bg-menta/45 text-tinta',
        'neutra' => 'border-linea bg-papel text-grafito/80',
    ];
@endphp

<div role="{{ $variante === 'aviso' ? 'alert' : 'status' }}"
     {{ $attributes->class([
        'border-l-2 px-4 py-3 text-sm',
        $variantes[$variante] ?? $variantes['neutra'],
     ]) }}>
    {{ $slot }}
</div>
