{{--
    El nombre de un órgano dentro del organigrama: el bloque de tinta sólida
    entre la Asamblea y las personas.

    Va en tinta plena y la Asamblea en menta: dos niveles distintos, dos pesos
    distintos, y ninguno compite con las tarjetas de abajo, que son de papel.
--}}
<span {{ $attributes->class([
        'cifra inline-block bg-tinta px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-papel',
    ]) }}>
    {{ $slot }}
</span>
