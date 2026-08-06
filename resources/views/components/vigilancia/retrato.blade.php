@props(['vigilante'])

{{--
    La cara de quien está en el acceso, o sus iniciales.

    El monograma no es un hueco a la espera de una foto: es la forma definitiva
    para quien prefirió no publicar la suya. Por eso comparte marco, tamaño y
    color con el retrato — que se note quién no quiso salir sería castigar la
    decisión, y son cuatro tarjetas juntas donde la diferencia se ve de
    inmediato.

    La foto va con `alt` vacío a propósito: el nombre está a un renglón de
    distancia, y un lector de pantalla que lo lea dos veces estorba más de lo que
    ayuda.
--}}
<div {{ $attributes->class([
        'flex-none overflow-hidden border border-linea bg-menta text-tinta',
        'grid place-items-center',
        'cifra font-semibold tracking-[0.06em]',
    ]) }}>
    @if ($vigilante->tieneFoto())
        <img src="{{ asset('img/vigilantes/'.$vigilante->foto) }}"
             alt=""
             class="size-full object-cover">
    @else
        <span aria-hidden="true">{{ $vigilante->iniciales() }}</span>
    @endif
</div>
