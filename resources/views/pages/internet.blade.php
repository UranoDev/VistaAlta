{{--
    La lista de espera para la instalación de internet.

    Todo el texto que lee el vecino está en este archivo: para trabajar el copy
    no hay que tocar nada más.

    Decisiones que no se deshacen sin volver a tomarlas:

    0. **El domicilio se da por número oficial, o por manzana y lote.** Con una
       forma basta; lo que no se da se guarda vacío.
   1. **La lista es por propiedad, no por persona.** Quien tiene dos propiedades
       llena el formulario dos veces con el mismo celular y recibe dos folios.
       Por eso, al terminar, el celular queda recordado en la sesión y la
       siguiente vuelta ya lo trae escrito.
    2. **La calle se elige, no se escribe.** Es la lista cerrada de
       `App\Enums\Calle`; de texto libre, «Margarita» y «margarita» serían dos
       calles para el panel.
    3. **Una propiedad entra una sola vez.** Si ya estaba, no se crea otro folio:
       se le dice cuál tiene.
    4. **Fuera de los buscadores y del menú.** Se comparte como enlace.

    Para celular primero: campos de 16 px o más (por debajo, iOS hace zoom al
    enfocarlos) y objetivos táctiles de 44 px o más.
--}}
@php
    $listo = session('internet.listo');
    $opcion = 'flex min-h-12 min-w-0 cursor-pointer items-center gap-2.5 border border-linea bg-papel-alto px-2.5 py-2 text-base '
        .'hover:border-tinta has-[:checked]:border-tinta has-[:checked]:bg-menta/40';
@endphp

<x-layout.app title="Internet"
              descripcion="Anota tu propiedad en la lista de espera para la instalación de internet en el fraccionamiento."
              :noindex="true">

    <x-palette-receipt.seccion rotulo="Internet" titulo="Lista de espera para la instalación">
        <div class="flex flex-col gap-3 text-grafito/85">
            <p>
                La empresa Alinet va a instalar Internet en el fraccionamiento. Anota aquí tu propiedad y te damos un folio.
            </p>
            <p class="text-sm text-grafito/70">
                Si tienes más de una propiedad, anótalas una por una con el mismo celular.
                Los campos con * son obligatorios.
            </p>
        </div>

        @if ($listo)
            <x-palette-receipt.tarjeta class="mt-7">
                <div class="flex flex-col gap-4">
                    {{-- `self-center`: en una columna flex el sello se estira al ancho de la tarjeta; así toma solo el de su texto. --}}
                    <x-palette-receipt.sello class="self-center">Anotada</x-palette-receipt.sello>

                    <div>
                        <x-palette-receipt.rotulo>Tu folio</x-palette-receipt.rotulo>
                        <p class="cifra mt-1 text-[clamp(2rem,10vw,2.75rem)] font-bold leading-none tracking-tight text-tinta">
                            {{ $listo['folio'] }}
                        </p>
                    </div>

                    <p class="text-grafito/85">{{ $listo['domicilio'] }}</p>

                    <p class="text-sm text-grafito/70">Guarda tu folio: con él identificamos tu lugar en la lista.</p>

                    <x-palette-receipt.boton :href="route('internet')" variante="contorno" class="w-full min-h-12">
                        Anotar otra propiedad
                    </x-palette-receipt.boton>
                </div>
            </x-palette-receipt.tarjeta>
        @else
            <form method="POST" action="{{ route('internet.store') }}" class="mt-7 flex flex-col gap-5" data-una-vez>
                @csrf

                {{-- El campo trampa: fuera de pantalla, fuera del orden de tabulación y sin autocompletar. --}}
                <div class="sr-only" aria-hidden="true">
                    <label for="sitio_web">No llenes este campo</label>
                    <input type="text" id="sitio_web" name="sitio_web" tabindex="-1" autocomplete="off" value="">
                </div>

                <x-palette-receipt.tarjeta :troquel="false">
                    <div class="flex flex-col gap-5">
                        <h3 class="text-lg font-bold tracking-tight">Tu propiedad</h3>

                        @error('domicilio')
                            <x-palette-receipt.nota variante="aviso">{{ $message }}</x-palette-receipt.nota>
                        @enderror

                        <fieldset class="space-y-2">
                            <legend class="text-sm font-semibold">Calle *</legend>

                            <div class="grid grid-cols-2 gap-2">
                                @foreach (\App\Enums\Calle::cases() as $calle)
                                    <label class="{{ $opcion }}">
                                        <input type="radio"
                                               name="calle"
                                               value="{{ $calle->value }}"
                                               required
                                               @checked(old('calle') === $calle->value)
                                               class="size-5 shrink-0 accent-[var(--color-tinta)]">
                                        {{ $calle->value }}
                                    </label>
                                @endforeach
                            </div>

                            @error('calle')
                                <p class="text-xs font-medium text-sello">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <x-palette-receipt.nota variante="neutra">
                            Escribe el número oficial, o bien la manzana y el lote. Con una de las dos formas basta.
                        </x-palette-receipt.nota>

                        <div class="grid grid-cols-3 gap-3">
                            <x-palette-receipt.campo nombre="numero_oficial"
                                                     etiqueta="Núm. oficial"
                                                     :value="old('numero_oficial')"
                                                     placeholder="Ej. 128"
                                                     autocomplete="off"
                                                     maxlength="20" />

                            <x-palette-receipt.campo nombre="manzana"
                                                     etiqueta="Manzana"
                                                     :value="old('manzana')"
                                                     placeholder="Ej. 4"
                                                     autocomplete="off"
                                                     maxlength="20" />

                            <x-palette-receipt.campo nombre="lote"
                                                     etiqueta="Lote"
                                                     :value="old('lote')"
                                                     placeholder="Ej. 12"
                                                     autocomplete="off"
                                                     maxlength="20" />
                        </div>

                        <x-palette-receipt.campo nombre="celular"
                                                 etiqueta="Celular *"
                                                 ayuda="A 10 dígitos. Es para seguimiento de la instalación."
                                                 tipo="tel"
                                                 inputmode="tel"
                                                 autocomplete="tel-national"
                                                 :value="old('celular', $celular)"
                                                 placeholder="10 dígitos"
                                                 required />
                    </div>
                </x-palette-receipt.tarjeta>

                <x-palette-receipt.boton type="submit" class="w-full min-h-14 text-base">Anotarme en la lista</x-palette-receipt.boton>

                {{--
                    El Aviso tiene que estar donde se recaban los datos. Sin casilla
                    que marcar: es el consentimiento tácito de la sección 9 del
                    Aviso, como en los comentarios. La lista no se entrega a nadie;
                    quien se anota se pone en contacto con Alinet por su cuenta.
                --}}
                <p class="text-xs text-grafito/70">
                    Al anotarte aceptas el
                    <a href="{{ route('privacidad') }}" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">Aviso de Privacidad</a>.
                </p>
            </form>
        @endif
    </x-palette-receipt.seccion>
</x-layout.app>
