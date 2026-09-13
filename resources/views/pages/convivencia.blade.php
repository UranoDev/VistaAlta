{{--
    El índice de Convivencia: lo que la Mesa Directiva publica sobre cómo se
    vive el fraccionamiento, más reciente primero.

    A diferencia de Actividades, aquí la lista **no** trae el contenido: cada
    post se lee en su propia página (`/convivencia/{slug}`). Un post es largo y
    lleva formato, y apilar cuatro completos en una sola página deja al lector
    sin saber dónde acaba uno y empieza el otro. Lo que se muestra de cada uno es
    el arranque de su propio texto, no un resumen capturado aparte, para que no
    pueda quedarse hablando de una versión anterior.

    La introducción sale de la base (`IntroDeConvivencia`) y puede no existir:
    mientras la Mesa Directiva no la escriba, el índice arranca directo con los
    posts. No hay texto de relleno a propósito.

    El texto de la introducción pasa por `TextoConLigas` y no por el Markdown de
    los posts: es un párrafo de encabezado, no un documento, y lo único que
    necesita además del texto es poder mandar al lector a otra página del sitio.
--}}
@use('App\Support\Contenido\Markdown')
@use('App\Support\Contenido\TextoConLigas')

<x-layout.app title="Convivencia"
              descripcion="Lo que hay que saber para convivir en Vista Alta: las reglas y los acuerdos del fraccionamiento, publicados por la Mesa Directiva.">
    <x-palette-receipt.seccion rotulo="Cómo se vive Vista Alta" titulo="Convivencia">
        @if ($intro)
            <p class="whitespace-pre-line text-grafito/85">{{ TextoConLigas::aHtml($intro) }}</p>
        @endif

        <div @class(['flex items-baseline justify-between gap-4 border-b border-linea pb-2', 'mt-8' => $intro])>
            <h3 class="text-lg font-bold tracking-tight">Publicaciones</h3>
            <span class="cifra text-xs text-grafito/70">
                {{ trans_choice('{0}ninguna|{1}:count publicación|[2,*]:count publicaciones', $posts->count(), ['count' => $posts->count()]) }}
            </span>
        </div>

        @if ($posts->isNotEmpty())
            <ul class="border-b border-linea">
                @foreach ($posts as $post)
                    <li class="border-t border-linea py-5 first:border-t-0">
                        <time datetime="{{ $post->publicado_en->toDateString() }}"
                              class="cifra text-xs font-semibold uppercase tracking-[0.08em] text-tinta">
                            {{ $post->publicado_en->translatedFormat('j M Y') }}
                        </time>

                        {{--
                            El enlace envuelve el título entero y no una palabra
                            suelta tipo «leer más»: es lo que un lector de
                            pantalla anuncia al recorrer la lista, y «leer más»
                            repetido cuatro veces no dice cuál es cuál.
                        --}}
                        <h4 class="mt-1 text-base font-bold tracking-tight">
                            <a href="{{ $post->urlPublica() }}"
                               class="text-tinta underline underline-offset-2 hover:text-tinta-suave">
                                {{ $post->titulo }}
                            </a>
                        </h4>

                        <p class="mt-1 text-sm text-grafito/85">{{ Markdown::aResumen($post->contenido) }}</p>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="py-4 text-sm text-grafito/70">
                Todavía no hay publicaciones. Aparecen aquí conforme la Mesa Directiva las captura.
            </p>
        @endif
    </x-palette-receipt.seccion>
</x-layout.app>
