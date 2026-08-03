{{--
    La única página del sitio que pide algo en vez de rendir cuentas: los
    comprobantes de depósito a la administración pasada.

    Dos cuidados que la gobiernan:

    1. Se limita a lo verificable —cuántos comprobantes hay y qué falta para
       documentar lo entregado—. No afirma delitos ni señala personas: es lo
       que la vuelve difícil de rebatir, y de paso lo prudente.

    2. Es el caso para el que se guardó el Rojo Sello (`resources/css/app.css`:
       reservado a alertas y urgencia). Se usa aquí y sigue sin aparecer en las
       otras páginas: si el rojo se vuelve decoración, esta página pierde su
       fuerza. Un solo punto de énfasis —el número— más la nota de aviso; el
       sello de goma sobraría. Lo que se resalta después del número va con
       peso, no con color, por lo mismo: dos piezas en rojo compiten y las dos
       pierden.

    3. No lleva enlaces de navegación interna. Es la única página que pide algo
       en vez de rendir cuentas, y cualquier salida es una fuga: quien llega
       aquí tiene una sola cosa que hacer. El `mailto:` no cuenta — es esa
       cosa.

    El número y el correo salen de `config/contenido.php`. El número porque va
    a cambiar conforme lleguen comprobantes y una página que se quedó en el
    número viejo miente; el correo porque es el buzón que se puede apagar —esta
    es la página que más se difunde y la que deja la dirección expuesta al
    scraping, así que va en `correo_comprobantes` y no en el institucional que
    citan las páginas legales.
--}}
@php
    $recibidos = config('contenido.comprobantes_recibidos');
    $correo = config('contenido.correo_comprobantes');
@endphp

<x-layout.app title="Demanda">
    <x-palette-receipt.seccion rotulo="Demanda" titulo="Faltan tus comprobantes">
        {{--
            Para qué se piden los comprobantes. Va antes del número porque sin
            esto la página pide un documento sin decir a qué se destina.

            Redactado con el cuidado del encabezado: «está evaluando demandar»
            y «no ha rendido cuenta» son una intención propia y un hecho
            verificable. No dice que alguien se robó nada ni nombra a nadie —
            anunciar una demanda es un paso más fuerte que pedir papeles, así
            que el criterio aprieta más aquí, no menos.
        --}}
        <p class="text-grafito/85">
            Como se acordó en la más reciente asamblea, la Mesa Directiva está evaluando demandar a la administración anterior por los depósitos que recibió y de
            los que no ha rendido cuenta. Para sostener una demanda hay que documentar cuánto se entregó, y eso solo se
            puede con los comprobantes que tengan los propietarios.
        </p>

        {{--
            El dato que duele, y la pieza más grande de la página. El texto de
            al lado no repite la cifra: la continúa, para que el número se
            escriba una sola vez.
        --}}
        <div class="mt-6 flex flex-col gap-5 border-y-2 border-sello/25 py-8 sm:flex-row sm:items-center sm:gap-10">
            <p class="cifra text-7xl font-bold leading-none text-sello sm:text-8xl">{{ $recibidos }}</p>
            {{--
                La frase que dice qué se está pidiendo va aparte, en su propio
                elemento, y no dentro del `trans_choice()`. Así se le puede dar
                énfasis sin abrirle la puerta a HTML sin escapar (`{!! !!}`)
                en una cadena con formas plurales que alguien va a editar. De
                paso, en el `trans_choice()` queda solo lo que cambia con el
                número, que es lo único que hay que repetir tres veces.
            --}}
            <p class="max-w-sm text-lg leading-snug text-grafito/85">
                {{ trans_choice(
                    '{0}propietarios de todo el fraccionamiento han entregado a la Mesa Directiva su'
                    .'|{1}propietario de todo el fraccionamiento ha entregado a la Mesa Directiva su'
                    .'|[2,*]propietarios de todo el fraccionamiento han entregado a la Mesa Directiva su',
                    $recibidos,
                ) }}
                <strong class="font-semibold text-grafito">comprobante de depósito a la administración pasada</strong>.
            </p>
        </div>

        <p class="mt-8 text-grafito/85">
            Con eso no se sostiene nada.
        </p>

        <x-palette-receipt.nota variante="aviso" class="mt-4">
            Sin comprobantes no hay forma de documentar cuánto se entregó, y sin eso el asunto se queda exactamente
            donde está: parado. No es que no se quiera avanzar — es que no se puede.
        </x-palette-receipt.nota>

        <p class="mt-6 text-grafito/85">
            Si tú depositaste, tu comprobante cambia la situación. No importa que sea de hace años, que sea la foto de un
            recibo arrugado o el pantallazo de una transferencia: todo suma. Si no lo encuentras, avísanos de todos
            modos.
        </p>

        {{--
            Lo segundo más visible de la página, después del número: una página
            que logra convencer y esconde el cómo no sirve de nada.

            Va por correo y no por un formulario porque el correo admite
            adjuntos y el sitio no recibe archivos en ninguna parte. `mailto:`
            para que en celular abra el correo con la dirección puesta.
        --}}
        <div class="mt-10 border border-linea bg-papel-alto px-6 py-8 text-center">
            <x-palette-receipt.rotulo>Mándalo a</x-palette-receipt.rotulo>
            <a href="mailto:{{ $correo }}"
               class="cifra mt-3 block break-words text-2xl font-bold text-tinta underline underline-offset-4 hover:text-tinta-suave sm:text-3xl">
                {{ $correo }}
            </a>
            <p class="mx-auto mt-5 max-w-md text-sm text-grafito/80">
                Adjunta la foto o el pantallazo; este sitio no recibe archivos, nuestro correo sí. Alguien de la Mesa
                Directiva revisa ese buzón y te confirma que llegó.
            </p>
        </div>

        <p class="mt-8 text-sm text-grafito/75">
            Es un buzón que la Mesa Directiva abrió solo para esto. Lo que se hace con los comprobantes es una sola
            cosa: documentar cuánto se entregó.
        </p>
    </x-palette-receipt.seccion>
</x-layout.app>
