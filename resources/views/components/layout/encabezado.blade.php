@php
    // Las páginas del sitio, en el orden en que se abren. Primero la cuenta del
    // mes —la portada, desde que `/` redirige ahí (URVA-95)—, luego lo que se
    // hizo, quién cuida y quiénes sirven. Demanda conserva el final porque es la
    // única que pide algo en vez de contar algo, y entrar por ahí dejaría la
    // petición antes que las cuentas.
    //
    // La etiqueta se mantiene corta a propósito: con seis entradas el menú ya
    // se aprieta en móvil, y aquí lo que salva el renglón es el largo de cada
    // una, no el `flex-wrap`.
    //
    // Convivencia entró tercera, entre Actividades y Vigilancia (URVA-97). Va
    // ahí y no al final porque se lee, no se pide: las primeras son lo que la
    // Mesa Directiva le cuenta al Colono, y la última es lo que le pide.
    //
    // La entrada queda marcada como activa también dentro de un post, no solo en
    // el índice: `routeIs` se pregunta con comodín para que `/convivencia/loquesea`
    // siga señalando de qué sección es la página que se está leyendo.
    //
    // Administración entró junto a Vigilancia (URVA-99) porque hacen juego:
    // quién cuida el acceso y quiénes ocupan los cargos. La etiqueta es la más
    // larga de las seis y no se acorta a «Cargos» ni a «Mesa Directiva» —
    // «Administración» es como se llama el órgano en el acta constitutiva.
    //
    // **Propuesta salió del menú** en el mismo cambio, y por eso el menú sigue
    // teniendo seis entradas. Lo que sometía a consideración de la Asamblea ya
    // está en trámite —el nombre de la asociación quedó autorizado— y por dónde
    // va se rinde en Administración. La página sigue publicada y sigue
    // recibiendo Comentarios; se entra por la liga que le deja «Lo que sigue»
    // en `/actividades`, que es donde tiene sentido buscarla ahora. Si la
    // Asamblea vuelve a someter algo a consideración, la entrada regresa.
    $navegacion = [
        ['ruta' => 'reporte-financiero', 'etiqueta' => 'Reporte financiero'],
        ['ruta' => 'actividades', 'etiqueta' => 'Actividades'],
        ['ruta' => 'convivencia', 'etiqueta' => 'Convivencia'],
        ['ruta' => 'vigilancia', 'etiqueta' => 'Vigilancia'],
        ['ruta' => 'administracion', 'etiqueta' => 'Administración'],
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
