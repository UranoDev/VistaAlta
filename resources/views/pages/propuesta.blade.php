{{--
    La página principal del sitio: el único asunto que la Mesa Directiva somete
    a consideración de la Asamblea.

    El texto es la Mesa Directiva hablando con sus vecinos. La paleta viene del
    producto de nvavista (docs/adr/0003); el texto no. Nada de aquí vende un
    servicio ni promete un beneficio: se argumenta y se dice también lo que no
    cambia, porque una propuesta que solo enumera ventajas se lee como una venta.
--}}
<x-layout.app title="Propuesta">
    <x-recibo.seccion rotulo="Ante los Colonos" :lectura="false">
        <div class="grid gap-8 sm:grid-cols-10 sm:gap-10">
            <div class="sm:col-span-4">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    Formalizar el fraccionamiento
                </h1>
                <p class="mt-4 text-grafito/85">
                    Dando seguimiento a lo planteado en la última Asamblea, la Mesa Directiva propone constituir legalmente la Asociación Civil.
                </p>
                <p class="mt-4 text-grafito/85">
                    Necesitamos tres colonos para que nos acompañen en esta tarea.
                    <a href="#participar" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Aquí explicamos para qué</a>.
                </p>
                <p class="mt-4 text-sm text-grafito/75">
                    Es el único asunto del periodo. Las
                    <a href="{{ route('actividades') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Actividades</a>
                    y el
                    <a href="{{ route('reporte-financiero') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Reporte financiero</a>
                    están aquí para que las revises.
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
    <x-recibo.seccion rotulo="Beneficios" titulo="Lo que cambia el día que la figura jurídica exista">
        @php
            $cambios = [
                [
                    'titulo' => 'Contar con una cuenta bancaria.',
                    'texto' => 'A la fecha no contamos con un instrumento bancario en donde se puedan recibir las cuotas por
                                lo que se ha estado manejando solo efectivo, lo que dificulta la administración de los recursos
                                económicos.
                                Con la creación de la asociación se contará con una cuenta bancaria perteneciente al
                                fraccionamiento, sin importar quién entra y sale de la Mesa Directiva, la cuenta (y el dinero) se queda.',
                ],
                [
                    'titulo' => 'Se podrán hacer contratos con los prestadores de servicios.',
                    'texto' => 'Actualmente los servicios de vigilancia, jardinería, mantenimiento, etc., se realizan mediante
                                acuerdos de palabra y algún representante de la administración, a título personal, los firma.
                                Una Asociación Civil contrata, exige lo pactado y tiene con qué reclamar cuando un proveedor no cumple.',
                ],
                [
                    'titulo' => 'La cuota se vuelve exigible',
                    'texto' => 'Hoy quien no paga simplemente no paga, y lo que deja de aportar lo terminan cubriendo
                                los vecinos que sí pagaron. Se trata de establecer que así como tendremos derechos,
                                también tendremos obligaciones que cumplir cada uno de los asociados, siempre actuando
                                 en beneficio de todos.',
                ],
                [
                    'titulo' => 'Rendir cuentas deja de ser un gesto de buena voluntad',
                    'texto' => 'Una Asociación Civil está obligada por sus estatutos a llevar cuentas y a presentarlas
                                ante su Asamblea y ante los Colonos. Lo que hoy hacemos porque nos parece lo correcto —esta página, entre
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
                'El fraccionamiento no se le entrega a nadie de fuera.' => 'De entrada, los asociados son los propietarios de Vista
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

    {{--
        La única petición de la página. Va después de «Lo que no cambia» a
        propósito: pedirle algo al Colono antes de haber contestado lo que teme
        se lee como reclutamiento. El Comité se explica antes de pedir a nadie
        que lo ocupe, porque nadie sabe qué es y tres lugares vacíos no se
        llenan pidiendo un cheque en blanco.
    --}}
    <x-recibo.seccion id="participar" rotulo="Participar" titulo="Lo que necesitamos de ti">
        <p class="text-grafito/85">
            La Asociación no la forma sola la Mesa Directiva. Además de las cuatro personas que hoy la integran, la
            propuesta contempla un <strong class="font-semibold">Comité de Supervisión de tres personas</strong>: los
            vecinos que revisan las cuentas y el trabajo de la Mesa, y que pueden pedirle explicaciones.
        </p>
        <p class="mt-4 text-grafito/85">
            No es un cargo honorífico. Existe para vigilar a quien administra, y por eso no puede ocuparlo nadie de la
            propia Mesa Directiva.
        </p>
        <p class="mt-4 text-grafito/85">
            Esas tres personas todavía no están. Si te interesa, déjalo en un
            <a href="#comentarios" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">comentario aquí abajo</a>.
        </p>
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
            La Asamblea es donde muchas cosas se deciden, pero no tiene por qué ser el único lugar donde se pregunte. Si algo
            de la Propuesta no te cuadra, escríbelo aquí: la Mesa Directiva lo lee y lo contesta antes de tomar alguna acción.
        </p>

        <div class="mt-8">
            @if ($recepcionAbierta)
                {{--
                    Los dos interruptores del panel, en el orden en que mandan: si
                    la Recepción está abierta, la Vía decide por dónde se recibe.
                    Cerrada, la vía es irrelevante y ninguno de los dos se muestra.
                --}}
                <x-recibo.tarjeta>
                    @if ($via->esOtp())
                        <x-comentarios.formulario :telefono-validado="$telefonoValidado"
                                                  :telefono-pendiente="$telefonoPendiente" />
                    @else
                        <x-comentarios.whatsapp :enlace="$via->enlaceDeWhatsApp()"
                                                :numero="$via->numeroLegible()" />
                    @endif
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
