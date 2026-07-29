<footer class="mt-16 border-t border-linea bg-papel-alto">
    <div class="mx-auto w-full max-w-5xl px-5 py-8 sm:px-8">
        <p class="cifra text-[0.6875rem] uppercase tracking-[0.14em] text-grafito/60">
            Fraccionamiento Vista Alta
        </p>
        <p class="mt-2 max-w-prose text-sm text-grafito/75">
            Transparencia y Rendición de cuentas de la Mesa Directiva ante los Colonos.
        </p>

        {{--
            Las páginas legales viven aquí y no en el menú de arriba: ese menú
            es para lo que se le pide a la Asamblea que lea, y ya va apretado en
            móvil. Dos enlaces chicos, no una segunda navegación.
        --}}
        <p class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-xs text-grafito/70">
            <a href="{{ route('privacidad') }}" class="underline underline-offset-2 hover:text-tinta">Aviso de Privacidad</a>
            <a href="{{ route('terminos') }}" class="underline underline-offset-2 hover:text-tinta">Términos de Servicio</a>
        </p>

        {{--
            Crédito de quien construyó el sitio. Va debajo de los enlaces
            legales y más tenue que ellos —`grafito/55` contra `/70`— porque en
            un documento de rendición de cuentas nada de fuera de la Mesa
            Directiva debe competir con lo que la Asamblea vino a leer.

            El enlace abre en pestaña nueva para que el vecino no pierda la
            página; `noopener` porque es un destino externo.
        --}}
        <p class="mt-5 max-w-prose text-xs leading-relaxed text-grafito/55">
            La tecnología para rendir cuentas en línea la provee
            <a href="https://urano.dev"
               target="_blank"
               rel="noopener"
               class="font-medium text-grafito/75 underline underline-offset-2 transition-colors hover:text-tinta">Urano.dev</a>.
        </p>
    </div>
</footer>
