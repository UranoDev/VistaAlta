@props([
    // El Teléfono validado de esta petición, si su Ventana de validación sigue abierta.
    'telefonoValidado' => null,
    // El teléfono al que se le acaba de mandar un código y todavía no lo confirma.
    'telefonoPendiente' => null,
])

<div {{ $attributes->class(['space-y-5']) }}>
    @if (session('comentario.exito'))
        <x-palette-receipt.nota variante="exito">{{ session('comentario.exito') }}</x-palette-receipt.nota>
    @endif

    @if (session('comentario.info'))
        <x-palette-receipt.nota variante="neutra">{{ session('comentario.info') }}</x-palette-receipt.nota>
    @endif

    @if ($telefonoValidado)
        {{-- Ventana de validación abierta: se puede escribir. --}}
        <form method="POST" action="{{ route('comentarios.store') }}" class="space-y-5">
            @csrf

            <x-palette-receipt.nota variante="neutra">
                Teléfono validado: <span class="cifra font-semibold">{{ $telefonoValidado }}</span>.
                Puedes comentar durante {{ \App\Support\VentanaDeValidacion::MINUTOS }} minutos sin volver a validarte.
            </x-palette-receipt.nota>

            <x-palette-receipt.campo nombre="nombre"
                            etiqueta="Tu nombre"
                            ayuda="Tal cual lo escribas es como aparece, si eliges que tu comentario sea público."
                            :value="old('nombre')"
                            maxlength="255"
                            required />

            <div class="space-y-1.5">
                <label for="comentario" class="block text-sm font-semibold">Tu pregunta o comentario</label>
                <textarea id="comentario"
                          name="comentario"
                          rows="5"
                          maxlength="2000"
                          required
                          @if ($errors->has('comentario')) aria-invalid="true" aria-describedby="comentario-error" @endif
                          @class([
                              'block w-full border bg-papel-alto px-3 py-2 text-base',
                              'placeholder:text-grafito/40',
                              $errors->has('comentario') ? 'border-sello' : 'border-linea',
                          ])>{{ old('comentario') }}</textarea>
                @error('comentario')
                    <p id="comentario-error" class="text-xs font-medium text-sello">{{ $message }}</p>
                @enderror
            </div>

            {{--
                La elección es del autor y es definitiva: la Mesa Directiva no
                puede volver público un comentario privado. Por eso no hay opción
                preseleccionada — se elige a propósito, no por omisión.
            --}}
            <fieldset class="space-y-3 border border-linea bg-papel px-4 py-4">
                <legend class="px-1 text-sm font-semibold">¿Quién puede leer tu comentario?</legend>

                <p class="text-xs text-grafito/70">
                    Elige con calma: <strong class="font-semibold">esta decisión no se puede deshacer</strong>. Una
                    vez enviado, nadie la cambia — ni tú, ni la Mesa Directiva.
                </p>

                @php
                    $opciones = [
                        [
                            'valor' => 'publico',
                            'titulo' => 'Público',
                            'detalle' => 'Público quiere decir público: queda en esta página, con tu nombre y a la
                                          vista de todos los Colonos y de cualquiera que abra el sitio. Aparece
                                          después de que la Mesa Directiva lo publique, y ya no se baja.',
                        ],
                        [
                            'valor' => 'privado',
                            'titulo' => 'Privado',
                            'detalle' => 'Lo lee únicamente la Mesa Directiva. No se publica aquí ni se lee en voz
                                          alta en la Asamblea, y no hay forma de que después lo vuelvan público.',
                        ],
                    ];
                @endphp

                @foreach ($opciones as $opcion)
                    <label for="visibilidad-{{ $opcion['valor'] }}"
                           class="flex cursor-pointer gap-3 border border-linea bg-papel-alto px-3 py-3 hover:border-tinta">
                        <input type="radio"
                               id="visibilidad-{{ $opcion['valor'] }}"
                               name="visibilidad"
                               value="{{ $opcion['valor'] }}"
                               required
                               @checked(old('visibilidad') === $opcion['valor'])
                               @if ($errors->has('visibilidad')) aria-describedby="visibilidad-error" @endif
                               class="mt-1 size-4 shrink-0 accent-[var(--color-tinta)]">
                        <span class="text-sm">
                            <span class="block font-semibold">{{ $opcion['titulo'] }}</span>
                            <span class="mt-0.5 block text-grafito/75">{{ $opcion['detalle'] }}</span>
                        </span>
                    </label>
                @endforeach

                @error('visibilidad')
                    <p id="visibilidad-error" class="text-xs font-medium text-sello">{{ $message }}</p>
                @enderror
            </fieldset>

            @error('telefono')
                <x-palette-receipt.nota variante="aviso">{{ $message }}</x-palette-receipt.nota>
            @enderror

            <x-palette-receipt.boton type="submit">Enviar comentario</x-palette-receipt.boton>
        </form>
    @elseif ($telefonoPendiente)
        {{-- Código mandado, falta confirmarlo. --}}
        <form method="POST" action="{{ route('comentarios.validar') }}" class="space-y-5">
            @csrf

            <p class="text-sm text-grafito/80">
                Enviamos un código por SMS a <span class="cifra font-semibold">{{ $telefonoPendiente }}</span>.
                Escríbelo aquí para poder comentar.
            </p>

            <x-palette-receipt.campo nombre="codigo"
                            etiqueta="Código de 6 dígitos"
                            tipo="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            class="max-w-40"
                            required
                            autofocus />

            <div class="flex flex-wrap items-center gap-3">
                <x-palette-receipt.boton type="submit">Validar mi teléfono</x-palette-receipt.boton>
                <span class="text-xs text-grafito/70">El código vence a los 5 minutos.</span>
            </div>
        </form>

        {{--
            Las dos salidas secundarias. Van como enlaces, no como botones: la
            acción esperada sigue siendo "Validar mi teléfono".
        --}}
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
            <form method="POST" action="{{ route('comentarios.codigo') }}">
                @csrf
                <input type="hidden" name="telefono" value="{{ $telefonoPendiente }}">
                <button type="submit" class="text-sm font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">
                    Volver a enviarme el código
                </button>
            </form>

            {{-- Para quien se equivocó de número: reenviar lo mandaría al mismo lado. --}}
            <form method="POST" action="{{ route('comentarios.cambiar-telefono') }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">
                    Usar otro número
                </button>
            </form>
        </div>
    @else
        {{-- Nada validado todavía: se empieza por el teléfono. --}}
        <form method="POST" action="{{ route('comentarios.codigo') }}" class="space-y-5">
            @csrf

            <p class="text-sm text-grafito/80">
                Para comentar validamos tu celular con un código por SMS. Es solo para saber que del otro lado hay
                una persona a la que se le puede responder: tu número no se publica en ninguna parte del sitio.
            </p>

            <x-palette-receipt.campo nombre="telefono"
                            etiqueta="Tu celular"
                            ayuda="A 10 dígitos, como lo marcas normalmente."
                            tipo="tel"
                            inputmode="tel"
                            autocomplete="tel"
                            :value="old('telefono')"
                            class="max-w-64"
                            required />

            <x-palette-receipt.boton type="submit">Enviarme el código</x-palette-receipt.boton>

            {{--
                El Aviso tiene que estar donde se recaban los datos, y ese lugar
                es éste: las otras dos ramas ya no piden nada. Sin casilla que
                marcar — es el consentimiento tácito de la sección 9 del Aviso, y
                el formulario ya trae tres pasos.
            --}}
            <p class="text-xs text-grafito/70">
                Al validar tu teléfono aceptas el
                <a href="{{ route('privacidad') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Aviso de Privacidad</a>
                y los
                <a href="{{ route('terminos') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Términos de Servicio</a>.
            </p>
        </form>
    @endif
</div>
