{{--
    La página principal del sitio: el único asunto que la Mesa Directiva somete
    a consideración de la Asamblea.

    El texto es la Mesa Directiva hablando con sus vecinos. La paleta viene del
    producto de nvavista (docs/adr/0003); el texto no. Nada de aquí vende un
    servicio ni promete un beneficio: se argumenta y se dice también lo que no
    cambia, porque una propuesta que solo enumera ventajas se lee como una venta.
--}}
<x-layout.app title="Propuesta">
    <x-recibo.seccion rotulo="Ante la Asamblea" :lectura="false">
        <div class="grid gap-8 sm:grid-cols-10 sm:gap-10">
            <div class="sm:col-span-4">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    Formalizar el fraccionamiento como Asociación Civil
                </h1>
                <p class="mt-4 text-grafito/85">
                    Vista Alta funciona. Lo que no existe es Vista Alta ante la ley: la cuenta donde caen las cuotas
                    está a nombre de un colono, los acuerdos con proveedores se sostienen de palabra y la cuota se
                    paga porque cada quien decide pagarla.
                </p>
                <p class="mt-4 text-grafito/85">
                    Esto es lo que la Mesa Directiva somete a consideración de la Asamblea: constituir la Asociación
                    Civil que le dé al fraccionamiento un nombre propio con el cual abrir una cuenta, firmar y
                    responder.
                </p>
                <p class="mt-4 text-sm text-grafito/75">
                    Es el único asunto del periodo. Las
                    <a href="{{ route('actividades') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Actividades</a>
                    y el
                    <a href="{{ route('reporte-financiero') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Reporte financiero</a>
                    están aquí para respaldarlo.
                </p>
            </div>

            <div class="sm:col-span-6">
                @if ($videoUrl)
                    <div class="aspect-video overflow-hidden border border-linea bg-papel-alto">
                        <iframe src="{{ $videoUrl }}"
                                title="Video sobre la Propuesta"
                                class="h-full w-full"
                                loading="lazy"
                                allowfullscreen></iframe>
                    </div>
                @else
                    <div class="flex aspect-video items-center justify-center border border-dashed border-linea bg-papel-alto px-6 text-center text-sm text-grafito/70">
                        El video con la explicación se publica aquí.
                    </div>
                @endif
            </div>
        </div>
    </x-recibo.seccion>

    {{--
        El argumento. Va en presente y en concreto —lo que pasa hoy y lo que
        pasaría— en vez de en abstracto: «personalidad jurídica» no le dice nada
        a nadie hasta que se cuenta a nombre de quién está la cuenta.
    --}}
    <x-recibo.seccion rotulo="El argumento" titulo="Lo que cambia el día que la figura existe">
        @php
            $cambios = [
                [
                    'titulo' => 'El dinero deja de estar a nombre de alguien',
                    'texto' => 'Hoy la cuenta donde caen las cuotas está a nombre de un colono, con su RFC y bajo su
                                responsabilidad personal. Con la Asociación constituida la cuenta es del
                                fraccionamiento: quien entra y sale de la Mesa Directiva cambia, la cuenta se queda.',
                ],
                [
                    'titulo' => 'Los acuerdos se pueden firmar',
                    'texto' => 'Vigilancia, jardinería, mantenimiento del acceso. Hoy se acuerdan de palabra o los
                                firma un particular que pone su nombre. Una Asociación Civil contrata, exige lo
                                pactado y tiene con qué reclamar cuando un proveedor no cumple.',
                ],
                [
                    'titulo' => 'La cuota se vuelve exigible',
                    'texto' => 'Hoy quien no paga simplemente no paga, y lo que deja de aportar lo terminan cubriendo
                                los vecinos que sí pagaron. No se trata de demandar a nadie: se trata de que dejar de
                                pagar tenga una consecuencia posible en vez de salir gratis.',
                ],
                [
                    'titulo' => 'Rendir cuentas deja de ser un gesto de buena voluntad',
                    'texto' => 'Una Asociación Civil está obligada por sus estatutos a llevar cuentas y a presentarlas
                                ante su Asamblea. Lo que hoy hacemos porque nos parece lo correcto —esta página, entre
                                otras cosas— quedaría como obligación de quien ocupe la Mesa Directiva después.',
                ],
            ];
        @endphp

        <ol class="border-t border-linea">
            @foreach ($cambios as $indice => $cambio)
                <li class="grid gap-1.5 border-b border-linea py-5 sm:grid-cols-[2.5rem_1fr] sm:gap-4">
                    <span aria-hidden="true" class="cifra text-sm font-semibold text-tinta">
                        {{ str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) }}
                    </span>
                    <div>
                        <h3 class="font-semibold">{{ $cambio['titulo'] }}</h3>
                        <p class="mt-1.5 text-sm text-grafito/85">{{ $cambio['texto'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-recibo.seccion>

    {{--
        La contraparte del argumento. Lo que la gente teme de una figura legal
        —perder algo suyo, pagar de más, entregarle poder a alguien— se contesta
        aquí y no en la Asamblea, donde ya no hay tiempo de contestarlo bien.
    --}}
    <x-recibo.seccion rotulo="Con todas sus letras" titulo="Lo que no cambia">
        @php
            $permanencias = [
                'Tu casa sigue siendo tuya.' => 'La Asociación administra lo común. Tu propiedad y tu escritura no
                                                 entran, no se aportan y no se gravan con nada.',
                'No hay cuota nueva.' => 'Se sigue pagando la cuota que ya se paga. Los gastos de notario y de registro
                                          salen del fondo del fraccionamiento y aparecen en el Reporte financiero.',
                'La Mesa Directiva no gana poder.' => 'Sigue rindiendo cuentas ante los Colonos y dura lo que la
                                                       Asamblea decida. Los estatutos la sujetan más de lo que hoy la
                                                       sujeta la costumbre.',
                'El fraccionamiento no se le entrega a nadie de fuera.' => 'Los asociados son los propietarios de Vista
                                                                            Alta. No entra una empresa, ni un
                                                                            administrador externo, ni el municipio.',
            ];
        @endphp

        <dl class="grid gap-5 sm:grid-cols-2">
            @foreach ($permanencias as $afirmacion => $detalle)
                <div class="border-l-2 border-linea pl-4">
                    <dt class="font-semibold">{{ $afirmacion }}</dt>
                    <dd class="mt-1 text-sm text-grafito/85">{{ $detalle }}</dd>
                </div>
            @endforeach
        </dl>
    </x-recibo.seccion>

    <x-recibo.seccion rotulo="Para entenderla" titulo="Preguntas frecuentes">
        <dl class="border-t border-linea">
            @foreach ($preguntasFrecuentes as $pregunta => $respuesta)
                <div class="border-b border-linea py-4">
                    <dt class="font-semibold">{{ $pregunta }}</dt>
                    <dd class="mt-1.5 text-sm text-grafito/80">{{ $respuesta }}</dd>
                </div>
            @endforeach
        </dl>
    </x-recibo.seccion>

    <x-recibo.seccion id="comentarios" rotulo="Tu voz" titulo="Preguntas y comentarios de los colonos">
        <p class="text-grafito/85">
            La Asamblea es donde esto se decide, pero no tiene por qué ser el único lugar donde se pregunte. Si algo
            de la Propuesta no te cuadra, escríbelo aquí: la Mesa Directiva lo lee antes de la reunión y así llega
            contestado en vez de improvisado.
        </p>

        <div class="mt-8">
            @if ($recepcionAbierta)
                <x-recibo.tarjeta>
                    <x-comentarios.formulario :telefono-validado="$telefonoValidado"
                                              :telefono-pendiente="$telefonoPendiente" />
                </x-recibo.tarjeta>
            @else
                {{--
                    Recepción de comentarios cerrada: se retira el formulario, pero
                    nada de lo ya publicado se oculta.
                --}}
                <x-recibo.nota variante="aviso">
                    @if (session('comentario.aviso'))
                        {{ session('comentario.aviso') }}
                    @else
                        La Mesa Directiva cerró la recepción de comentarios. Lo que ya se publicó sigue aquí abajo.
                    @endif
                </x-recibo.nota>
            @endif
        </div>

        <div class="mt-10 space-y-4">
            <div class="flex items-baseline justify-between gap-4 border-b border-linea pb-2">
                <h3 class="text-lg font-bold tracking-tight">Comentarios públicos</h3>
                <span class="cifra text-xs text-grafito/70">
                    {{ trans_choice('{0}ninguno|{1}:count comentario|[2,*]:count comentarios', $comentariosPublicos->count(), ['count' => $comentariosPublicos->count()]) }}
                </span>
            </div>

            @forelse ($comentariosPublicos as $comentario)
                <x-recibo.tarjeta :troquel="false" style="--recibo-fondo: var(--color-papel)">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                        <p class="font-semibold">{{ $comentario->nombre }}</p>
                        <time datetime="{{ $comentario->created_at->toDateString() }}"
                              class="cifra text-xs text-grafito/70">
                            {{ $comentario->created_at->translatedFormat('j M Y') }}
                        </time>
                    </div>
                    <p class="mt-2 whitespace-pre-line text-sm text-grafito/85">{{ $comentario->comentario }}</p>
                </x-recibo.tarjeta>
            @empty
                <p class="py-2 text-sm text-grafito/70">
                    Todavía no hay comentarios públicos. Los que se envían aparecen aquí una vez que la Mesa
                    Directiva los publica.
                </p>
            @endforelse
        </div>
    </x-recibo.seccion>
</x-layout.app>
