{{--
    Quién cuida el fraccionamiento (URVA-79).

    Es la única página del sitio que publica datos personales de terceros
    —nombre y cara de cuatro trabajadores—, y de ahí salen casi todas sus reglas
    de redacción. Las tres que no se pueden deshacer sin volver a decidirlas:

    1. **No se imprime ningún horario.** Las horas están en la configuración
       porque sin ellas no se puede calcular quién está de guardia, pero no
       aparecen aquí, y tampoco la hora del relevo. Quien consulte cuatro veces
       una página que dice «hasta las 06:00» ya reconstruyó el rol completo, y
       son cuatro personas cubriendo un acceso. Lo que se lee es el rótulo del
       turno.

    2. **No se publica la liga del grupo de WhatsApp.** Una liga de invitación
       es una credencial al portador: quien la tenga entra, desde donde sea y
       para siempre, y hay quien las cosecha de páginas públicas. La página
       nombra el grupo y manda con la Mesa Directiva a quien no esté en él.

    3. **Nombre de pila e inicial**, y la foto solo de quien aceptó que se
       publique. Sin foto se dibuja el monograma y la tarjeta no se ve
       incompleta: que alguien prefiera no salir no puede notarse como una falta.

    La página no advierte sobre impostores. Se evaluó un «si ves a alguien que no
    es ninguna de ellas, avisa» y se descartó: alarma más de lo que sirve.
--}}
<x-layout.app title="Vigilancia"
              descripcion="Quién hace la vigilancia de Vista Alta y quién está de guardia en este momento."
              :noindex="true">

    {{-- Lo único que cambia con el reloj, y lo único por lo que alguien vuelve. --}}
    <x-palette-receipt.seccion rotulo="Vigilancia" titulo="Quién cuida Vista Alta">
        <p class="text-grafito/85">
            El acceso está cubierto las 24 horas, los siete días de la semana. Estas son las cuatro personas que hacen
            la vigilancia del fraccionamiento, y quién está de guardia en este momento.
        </p>

        <x-palette-receipt.tarjeta class="mt-6">
            <x-palette-receipt.rotulo>En este momento</x-palette-receipt.rotulo>

            @if ($deGuardia !== null)
                <div class="mt-5 flex flex-col items-center gap-5 text-center sm:flex-row sm:gap-7 sm:text-left">
                    <x-vigilancia.retrato :vigilante="$deGuardia" class="size-28" />

                    <div>
                        <p class="text-3xl font-bold leading-tight tracking-tight text-tinta sm:text-4xl">
                            {{ $deGuardia->nombre }}
                        </p>
                        <p class="cifra mt-1.5 text-[0.8125rem] uppercase tracking-[0.12em] text-grafito/70">
                            {{ $deGuardia->etiqueta }}
                        </p>
                    </div>
                </div>
            @else
                {{--
                    Hoy no puede pasar —los cuatro turnos cubren la semana entera
                    y hay una prueba que lo exige—, pero la configuración se llena
                    a mano. El día que alguien recorte un horario, la página dice
                    que no sabe en vez de inventar a alguien.
                --}}
                <p class="mt-5 text-grafito/85">
                    No tenemos registrado quién está de guardia en este momento. Si necesitas algo, escríbele a la Mesa
                    Directiva.
                </p>
            @endif

            {{--
                La hora de consulta es el reloj del propio lector, así que no
                revela nada del rol; lo que hace es que la página no se lea como
                si estuviera rancia.
            --}}
            <p class="mt-5 border-t border-dashed border-linea pt-4 text-[0.8125rem] text-grafito/65">
                Consultado el {{ $momento->translatedFormat('l j \d\e F \d\e Y, H:i') }} h. Actualiza la página para
                volver a verificarlo.
            </p>
        </x-palette-receipt.tarjeta>
    </x-palette-receipt.seccion>

    <x-palette-receipt.seccion rotulo="Los cuatro vigilantes" titulo="Quiénes están y cuándo les toca" :lectura="false">
        <p class="max-w-(--container-lectura) text-grafito/85">
            Entre las cuatro se cubre la semana completa, sin horas descubiertas.
        </p>

        <ul class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($vigilantes as $vigilante)
                <li class="text-center">
                    <x-vigilancia.retrato :vigilante="$vigilante" class="aspect-square w-full text-2xl" />

                    <p class="mt-3.5 font-semibold text-tinta">{{ $vigilante->nombre }}</p>
                    <p class="cifra mt-1 text-[0.6875rem] uppercase tracking-[0.1em] text-grafito/65">
                        {{ $vigilante->etiqueta }}
                    </p>

                    @if ($vigilante->incorporacion() !== null)
                        {{-- En un solo renglón: partido, el salto de línea se cuela a media frase. --}}
                        <p class="mt-2 text-xs text-grafito/60">Se incorporó el {{ $vigilante->incorporacion()->translatedFormat('j \d\e F \d\e Y') }}.</p>
                    @endif
                </li>
            @endforeach
        </ul>

        <x-palette-receipt.nota variante="exito" class="mt-6 max-w-(--container-lectura)">
            Con el turno de domingo quedó cerrado el último hueco de cobertura. Era uno de los pendientes publicados en
            <a href="{{ route('actividades') }}" class="font-medium underline underline-offset-2">«Lo que sigue»</a>.
        </x-palette-receipt.nota>
    </x-palette-receipt.seccion>

    <x-palette-receipt.seccion rotulo="Comentarios" titulo="Quedamos atentos">
        <div class="flex flex-col gap-4 text-grafito/85">
            <p>
                Lo que necesites reportarle al vigilante en turno se escribe en el grupo de WhatsApp de Colonos, que es
                por donde se les localiza a cualquier hora.
            </p>
            <p>
                Si todavía no formas parte del grupo, solicita tu incorporación a la Mesa Directiva. Aquí no se publica
                la liga de acceso: el grupo es de los colonos y se entra por invitación.
            </p>
            <p>
                Cualquier comentario sobre el servicio de vigilancia —o sobre este esquema— se recibe por la misma vía.
            </p>
        </div>
    </x-palette-receipt.seccion>
</x-layout.app>
