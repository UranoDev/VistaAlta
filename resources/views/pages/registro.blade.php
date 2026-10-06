{{--
    El formulario de registro de propietarios.

    TODO el texto que lee el propietario está en este archivo y en los dos
    componentes de renglón (`components/registro/lote` y `contacto`): para
    trabajar el copy no hay que tocar nada más.

    Decisiones que no se deshacen sin volver a tomarlas:

    1. **Los lotes se eligen, no se escriben.** La calle es una lista cerrada
       (`App\Enums\Calle`), igual que la situación del lote: de texto libre,
       el filtro del panel tendría tres «Margarita».
    2. **Con teléfono o correo basta.** Pedir los dos deja fuera a quien solo
       tiene uno, y a esa persona sí hay que poder localizarla.
    3. **Sin OTP.** No publica nada: lo que se captura solo lo lee quien entra
       al panel. Lo cuida un tope por IP en la ruta y un campo trampa.
    4. **Fuera de los buscadores y del menú.** Se comparte como enlace; no es
       una página para quien navega el sitio.

    Para celular primero: campos de 16 px o más (por debajo, iOS hace zoom al
    enfocarlos), objetivos táctiles de 44 px, y los renglones de lote en una
    sola columna que en escritorio se queda en el ancho de lectura.
--}}
<x-layout.app title="Registro de propietarios"
              descripcion="Registra tus lotes y tus datos de contacto para el sistema automatizado de pagos del fraccionamiento."
              :noindex="true">

    <x-palette-receipt.seccion rotulo="Registro de propietarios" titulo="Registra tus lotes y tus datos">
        <div class="flex flex-col gap-3 text-grafito/85">
            <p>
                Este registro nos permitirá registrar los pagos en el sistema automatizado.
            </p>
            <p class="text-sm text-grafito/70">Toma unos 3 minutos. Los campos con * son obligatorios.</p>
        </div>

            <form method="POST" action="{{ route('registro.store') }}" class="mt-7 flex flex-col gap-5" data-una-vez>
                @csrf

                @if ($errors->any())
                    <x-palette-receipt.nota variante="aviso">
                        Revisa los campos marcados en rojo y vuelve a enviar.
                    </x-palette-receipt.nota>
                @endif

                {{-- El campo trampa: fuera de pantalla, fuera del orden de tabulación y sin autocompletar. --}}
                <div class="sr-only" aria-hidden="true">
                    <label for="sitio_web">No llenes este campo</label>
                    <input type="text" id="sitio_web" name="sitio_web" tabindex="-1" autocomplete="off" value="">
                </div>

                {{-- 1 · Los lotes --}}
                <x-palette-receipt.tarjeta :troquel="false">
                    <div class="flex flex-col gap-4">
                        <div>
                            <x-palette-receipt.rotulo>Paso 1</x-palette-receipt.rotulo>
                            <h3 class="mt-1 text-lg font-bold tracking-tight">Tus lotes</h3>
                            <p class="mt-1 text-sm text-grafito/75">
                                Registra cada lote que sea de tu propiedad. Si tienes más de uno, usa el botón de abajo.
                            </p>
                        </div>

                        @error('lotes')
                            <p class="text-xs font-medium text-sello">{{ $message }}</p>
                        @enderror

                        <div data-repetible
                             data-ordinales="{{ json_encode(\App\Support\Registro\Ordinales::LISTA, JSON_UNESCAPED_UNICODE) }}"
                             data-minimo="1"
                             data-siguiente="{{ collect(array_keys($lotes))->map(fn ($k) => (int) $k)->max() + 1 }}"
                             class="flex flex-col gap-4">
                            <div data-items class="flex flex-col gap-4">
                                @foreach ($lotes as $i => $valores)
                                    <x-registro.lote :i="$i" :valores="$valores" :quitable="count($lotes) > 1" :posicion="$loop->iteration" />
                                @endforeach
                            </div>

                            <template data-plantilla>
                                <x-registro.lote i="__I__" />
                            </template>

                            <x-palette-receipt.boton variante="contorno" data-agregar class="w-full min-h-12">
                                + Agregar otro lote
                            </x-palette-receipt.boton>
                        </div>
                    </div>
                </x-palette-receipt.tarjeta>

                {{-- 2 · El propietario --}}
                <x-palette-receipt.tarjeta :troquel="false">
                    <div class="flex flex-col gap-4">
                        <div>
                            <x-palette-receipt.rotulo>Paso 2</x-palette-receipt.rotulo>
                            <h3 class="mt-1 text-lg font-bold tracking-tight">Datos del propietario</h3>
                        </div>

                        <x-palette-receipt.campo nombre="nombre"
                                                 etiqueta="Nombre completo *"
                                                 :value="old('nombre')"
                                                 placeholder="Nombre y apellidos"
                                                 autocomplete="name"
                                                 maxlength="255"
                                                 required />

                        <x-palette-receipt.nota variante="neutra">
                            Indica al menos un medio de contacto: teléfono o correo. Con uno basta.
                        </x-palette-receipt.nota>

                        <x-palette-receipt.campo nombre="telefono"
                                                 etiqueta="Teléfono o WhatsApp"
                                                 tipo="tel"
                                                 inputmode="tel"
                                                 autocomplete="tel-national"
                                                 :value="old('telefono')"
                                                 placeholder="10 dígitos" />

                        <x-palette-receipt.campo nombre="correo"
                                                 etiqueta="Correo electrónico"
                                                 tipo="email"
                                                 inputmode="email"
                                                 autocomplete="email"
                                                 :value="old('correo')"
                                                 placeholder="nombre@correo.com" />

                        <div data-repetible
                             data-minimo="0"
                             data-siguiente="{{ $contactos ? collect(array_keys($contactos))->map(fn ($k) => (int) $k)->max() + 1 : 0 }}"
                             class="flex flex-col gap-4">
                            <div data-items class="flex flex-col gap-4">
                                @foreach ($contactos as $i => $valores)
                                    <x-registro.contacto :i="$i" :valores="$valores" :posicion="$loop->iteration" />
                                @endforeach
                            </div>

                            <template data-plantilla>
                                <x-registro.contacto i="__I__" />
                            </template>

                            <x-palette-receipt.boton variante="contorno" data-agregar class="w-full min-h-12">
                                + Agregar contacto
                            </x-palette-receipt.boton>
                        </div>
                    </div>
                </x-palette-receipt.tarjeta>

                {{-- 3 · Emergencia --}}
                <x-palette-receipt.tarjeta :troquel="false">
                    <div class="flex flex-col gap-4">
                        <div>
                            <x-palette-receipt.rotulo>Paso 3 · Opcional</x-palette-receipt.rotulo>
                            <h3 class="mt-1 text-lg font-bold tracking-tight">Contacto de emergencia</h3>
                        </div>

                        <x-palette-receipt.campo nombre="emergencia_nombre"
                                                 etiqueta="Nombre"
                                                 :value="old('emergencia_nombre')"
                                                 autocomplete="off"
                                                 maxlength="255" />

                        <div class="grid gap-4 sm:grid-cols-[2fr_1fr]">
                            <x-palette-receipt.campo nombre="emergencia_telefono"
                                                     etiqueta="Teléfono"
                                                     tipo="tel"
                                                     inputmode="tel"
                                                     :value="old('emergencia_telefono')"
                                                     placeholder="10 dígitos" />

                            <x-palette-receipt.campo nombre="residentes"
                                                     etiqueta="Residentes"
                                                     tipo="number"
                                                     inputmode="numeric"
                                                     min="0"
                                                     max="99"
                                                     :value="old('residentes')"
                                                     placeholder="Ej. 4" />
                        </div>
                    </div>
                </x-palette-receipt.tarjeta>

                {{-- 4 · Confirmación --}}
                <x-palette-receipt.tarjeta :troquel="false">
                    <div class="flex flex-col gap-4">
                        <div>
                            <x-palette-receipt.rotulo>Paso 4</x-palette-receipt.rotulo>
                            <h3 class="mt-1 text-lg font-bold tracking-tight">Confirmación</h3>
                        </div>

                        @php
                            $casilla = 'flex items-start gap-3 text-[0.9375rem] leading-snug';
                        @endphp

                        <div class="flex flex-col gap-1.5">
                            <label class="{{ $casilla }}">
                                <input type="checkbox"
                                       name="acepto_aviso"
                                       value="1"
                                       @checked(old('acepto_aviso'))
                                       class="mt-0.5 size-5 shrink-0 accent-[var(--color-tinta)]">
                                <span>
                                    He leído el
                                    <a href="{{ route('privacidad') }}" target="_blank" rel="noopener" class="font-medium text-tinta underline underline-offset-2">aviso de privacidad</a>
                                    y autorizo el uso de mis datos para integrar el padrón de propietarios. *
                                </span>
                            </label>
                            @error('acepto_aviso')
                                <p class="text-xs font-medium text-sello">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </x-palette-receipt.tarjeta>

                <x-palette-receipt.boton type="submit" class="w-full min-h-14 text-base">Enviar registro</x-palette-receipt.boton>

                <p class="text-center text-xs text-grafito/70">
                    Tus datos se usan para registrar tus pagos en el sistema automatizado. Si tienes dudas, escríbenos a
                    <a href="mailto:{{ config('contenido.correo_contacto') }}" class="font-medium text-tinta underline underline-offset-2">{{ config('contenido.correo_contacto') }}</a>.
                </p>
            </form>
    </x-palette-receipt.seccion>
</x-layout.app>
