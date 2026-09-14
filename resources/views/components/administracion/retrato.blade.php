@props(['integrante'])

{{--
    La cara de quien ocupa un cargo, o sus iniciales.

    Es gemelo de `components/vigilancia/retrato.blade.php` y se mantiene aparte
    a propósito: aquel lleva su propia carpeta de fotos y su propio contrato de
    privacidad —nombre de pila e inicial, trabajadores— y juntarlos en un
    componente con parámetros dejaría las dos decisiones colgando de una misma
    llave que cualquiera cambia sin ver a quién afecta.

    Lo que sí comparten, porque es la misma regla: el monograma no es un hueco a
    la espera de una foto, es la forma definitiva de quien prefirió no publicar
    la suya. Mismo marco, mismo tamaño, mismo color — son siete tarjetas juntas
    y ahí la diferencia se ve de inmediato.

    La foto va con `alt` vacío a propósito: el nombre está a un renglón de
    distancia, y un lector de pantalla que lo lea dos veces estorba más de lo
    que ayuda.
--}}
<div {{ $attributes->class([
        'flex-none overflow-hidden border border-linea bg-menta text-tinta',
        'grid place-items-center',
        'cifra font-semibold tracking-[0.06em]',
    ]) }}>
    @if ($integrante->tieneFoto())
        <img src="{{ asset('img/administracion/'.$integrante->foto) }}"
             alt=""
             class="size-full object-cover">
    @else
        <span aria-hidden="true">{{ $integrante->iniciales() }}</span>
    @endif
</div>
