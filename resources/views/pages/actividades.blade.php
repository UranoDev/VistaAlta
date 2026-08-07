{{--
    Lo que la Mesa Directiva llevó a cabo durante el Periodo, más reciente
    primero. Se lee entera en esta página: no hay detalle al que entrar, ni
    documento que descargar.

    El texto de la Bitácora y el de «Lo que sigue» pasan por `TextoConLigas` y no
    por el escape normal de Blade. Lo único que eso habilita es una liga interna
    escrita `[texto](/ruta)` desde el panel — hizo falta cuando una Actividad
    tuvo que mandar al lector a otra página del sitio con una frase en vez de con
    una URL pegada a media línea. Sigue siendo texto plano para todo lo demás, y
    el escape ocurre **antes** de buscar el patrón, así que nada de lo capturado
    puede salir como HTML.
--}}
@use('App\Support\Contenido\TextoConLigas')

<x-layout.app title="Actividades">
    <x-palette-receipt.seccion rotulo="Lo realizado en el Periodo" titulo="Actividades">
        <div class="mt-8 flex items-baseline justify-between gap-4 border-b border-linea pb-2">
            <h3 class="text-lg font-bold tracking-tight">Mantener el fraccionamiento funcionando</h3>
        </div>

        <p class="text-grafito/85">
            Como equipo de Administración tenemos la obligación de mostrar qué se hizo con lo que se nos confió.<br><br>
            Esto es lo que se ha hecho en el fraccionamiento durante estos casi tres meses. <br><br>El primer reto fue mantener el fraccionamiento en funcionamiento. No ha sido fácil, pero se ha logrado.<br><br>
            Las actividades se listan sin ningún orden determinado.
        </p>

        <div class="mt-8 flex items-baseline justify-between gap-4 border-b border-linea pb-2">
            <h3 class="text-lg font-bold tracking-tight">Bitácora</h3>
            <span class="cifra text-xs text-grafito/70">
                {{ trans_choice('{0}ninguna|{1}:count actividad|[2,*]:count actividades', $actividades->count(), ['count' => $actividades->count()]) }}
            </span>
        </div>

        @if ($actividades->isNotEmpty())
            {{--
                Un renglón por Actividad, con la fecha dibujada una sola vez por día:
                la fecha encabeza lo que pasó ese día en vez de sellarse en cada
                renglón. La agrupación se ve en los separadores —línea sólida de ancho
                completo entre días, punteada entre Actividades del mismo día—, y en
                escritorio la punteada arranca donde arrancan las descripciones, porque
                cuelga de la segunda columna; abajo de `sm` la rejilla colapsa y la
                distinción queda solo en el trazo.

                La rejilla es de la Actividad y no del día porque la marca de novedad
                va en la columna de la fecha, y esa marca es por Actividad: `created_at`
                es de cada renglón, y una Actividad de junio capturada hoy es nueva para
                el lector aunque comparta fecha con otras viejas. Colgar la marca del
                día entero mentiría en ese caso, que es justo el que se da al ponerse al
                corriente con la captura.

                Se agrupa aquí y no en el controlador para que `$actividades` le llegue
                entera al contador del encabezado: cuenta Actividades, no días.
            --}}
            <ol class="border-b border-linea">
                @foreach ($actividades->groupBy(fn ($actividad) => $actividad->fecha->toDateString()) as $dia => $actividadesDelDia)
                    @foreach ($actividadesDelDia as $actividad)
                        @php($abreElDia = $loop->first)
                        {{--
                            `[border-left-style:solid]` no es un capricho: el
                            `border-dotted` del separador entre Actividades del mismo
                            día es la propiedad de las cuatro orillas, así que sin esto
                            el listón de novedad sale punteado y deja de leerse como
                            listón.
                        --}}
                        <li @class([
                                'grid gap-1.5 sm:grid-cols-[8.5rem_1fr] sm:gap-6',
                                'border-t border-linea pt-4 first:border-t-0' => $abreElDia,
                                'mt-3 border-t border-dotted border-linea pt-3 sm:mt-0 sm:border-t-0 sm:pt-0' => ! $abreElDia,
                                'pb-4' => $loop->last,
                                'border-l-[3px] border-l-menta pl-3 [border-left-style:solid]' => $actividad->esNuevo(),
                            ])>
                            <div @class(['sm:mt-3 sm:pt-3' => ! $abreElDia])>
                                @if ($actividad->esNuevo())
                                    <x-palette-receipt.marca>Se agregó</x-palette-receipt.marca>
                                @endif

                                @if ($abreElDia)
                                    <time datetime="{{ $dia }}"
                                          class="cifra text-xs font-semibold uppercase tracking-[0.08em] text-tinta">
                                        {{ $actividad->fecha->translatedFormat('j M Y') }}
                                    </time>
                                @endif
                            </div>

                            <div @class([
                                    'text-sm text-grafito/85',
                                    'sm:mt-3 sm:border-t sm:border-dotted sm:border-linea sm:pt-3' => ! $abreElDia,
                                ])>
                                <span class="block whitespace-pre-line">{{ TextoConLigas::aHtml($actividad->descripcion) }}</span>
                            </div>
                        </li>
                    @endforeach
                @endforeach
            </ol>
        @else
            <p class="py-4 text-sm text-grafito/70">
                Todavía no hay actividades publicadas para este periodo. Aparecen aquí conforme la Mesa Directiva
                las captura.
            </p>
        @endif

        {{--
            La contraparte de la Bitácora. Va después y no antes porque el orden
            es el argumento —primero lo que ya se hizo, y solo entonces lo que
            falta; al revés se lee como una lista de promesas.

            Sin casilla de verificación a propósito, y sin sangría: un cuadrito
            vacío por renglón convierte la rendición de cuentas en una lista de
            tareas, y arriba la Bitácora tampoco marca nada. Lo que separa un
            pendiente del siguiente es la misma línea que separa un día del
            siguiente, que es lo que hace que las dos mitades se lean como una
            sola página.

            Sin fechas a propósito: varios de estos pendientes dependen de un
            tercero, y comprometer una fecha que no se controla es prometer de
            más.
        --}}
        {{--
            Los que siguen abiertos, aparte de la lista que se pinta. La lista
            incluye a los cumplidos hace poco —tachados—, pero el conteo y la
            frase del final hablan solo de lo que falta: un encabezado que diga
            «6 pendientes» sobre una lista con uno tachado está contando mal, y
            «el primero es el más importante» apuntando a algo ya cumplido manda
            a la Asamblea a leer la línea equivocada.
        --}}
        @php($abiertos = $pendientes->reject->estaCumplido())

        <div class="mt-10 flex items-baseline justify-between gap-4 border-b border-linea pb-2">
            <h3 class="text-lg font-bold tracking-tight">Lo que sigue</h3>
            <span class="cifra text-xs text-grafito/70">
                {{ trans_choice('{0}ninguno|{1}:count pendiente|[2,*]:count pendientes', $abiertos->count(), ['count' => $abiertos->count()]) }}
            </span>
        </div>

        <p class="mt-4 text-grafito/85">
            Arriba está lo hecho; esto es lo que falta. Ninguno lleva fecha comprometida: la notaría, la Fraccionadora
            y los proveedores llevan su propio paso. Cada pendiente que se cumpla sube a la Bitácora, ya con el día en
            que se hizo.
        </p>

        @if ($pendientes->isNotEmpty())
            <ul class="mt-6 border-b border-linea">
                @foreach ($pendientes as $pendiente)
                    {{--
                        Un pendiente cumplido se queda unos días, tachado y en su
                        lugar de siempre: tachado donde el lector lo recuerda es
                        lo que le dice «esto se cerró». Un renglón que
                        simplemente desaparece no distingue entre cumplirse y
                        abandonarse.

                        El tachado va solo en el título. Un párrafo entero
                        cruzado por una línea cuesta leerlo, y no hay razón para
                        cobrarle eso a quien quiere enterarse de qué se hizo.
                    --}}
                    <li @class([
                            'border-t border-linea py-4 first:border-t-0',
                            'border-l-[3px] border-l-menta pl-3' => $pendiente->esNuevo() || $pendiente->estaCumplido(),
                        ])>
                        @if ($pendiente->estaCumplido())
                            <x-palette-receipt.marca>Se cumplió</x-palette-receipt.marca>
                        @elseif ($pendiente->esNuevo())
                            <x-palette-receipt.marca>Se agregó</x-palette-receipt.marca>
                        @endif

                        <h4 @class(['font-semibold', 'text-grafito/55 line-through' => $pendiente->estaCumplido()])>{{ $pendiente->titulo }}</h4>
                        <p @class([
                            'mt-1 whitespace-pre-line text-sm',
                            $pendiente->estaCumplido() ? 'text-grafito/55' : 'text-grafito/85',
                        ])>{{ TextoConLigas::aHtml($pendiente->detalle) }}</p>
                    </li>
                @endforeach
            </ul>
        @else
            {{--
                Antes no podía pasar, con la lista escrita en esta misma vista.
                Ahora que se mantiene desde el panel sí: sin esto el encabezado
                quedaría colgando con «0 pendientes» y nada debajo.
            --}}
            <p class="py-4 text-sm text-grafito/70">
                No hay pendientes publicados en este momento.
            </p>
        @endif

        {{--
            Aquí no aparece cuánto costó cada Actividad, y no es un olvido: el
            dinero se rinde en un solo lugar. Decirlo evita que la Asamblea lo
            lea como una omisión.
        --}}
        <p class="mt-8 text-grafito/85">
            {{--
                Se nombra en vez de decir «el primero». Desde que un cumplido se
                queda tachado unos días, el primer renglón de la lista puede ser
                justamente el que ya se hizo, y la frase mandaría a la Asamblea a
                leer la línea equivocada. Nombrarlo no depende de la posición.
            --}}
            @if ($abiertos->isNotEmpty())
                De los pendientes de arriba, «{{ $abiertos->first()->titulo }}» es el más importante. Por
                eso les pedimos que vean nuestra
            @else
                Les pedimos que vean nuestra
            @endif
            <a href="{{ route('propuesta') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Propuesta</a>
            de constituir la Asociación Civil.
        </p>

        <x-palette-receipt.nota class="mt-8">
            La transparencia en el manejo del dinero se rinde completo y en un solo
            lugar —el
            <a href="{{ route('reporte-financiero') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Reporte financiero</a>
            del periodo.
        </x-palette-receipt.nota>
    </x-palette-receipt.seccion>
</x-layout.app>
