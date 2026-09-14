{{--
    Quiénes sirven al fraccionamiento: el trámite de la asociación civil y los
    dos órganos que la gobiernan.

    Es la segunda página del sitio que publica datos personales —la otra es
    Vigilancia— y la regla de allá **no** se copia aquí. Allá va nombre de pila
    e inicial porque son trabajadores a los que el apellido solo vuelve
    buscables; aquí van los nombres completos porque son cargos electos ante la
    Asamblea y el acta constitutiva los va a asentar así. La consecuencia es que
    esta página sí se deja indexar, y por eso las siete personas tienen que
    saber que su nombre y su foto se publican.

    Tres decisiones de redacción que no se deshacen sin volver a tomarlas:

    1. **El trámite se publica como trámite, no como hecho.** Lo único
       consumado es la autorización del nombre; el acta, el RFC y la cuenta
       todavía no. El sello dice «Nombre autorizado» y no «Registrada», y cada
       paso trae su estado. Si alguien cambia esto por un «ya quedó», la página
       promete algo que no ocurrió.

    2. **El Comité de Vigilancia va primero.** Es el contrapeso: leerlo antes
       que a quien supervisa es el orden que le da sentido. De paso aclara que
       no es el equipo de la caseta, que vive en `/vigilancia` y no tiene nada
       que ver.

    3. **Las siete tarjetas son el mismo objeto.** Mismo ancho, mismo alto,
       mismo retrato — incluida la de la Administradora, cuya jerarquía la
       marcan el filete de tinta y su renglón propio, no el tamaño. Por eso la
       línea de «qué hace» no vive en la tarjeta sino en la lista de abajo: dos
       órganos con tarjetas de distinto alto se leen como un error.

    Todo el contenido sale de `config/contenido.php`; el controlador arma los
    integrantes y aquí solo se pintan.
--}}
@php
    // El chip de estado de cada paso del trámite. `sigue` es el que está en
    // curso y lleva filete entero; `falta` va punteado, como el renglón de un
    // recibo que todavía no se llena.
    $chips = [
        'listo' => 'bg-menta text-tinta',
        'sigue' => 'border border-tinta text-tinta',
        'falta' => 'border border-dashed border-linea text-grafito/55',
    ];

    $rotulos = ['listo' => 'Listo', 'sigue' => 'Sigue', 'falta' => 'Falta'];
@endphp

<x-layout.app title="Administración"
              descripcion="Quiénes servimos a Vista Alta: el Comité de Vigilancia, la Administración y cómo va el registro de la asociación civil.">

    {{-- Por dónde va el trámite, que es la novedad y lo que enmarca a los dos órganos. --}}
    <x-palette-receipt.seccion rotulo="Administración" titulo="Quiénes servimos a Vista Alta">
        <div class="flex flex-col gap-4 text-grafito/85">
            <p>
                En agosto arrancamos el trámite para registrar la asociación civil del fraccionamiento. Ya nos
                autorizaron el nombre, y quedó como <strong class="font-semibold text-tinta">{{ $razonSocial }}</strong>.
            </p>
            <p>
                Lo que sigue es firmar ante notario el acta constitutiva (esperamos que sea este mes de septiembre) y
                sacar el RFC. Con eso en la mano abrimos la cuenta bancaria, y de ahí en adelante el dinero del
                fraccionamiento va a estar a nombre de la asociación.
            </p>
        </div>

        <x-palette-receipt.tarjeta class="mt-7">
            {{--
                El nombre autorizado va impreso como la cabecera de un ticket:
                centrado y en el tipo más grande de la página. Es lo que se
                consiguió, y el troquel de arriba lo deja leerse como papel
                recién salido de la impresora.
            --}}
            <div class="flex flex-col items-center gap-3 border-b border-dashed border-linea pb-6 text-center">
                <p class="cifra text-[0.6875rem] font-semibold uppercase tracking-[0.14em] text-grafito/60">
                    La asociación se va a llamar
                </p>

                <p class="text-balance text-[clamp(1.875rem,7vw,2.875rem)] font-bold leading-[1.05] tracking-[-0.03em] text-tinta">
                    {{ $razonSocial }}
                </p>

                <x-palette-receipt.sello class="mt-0.5">Nombre autorizado</x-palette-receipt.sello>
            </div>

            <x-palette-receipt.rotulo class="mt-6">Cómo va el trámite</x-palette-receipt.rotulo>

            <ol class="mt-2">
                @foreach ($tramite as $i => $paso)
                    <li class="flex items-baseline gap-3.5 border-b border-dashed border-linea py-2.5 last:border-b-0">
                        <span @class([
                            'cifra w-5 flex-none text-[0.8125rem] font-semibold',
                            'text-tinta' => $paso['estado'] === 'listo',
                            'text-grafito/45' => $paso['estado'] !== 'listo',
                        ])>{{ $i + 1 }}</span>

                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold leading-snug text-tinta">{{ $paso['titulo'] }}</span>
                            <span class="block text-[0.8125rem] text-grafito/65">{{ $paso['detalle'] }}</span>
                        </span>

                        <span class="cifra flex-none px-2 py-0.5 text-[0.6875rem] font-semibold uppercase tracking-[0.08em] {{ $chips[$paso['estado']] ?? $chips['falta'] }}">
                            {{ $rotulos[$paso['estado']] ?? $paso['estado'] }}
                        </span>
                    </li>
                @endforeach
            </ol>
        </x-palette-receipt.tarjeta>

        <div class="mt-8 flex flex-col gap-4 text-grafito/85">
            <p>
                Parte del trámite es dejar asentado cómo se gobierna la asociación. En el acta constitutiva se registran
                dos grupos: el <strong class="font-semibold text-tinta">Comité de Vigilancia</strong>, que supervisa, y
                la <strong class="font-semibold text-tinta">Administración</strong>, que se encarga del día a día.
            </p>
            <p>Estas son las siete personas que los integran y su cargo.</p>
        </div>
    </x-palette-receipt.seccion>

    {{-- El Comité primero: es el contrapeso, y se lee antes que a quien supervisa. --}}
    <x-palette-receipt.seccion rotulo="Comité de Vigilancia" titulo="Es quien supervisa" :lectura="false">
        <p class="max-w-(--container-lectura) text-grafito/85">
            También se le dice Comité de Supervisión. Revisa el padrón, las cuotas, los recibos y los cortes de caja:
            tiene acceso a toda la información. Es el contrapeso de la Administración.
        </p>

        <div class="organigrama mt-7">
            <x-administracion.raiz />

            <ul class="organigrama-fila">
                <li><x-administracion.organo>Comité de Vigilancia</x-administracion.organo></li>
            </ul>

            <div class="organigrama-bajada"></div>

            <ul class="organigrama-fila">
                @foreach ($comite as $integrante)
                    <li><x-administracion.tarjeta :integrante="$integrante" /></li>
                @endforeach
            </ul>
        </div>
    </x-palette-receipt.seccion>

    {{-- La Administración, con su cabeza un nivel arriba del resto. --}}
    <x-palette-receipt.seccion rotulo="Administración" titulo="Es quien lleva el día a día" :lectura="false">
        <p class="max-w-(--container-lectura) text-grafito/85">
            Decide qué se cobra, ejerce el gasto y cada mes publica el
            <a href="{{ route('reporte-financiero') }}" class="font-medium underline underline-offset-2">reporte financiero</a>
            y las
            <a href="{{ route('actividades') }}" class="font-medium underline underline-offset-2">actividades</a>
            de las que hay que rendir cuentas.
        </p>

        <div class="organigrama mt-7">
            <x-administracion.raiz />

            <ul class="organigrama-fila">
                <li><x-administracion.organo>Administración</x-administracion.organo></li>
            </ul>

            <div class="organigrama-bajada"></div>

            @if ($cabeza !== null)
                <ul class="organigrama-fila">
                    <li><x-administracion.tarjeta :integrante="$cabeza" :cabeza="true" /></li>
                </ul>

                <div class="organigrama-bajada"></div>
            @endif

            <ul class="organigrama-fila">
                @foreach ($integrantes as $integrante)
                    <li><x-administracion.tarjeta :integrante="$integrante" /></li>
                @endforeach
            </ul>
        </div>

        @if (filled($cargos))
            <x-palette-receipt.rotulo class="mt-9">Qué hace cada quien</x-palette-receipt.rotulo>

            <ul class="mt-3 max-w-(--container-lectura)">
                @foreach ($cargos as $integrante)
                    <li class="flex flex-col gap-0.5 border-b border-dashed border-linea py-2.5 last:border-b-0 sm:flex-row sm:items-baseline sm:gap-4">
                        <span class="cifra flex-none text-[0.6875rem] font-semibold uppercase tracking-[0.1em] text-tinta sm:w-36">
                            {{ $integrante->cargo }}
                        </span>
                        <span class="flex-1 text-[0.9375rem] text-grafito/80">{{ $integrante->hace }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-palette-receipt.seccion>

    <x-palette-receipt.seccion rotulo="Contacto" titulo="Cómo contactarnos">
        <p class="text-grafito/85">
            En cualquier momento, cuando quieras plantear algo, por favor mándanoslo por correo a
            <a href="mailto:{{ config('contenido.correo_contacto') }}" class="font-medium underline underline-offset-2">{{ config('contenido.correo_contacto') }}</a>.
            Es el único buzón por el que atendemos, y ahí lo leemos todos.
        </p>
    </x-palette-receipt.seccion>
</x-layout.app>
