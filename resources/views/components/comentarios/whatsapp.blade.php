@props([
    // El enlace a wa.me, con el mensaje ya prellenado.
    'enlace',
    // El mismo número escrito como se lee, para quien no abre WhatsApp aquí.
    'numero',
])

{{--
    La Vía de recepción está en WhatsApp: la página no pide teléfono, no promete
    ningún código y no ofrece formulario. Solo el enlace a la conversación.

    El texto dice lo que de verdad pasa —quién recibe, quién publica y de quién
    depende que el comentario salga público o no—, sin excusa técnica. Prometer
    un SMS que no llega fue el problema; explicar el problema no es el trabajo de
    esta página.
--}}
<div {{ $attributes->class(['space-y-5']) }}>
    @if (session('comentario.aviso'))
        <x-palette-receipt.nota variante="aviso">{{ session('comentario.aviso') }}</x-palette-receipt.nota>
    @endif

    <div class="space-y-3">
        <h3 class="text-lg font-bold tracking-tight">Los comentarios se reciben por WhatsApp</h3>

        {{--
            Lo que promete esta página es lo que la Mesa Directiva sí hace hoy:
            leer y contestar en el chat. Publicar en el sitio un comentario que
            llegó por WhatsApp requiere capturarlo en el panel, y esa pantalla
            todavía no existe — prometerlo aquí sería repetir exactamente el
            problema que esta vía viene a resolver (URVA-26).
        --}}
        <p class="text-sm text-grafito/85">
            Escríbele a la Mesa Directiva por WhatsApp: ahí se leen y se contestan todas las preguntas y
            comentarios sobre la Propuesta.
        </p>

        {{--
            Lo que cambia respecto de escribir en el sitio, dicho en la propia
            página: aquí la visibilidad la captura alguien de la Mesa Directiva,
            así que tiene que venir pedida por escrito. El mensaje del enlace ya
            la pide; esto explica por qué importa contestarla.
        --}}
        <p class="text-sm text-grafito/85">
            El mensaje ya viene escrito y solo hay que llenarlo. Dinos ahí si quieres que tu comentario sea
            <strong class="font-semibold">público</strong> —para que la Mesa Directiva pueda publicarlo en esta
            página con tu nombre— o <strong class="font-semibold">privado</strong>, solo para ella. Se respeta lo
            que pidas, y esa decisión no se deshace después.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-x-5 gap-y-3">
        <x-palette-receipt.boton :href="$enlace" target="_blank" rel="noopener noreferrer">
            Escribir por WhatsApp
        </x-palette-receipt.boton>

        <span class="text-sm text-grafito/70">
            o guarda el número: <span class="cifra font-semibold text-grafito">{{ $numero }}</span>
        </span>
    </div>

    <p class="text-xs text-grafito/70">
        Tu número no se publica en ninguna parte del sitio. Al escribirnos aceptas el
        <a href="{{ route('privacidad') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Aviso de Privacidad</a>
        y los
        <a href="{{ route('terminos') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Términos de Servicio</a>.
    </p>
</div>
