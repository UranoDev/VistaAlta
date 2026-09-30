{{--
    La rendición de cuentas económica de un mes: el resumen de cifras que el
    sitio muestra y el enlace a la hoja de cálculo donde vive el detalle.

    La misma vista sirve el mes vigente y cualquier mes del archivo
    (docs/adr/0005). La única diferencia es el aviso de arriba: un reporte que ya
    no es el más reciente tiene que decirlo, o se lee como si fuera el estado de
    hoy.

    Pública y sin ninguna barrera (docs/adr/0004): aquí no se pide contraseña ni
    clave compartida, y no es un descuido.
--}}
@php($titulo = $esVigente ? 'Reporte financiero' : 'Reporte financiero · '.$reporte->periodo)

<x-layout.app :title="$titulo" :canonical="$canonical">
    <x-palette-receipt.seccion rotulo="Rendición de cuentas del Periodo" titulo="Reporte financiero">
        <p class="text-grafito/85">
            De dónde salió y en qué se fue el dinero del fraccionamiento durante el mes. Abajo van las cifras de
            resumen; el detalle, movimiento por movimiento, está completo en la hoja de cálculo y se abre sin pedir
            nada a nadie.
        </p>

        @if ($reporte->periodo)
            <x-palette-receipt.rotulo class="mt-6">Periodo: {{ $reporte->periodo }}</x-palette-receipt.rotulo>
        @endif

        @unless ($esVigente)
            {{--
                Un mes del archivo se ve idéntico al vigente, así que decirlo es
                lo único que evita que alguien lea cifras viejas como si fueran
                las de hoy. Va antes de las cifras, no después.
            --}}
            <x-palette-receipt.nota variante="aviso" class="mt-4">
                Este es el reporte de {{ $reporte->periodo }} y ya no es el más reciente. Se conserva tal como se
                rindió.
                <a href="{{ route('reporte-financiero') }}"
                   class="font-medium underline underline-offset-2">Ver el reporte vigente</a>.
            </x-palette-receipt.nota>
        @endunless

        @if ($reporte->estaVacio())
            {{-- Todavía no se ha capturado nada: se dice, en vez de mostrar un comprobante en blanco. --}}
            <div class="mt-8 border border-dashed border-linea bg-papel-alto px-6 py-10 text-center text-sm text-grafito/70">
                El reporte financiero de este periodo se publica aquí.
            </div>
        @endif

        @if ($reporte->tieneResumen())
            <div class="mt-8">
                <div class="flex items-baseline justify-between gap-4 border-b border-linea pb-2">
                    <h3 class="text-lg font-bold tracking-tight">Resumen</h3>
                    <span class="cifra text-xs uppercase tracking-[0.08em] text-grafito/70">
                        Pesos MXN
                    </span>
                </div>

                <x-palette-receipt.tarjeta class="mt-4">
                    @foreach ($reporte->resumen() as $cifra)
                        {{-- El renglón del total va destacado, como en un comprobante. --}}
                        <x-palette-receipt.renglon :concepto="$cifra->concepto"
                                          class="{{ $cifra->destacada ? 'mt-1 border-t-2 border-t-tinta pt-3 text-base font-semibold text-tinta' : '' }}">
                            {{ $cifra->montoFormateado() }}
                        </x-palette-receipt.renglon>
                    @endforeach
                </x-palette-receipt.tarjeta>

                {{--
                    Lo que las cifras no dicen solas. Va pegada a la tarjeta y
                    antes de la nota de procedencia porque matiza los números que
                    se acaban de leer: un remanente inflado por un ingreso que no
                    se repite se malinterpreta si nadie lo advierte a tiempo.
                --}}
                @if ($reporte->tieneAclaracion())
                    <x-palette-receipt.nota variante="aviso" class="mt-4">
                        {{ $reporte->aclaracion }}
                    </x-palette-receipt.nota>
                @endif

                {{--
                    El resumen es un resumen: si alguien busca de dónde sale una
                    cifra, el lugar es la hoja, no esta página.
                --}}
                {{--
                    Cómo se cuentan los pagos. Es una nota **común**: sale igual en
                    todos los meses, con el nombre del que se está leyendo, y no se
                    captura. Va aquí y no en la aclaración porque la aclaración es
                    lo que un mes puntual tiene de distinto, y esto no lo es: es el
                    criterio con el que se arma cualquier mes. Dicho en la página
                    evita que un pago adelantado se busque en el mes al que
                    corresponde y no en el que llegó el dinero.
                --}}
                @php($mesQueSeRinde = $reporte->mesEnPalabras())
                @if ($mesQueSeRinde)
                    <x-palette-receipt.nota class="mt-4">
                        Los pagos que se reflejan aquí son los que se recibieron en {{ $mesQueSeRinde }}. Si alguien pagó
                        por adelantado antes de {{ $mesQueSeRinde }}, ese pago quedó registrado en el mes en que se
                        recibió el dinero.
                    </x-palette-receipt.nota>
                @endif

                <x-palette-receipt.nota class="mt-4">
                    Estas son cifras de resumen, capturadas a mano por la Administración. Si quieres saber de dónde
                    sale alguna, el lugar no es esta página: es la hoja de cálculo, donde está cada movimiento con su
                    fecha y su concepto.
                </x-palette-receipt.nota>
            </div>
        @endif

        @if ($reporte->tieneDesglose())
            {{--
                Dos enlaces al mismo contenido: el PDF, que se lee mejor en el
                celular, y la hoja, que es la fuente de verdad. Son dos formas de
                ver lo mismo y no una acción principal y otra secundaria, así que
                los dos llevan relleno y pesan igual; el PDF va primero porque es
                por donde la mayoría va a querer entrar. Cuál de los dos vale lo
                dice el párrafo de arriba, no el estilo del botón.

                Se asignan con la directiva de una línea y no con un bloque de
                varias, porque este archivo ya trae una de una línea arriba y
                Blade empareja el primer inicio de bloque con el primer cierre:
                el bloque se tragaba todo lo que hay en medio como PHP crudo. Ojo
                también con escribir aquí, en un comentario, el nombre de esas
                directivas con su arroba: Blade las busca antes de quitar los
                comentarios y se confunde igual.
            --}}
            @php($conPdfYHoja = $reporte->tienePdf() && $reporte->tieneHoja())
            @php($dominios = collect([$reporte->dominioDelPdf(), $reporte->dominioDeLaHoja()])->filter()->unique()->values())

            <div class="mt-10 border-t border-linea pt-8">
                <h3 class="text-lg font-bold tracking-tight">Desglose completo</h3>
                <p class="mt-2 text-sm text-grafito/80">
                    @if ($conPdfYHoja)
                        Cada ingreso y cada gasto del periodo, en dos formatos con la misma información: un PDF, más
                        cómodo de revisar, y la hoja de cálculo. La hoja es la fuente de verdad de este reporte: si una
                        cifra de arriba y el PDF no coinciden con la hoja, la que vale es la hoja.
                    @elseif ($reporte->tienePdf())
                        Cada ingreso y cada gasto del periodo, en un PDF. Si una cifra de arriba y el PDF no coinciden,
                        la que vale es el PDF.
                    @else
                        Cada ingreso y cada gasto del periodo. La hoja de cálculo es la fuente
                        de verdad de este reporte: si una cifra de arriba y la hoja no coinciden, la que vale es la hoja.
                    @endif
                </p>

                {{--
                    Salen del sitio: abren en pestaña nueva y lo dicen antes de
                    que alguien los toque, con el dominio a la vista.
                --}}
                <div class="mt-4 flex flex-wrap gap-3">
                    @if ($reporte->tienePdf())
                        <x-palette-receipt.boton :href="$reporte->pdf_url"
                                        target="_blank"
                                        rel="noopener noreferrer">
                            Ver el desglose en PDF
                            <span aria-hidden="true">&nearr;</span>
                        </x-palette-receipt.boton>
                    @endif

                    @if ($reporte->tieneHoja())
                        <x-palette-receipt.boton :href="$reporte->hoja_url"
                                        target="_blank"
                                        rel="noopener noreferrer">
                            Ver el desglose en la hoja de cálculo
                            <span aria-hidden="true">&nearr;</span>
                        </x-palette-receipt.boton>
                    @endif
                </div>

                <p class="mt-3 text-xs text-grafito/70">
                    {{ $conPdfYHoja ? 'Se abren' : 'Se abre' }} en una pestaña nueva, fuera de este sitio{{ $dominios->isNotEmpty() ? ' ('.$dominios->join(', ', ' y ').')' : '' }}.
                </p>
            </div>
        @endif

        {{--
            El índice del archivo. Con un solo mes publicado no aparece: una
            lista de un renglón que repite lo que ya está arriba es ruido, no
            navegación.

            Están todos los meses, incluido el vigente, y no solo «los
            anteriores»: así la lista es la misma se entre por donde se entre, y
            desde un mes viejo se ve de un vistazo dónde queda uno parado. El mes
            que se está leyendo no lleva enlace —ya se está ahí— y el vigente
            apunta a la raíz, que es su dirección canónica.
        --}}
        @if ($meses->count() > 1)
            <div class="mt-10 border-t border-linea pt-8">
                <div class="flex items-baseline justify-between gap-4 border-b border-linea pb-2">
                    <h3 class="text-lg font-bold tracking-tight">Meses publicados</h3>
                    <span class="cifra text-xs text-grafito/70">
                        {{ trans_choice('{1}:count mes|[2,*]:count meses', $meses->count(), ['count' => $meses->count()]) }}
                    </span>
                </div>

                <p class="mt-4 text-sm text-grafito/80">
                    Cada mes que se rinde se queda publicado y se puede volver a consultar. Ninguno se corrige hacia
                    atrás para que cuadre con otro: lo que se rindió en su momento es lo que sigue ahí.
                </p>

                <ul class="mt-4 border-b border-linea">
                    @foreach ($meses as $indice => $otro)
                        @php($enPantalla = $reporte->exists && $otro->is($reporte))
                        <li class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-t border-linea py-3 first:border-t-0">
                            @if ($enPantalla)
                                <span aria-current="page" class="text-sm font-semibold text-tinta">
                                    {{ $otro->periodo }}
                                </span>
                            @else
                                <a href="{{ $indice === 0 ? route('reporte-financiero') : route('reporte-financiero.mes', ['mes' => $otro->mesEnUrl()]) }}"
                                   class="text-sm font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">
                                    {{ $otro->periodo }}
                                </a>
                            @endif

                            @if ($indice === 0)
                                <span class="cifra text-[0.6875rem] uppercase tracking-[0.14em] text-grafito/60">
                                    Vigente
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-palette-receipt.seccion>
</x-layout.app>
