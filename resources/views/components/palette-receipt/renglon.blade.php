@props([
    // Concepto a la izquierda; la cifra o el valor va en el slot, a la derecha.
    'concepto',
])

<div {{ $attributes->class(['recibo-renglon text-sm']) }}>
    <span class="text-grafito/80">{{ $concepto }}</span>
    <span>{{ $slot }}</span>
</div>
