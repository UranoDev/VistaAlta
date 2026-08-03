@props([
    // El troquel de papel térmico en el borde superior. Se apaga en tarjetas
    // que van en lista, donde repetirlo satura.
    'troquel' => true,
])

<div {{ $attributes->class([
        'bg-papel-alto border border-linea',
        'recibo-troquel' => $troquel,
    ]) }}>
    <div @class(['px-5 py-5 sm:px-6 sm:py-6', 'pt-6 sm:pt-7' => $troquel])>
        {{ $slot }}
    </div>
</div>
