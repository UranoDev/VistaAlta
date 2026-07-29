{{--
    Un artículo numerado de las páginas legales. Entre el Aviso de Privacidad y
    los Términos de Servicio son diecinueve con la misma forma, así que el
    número, el título y el separador se ponen aquí una vez.

    El separador es el punteado de la Bitácora (`border-t border-dashed
    border-linea`): marca dónde termina un artículo sin encerrarlo en una caja.
    El primero de la lista no lo lleva — arriba está el encabezado, no otro
    artículo.

    `numero` es opcional: la sección de Contacto cierra ambos documentos sin
    número porque no es parte del articulado.
--}}
@props([
    'numero' => null,
    'titulo',
])

<div {{ $attributes->class(['border-t border-dashed border-linea pt-8 first:border-t-0 first:pt-0']) }}>
    <h2 class="mb-3 text-lg font-bold tracking-tight">
        @if ($numero)
            <span class="cifra text-tinta">{{ $numero }}.</span>
        @endif
        {{ $titulo }}
    </h2>

    <div class="space-y-3 text-grafito/85">
        {{ $slot }}
    </div>
</div>
