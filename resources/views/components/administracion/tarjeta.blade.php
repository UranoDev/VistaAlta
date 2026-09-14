@props([
    'integrante',
    // Quien encabeza el órgano. Cambia el filete y el color del cargo, nunca el
    // tamaño: las siete tarjetas miden lo mismo y eso no se negocia.
    'cabeza' => false,
])

{{--
    Una persona dentro del organigrama: retrato, nombre y cargo.

    En el celular se acuesta —retrato a la izquierda, texto a la derecha—
    porque tres columnas de 120 px no son un organigrama, son tres nombres
    partidos a la mitad.

    Deliberadamente **no** lleva la línea de qué hace ese cargo. Esa vive en la
    lista de abajo del organigrama por dos razones: el Comité no la tiene —los
    tres hacen lo mismo y el párrafo de la sección ya lo dice— y meterla aquí
    dejaría las tarjetas de un órgano más altas que las del otro, lo que se lee
    como un error de maquetado y no como una diferencia real.
--}}
<div @class([
    'flex h-full flex-row items-start gap-3.5 border bg-papel-alto p-3.5 text-left',
    'sm:flex-col sm:items-center sm:gap-2.5 sm:px-3 sm:pb-4 sm:pt-3.5 sm:text-center',
    'border-tinta' => $cabeza,
    'border-linea' => ! $cabeza,
])>
    <x-administracion.retrato :integrante="$integrante"
                              class="w-16 max-w-16 aspect-square text-sm sm:w-full sm:max-w-28 sm:text-[0.9375rem]" />

    <div class="flex min-w-0 flex-col gap-1">
        <p class="font-semibold leading-snug text-tinta">{{ $integrante->nombre }}</p>
        <p @class([
            'cifra text-[0.6875rem] uppercase tracking-[0.1em]',
            'text-tinta' => $cabeza,
            'text-grafito/65' => ! $cabeza,
        ])>{{ $integrante->cargo }}</p>
    </div>
</div>
