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
                Un grupo por día, no una fila por Actividad: la fecha encabeza lo que
                pasó ese día en vez de sellarse en cada renglón. El `<time>` vive en el
                grupo y las descripciones de ese día van dentro, así que la asociación
                entre una descripción y su fecha sigue en el marcado aunque la fecha se
                dibuje una sola vez.

                Se agrupa aquí y no en el controlador para que `$actividades` le llegue
                entera al contador del encabezado: cuenta Actividades, no días.

                Dos pesos de separador, y la diferencia es la que hace leer los días
                como bloques: línea sólida de ancho completo entre días, punteada entre
                Actividades del mismo día. En escritorio la punteada además arranca
                donde arrancan las descripciones, porque cuelga de la segunda columna
                de la rejilla; abajo de `sm` la rejilla colapsa y la distinción queda
                solo en el trazo.
            --}}
            <ol class="border-b border-linea">
                @foreach ($actividades->groupBy(fn ($actividad) => $actividad->fecha->toDateString()) as $dia => $actividadesDelDia)
                    <li class="grid gap-1.5 border-t border-linea py-4 first:border-t-0 sm:grid-cols-[8.5rem_1fr] sm:gap-6">
                        <time datetime="{{ $dia }}"
                              class="cifra text-xs font-semibold uppercase tracking-[0.08em] text-tinta">
                            {{ $actividadesDelDia->first()->fecha->translatedFormat('j M Y') }}
                        </time>
                        <ol>
                            @foreach ($actividadesDelDia as $actividad)
                                <li class="mt-3 whitespace-pre-line border-t border-dotted border-linea pt-3 text-sm text-grafito/85 first:mt-0 first:border-t-0 first:pt-0">{{ TextoConLigas::aHtml($actividad->descripcion) }}</li>
                            @endforeach
                        </ol>
                    </li>
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
        <div class="mt-10 flex items-baseline justify-between gap-4 border-b border-linea pb-2">
            <h3 class="text-lg font-bold tracking-tight">Lo que sigue</h3>
            <span class="cifra text-xs text-grafito/70">
                {{ trans_choice('{1}:count pendiente|[2,*]:count pendientes', $pendientes->count(), ['count' => $pendientes->count()]) }}
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
                    <li class="border-t border-linea py-4 first:border-t-0">
                        <h4 class="font-semibold">{{ $pendiente->titulo }}</h4>
                        <p class="mt-1 whitespace-pre-line text-sm text-grafito/85">{{ TextoConLigas::aHtml($pendiente->detalle) }}</p>
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
            {{-- Sin lista arriba, la referencia a «el primero» apuntaría a nada. --}}
            @if ($pendientes->isNotEmpty())
                De los pendientes de arriba, el primero es el más importante. Por
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
