{{--
    Un post de Convivencia, en su propia dirección para que se pueda pegar el
    enlace de uno solo.

    El contenido es Markdown capturado en el panel y se pinta con `{!! !!}` a
    propósito: es el único texto del sitio que sale como HTML de verdad —con
    títulos, listas e imágenes—. Lo que lo hace seguro no es el `{!! !!}` sino
    lo que hay antes: `Markdown::aHtml` tira el HTML escrito a mano dentro del
    Markdown y luego sanea el HTML que genera. Ver esa clase antes de tocar esta
    línea; **nunca** pasarle a `{!! !!}` el contenido crudo del modelo.

    Sin Comentarios (URVA-96): un post no se somete a consideración de la
    Asamblea, se lee. La única página del sitio que recibe Comentarios es la
    Propuesta, que es la que se vota.
--}}
@use('App\Support\Contenido\Markdown')

<x-layout.app :title="$post->titulo"
              :descripcion="Markdown::aResumen($post->contenido)"
              :canonical="$post->urlPublica()">
    <x-palette-receipt.seccion rotulo="Convivencia" :titulo="$post->titulo">
        <p class="-mt-4">
            <time datetime="{{ $post->publicado_en->toDateString() }}"
                  class="cifra text-xs font-semibold uppercase tracking-[0.08em] text-grafito/70">
                Publicado el {{ $post->publicado_en->translatedFormat('j \d\e F \d\e Y') }}
            </time>
        </p>

        <article class="prosa mt-6 border-t border-linea pt-6">
            {!! Markdown::aHtml($post->contenido) !!}
        </article>

        {{--
            La vuelta al índice va al final y no arriba: el lector llegó aquí por
            un enlace pegado en un grupo de vecinos, así que lo más probable es
            que no sepa que hay más publicaciones hasta que termine ésta.
        --}}
        <p class="mt-10 border-t border-linea pt-6 text-sm">
            <a href="{{ route('convivencia') }}"
               class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">
                Ver todas las publicaciones de Convivencia
            </a>
        </p>
    </x-palette-receipt.seccion>
</x-layout.app>
