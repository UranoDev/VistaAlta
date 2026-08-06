@php
    // Las páginas del sitio. El orden es el del argumento: primero la
    // Propuesta, luego lo que la respalda. Demanda va al final porque no
    // respalda a la Propuesta — pide algo distinto, y entrar por ahí dejaría
    // la petición antes que el asunto que se somete a la Asamblea.
    //
    // La etiqueta se mantiene corta a propósito: con cinco entradas el menú
    // ya se aprieta en móvil, y aquí lo que salva el renglón es el largo de
    // cada una, no el `flex-wrap`.
    //
    // Vigilancia entró como cuarta y no como última: también rinde cuentas —del
    // servicio que se paga con la cuota— y Demanda conserva el final por lo
    // dicho arriba.
    $navegacion = [
        ['ruta' => 'propuesta', 'etiqueta' => 'Propuesta'],
        ['ruta' => 'actividades', 'etiqueta' => 'Actividades'],
        ['ruta' => 'reporte-financiero', 'etiqueta' => 'Reporte financiero'],
        ['ruta' => 'vigilancia', 'etiqueta' => 'Vigilancia'],
        ['ruta' => 'demanda', 'etiqueta' => 'Demanda'],
    ];
@endphp

<header class="border-b border-linea bg-papel-alto">
    <div class="mx-auto w-full max-w-5xl px-5 py-4 sm:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('propuesta') }}" class="group flex items-baseline gap-2.5">
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
                        @php $activo = request()->routeIs($enlace['ruta']); @endphp
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
