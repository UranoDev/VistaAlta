@php
    // Las páginas del sitio, en el orden en que se abren. Primero la cuenta del
    // mes —la portada, desde que `/` redirige ahí (URVA-95)—, luego lo que se
    // hizo y quién cuida. La Propuesta bajó de primera a penúltima: sigue
    // entera, con su formulario de Comentarios, pero ya no es por donde se
    // entra. Demanda conserva el final porque no respalda a la Propuesta —pide
    // algo distinto, y entrar por ahí dejaría la petición antes que el asunto
    // que se somete a la Asamblea.
    //
    // La etiqueta se mantiene corta a propósito: con seis entradas el menú ya
    // se aprieta en móvil, y aquí lo que salva el renglón es el largo de cada
    // una, no el `flex-wrap`.
    //
    // Convivencia entró tercera, entre Actividades y Vigilancia (URVA-97): es la
    // sexta entrada, una más de las cinco que ya apretaban, y es una decisión
    // tomada a sabiendas. Va ahí y no al final porque se lee, no se pide: las
    // tres primeras son lo que la Mesa Directiva le cuenta al Colono, y las dos
    // últimas son lo que le pide.
    //
    // La entrada queda marcada como activa también dentro de un post, no solo en
    // el índice: `routeIs` se pregunta con comodín para que `/convivencia/loquesea`
    // siga señalando de qué sección es la página que se está leyendo.
    $navegacion = [
        ['ruta' => 'reporte-financiero', 'etiqueta' => 'Reporte financiero'],
        ['ruta' => 'actividades', 'etiqueta' => 'Actividades'],
        ['ruta' => 'convivencia', 'etiqueta' => 'Convivencia'],
        ['ruta' => 'vigilancia', 'etiqueta' => 'Vigilancia'],
        ['ruta' => 'propuesta', 'etiqueta' => 'Propuesta'],
        ['ruta' => 'demanda', 'etiqueta' => 'Demanda'],
    ];
@endphp

<header class="border-b border-linea bg-papel-alto">
    <div class="mx-auto w-full max-w-5xl px-5 py-4 sm:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            {{--
                El logo lleva a la portada, que desde URVA-95 es el Reporte
                financiero. Apunta al destino y no a `/`: la raíz llega igual,
                pero de rebote, y no hay razón para gastar un salto de
                redirección en el enlace que más se toca del sitio.
            --}}
            <a href="{{ route('reporte-financiero') }}" class="group flex items-baseline gap-2.5">
                <span class="text-lg font-bold tracking-tight text-tinta group-hover:text-tinta-suave">
                    Vista Alta
                </span>
                <span class="cifra text-[0.6875rem] uppercase tracking-[0.14em] text-grafito/60">
                    Mesa Directiva
                </span>
            </a>

            <nav aria-label="Secciones del sitio">
                <ul class="-mx-1 flex flex-wrap items-center gap-x-1 gap-y-1 text-sm">
                    @foreach ($navegacion as $enlace)
                        @php $activo = request()->routeIs($enlace['ruta'], $enlace['ruta'].'.*'); @endphp
                        <li>
                            <a href="{{ route($enlace['ruta']) }}"
                               @if ($activo) aria-current="page" @endif
                               class="block rounded px-3 py-1.5 font-medium transition-colors
                                      {{ $activo
                                          ? 'bg-tinta text-papel'
                                          : 'text-grafito/80 hover:bg-menta/50 hover:text-tinta' }}">
                                {{ $enlace['etiqueta'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>
</header>
