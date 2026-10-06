{{--
    Las pantallas de confirmación del registro. Una sola vista con cinco
    estados, para que todo el copy esté en un archivo:

    - `pendiente`: se escribe el código que llegó por correo o por WhatsApp.
    - `confirmado`: ya quedó.
    - `enlace`: se llegó desde el correo; falta tocar el botón. El GET nunca
      confirma, porque los antivirus abren los enlaces de los correos.
    - `enlace-vencido` y `enlace-invalido`: el enlace ya no sirve.
--}}
@php
    $canales = $canales ?? [];
    $destinos = $destinos ?? ['correo' => null, 'telefono' => null];
    $correo = config('contenido.correo_contacto');
@endphp

<x-layout.app title="Confirma tu registro"
              descripcion="Confirma tu registro de propietario."
              :noindex="true">

    <x-palette-receipt.seccion rotulo="Registro de propietarios" :titulo="match ($estado) {
        'confirmado' => 'Tu registro quedó confirmado',
        'enlace' => 'Confirma tu registro',
        'enlace-vencido', 'enlace-invalido' => 'Este enlace ya no sirve',
        default => 'Confirma tu registro',
    }">

        @if ($estado === 'confirmado')
            <x-palette-receipt.tarjeta>
                <div class="flex flex-col gap-3">
                    <x-palette-receipt.sello>Confirmado</x-palette-receipt.sello>
                    <p class="text-grafito/85">Listo. Ya tenemos tus lotes y tus datos de contacto.</p>
                    <p class="text-sm text-grafito/70">
                        ¿Tienes lotes a nombre de otra persona de tu familia? Puedes
                        <a href="{{ route('registro') }}" class="font-medium text-tinta underline underline-offset-2">hacer otro registro</a>.
                    </p>
                </div>
            </x-palette-receipt.tarjeta>

        @elseif ($estado === 'enlace')
            <x-palette-receipt.tarjeta>
                <form method="POST" action="{{ $accion }}" class="flex flex-col gap-4" data-una-vez>
                    @csrf
                    <p class="text-grafito/85">Toca el botón para terminar.</p>
                    <x-palette-receipt.boton type="submit" class="w-full min-h-14 text-base">Confirmar mi registro</x-palette-receipt.boton>
                </form>
            </x-palette-receipt.tarjeta>

        @elseif ($estado === 'enlace-vencido')
            <x-palette-receipt.nota variante="aviso">
                El enlace duró {{ \App\Support\Registro\ConfirmacionDelRegistro::VIGENCIA_HORAS }} horas y ya venció.
                Escríbenos a
                <a href="mailto:{{ $correo }}" class="font-medium underline underline-offset-2">{{ $correo }}</a>
                y te mandamos otro.
            </x-palette-receipt.nota>

        @elseif ($estado === 'enlace-invalido')
            <x-palette-receipt.nota variante="aviso">
                Revisa que hayas copiado la dirección completa del correo. Si sigue sin abrir, escríbenos a
                <a href="mailto:{{ $correo }}" class="font-medium underline underline-offset-2">{{ $correo }}</a>.
            </x-palette-receipt.nota>

        @else
            <div class="flex flex-col gap-5">
                @if (session('registro.info'))
                    <x-palette-receipt.nota variante="exito">{{ session('registro.info') }}</x-palette-receipt.nota>
                @endif

                @if ($canales === [])
                    <x-palette-receipt.nota variante="aviso">
                        Recibimos tu registro, pero no pudimos mandarte el código. Toca «Mandar otro código»; si sigue sin
                        llegar, escríbenos a
                        <a href="mailto:{{ $correo }}" class="font-medium underline underline-offset-2">{{ $correo }}</a>.
                    </x-palette-receipt.nota>
                @else
                    <div class="flex flex-col gap-2 text-grafito/85">
                        <p>Recibimos tu registro. Falta confirmarlo.</p>
                        <p>
                            Te mandamos un código de 6 dígitos
                            @if (in_array('correo', $canales, true) && in_array('whatsapp', $canales, true))
                                a tu correo ({{ $destinos['correo'] }}) y a tu WhatsApp ({{ $destinos['telefono'] }}).
                            @elseif (in_array('correo', $canales, true))
                                a tu correo ({{ $destinos['correo'] }}).
                            @else
                                a tu WhatsApp ({{ $destinos['telefono'] }}).
                            @endif
                            @if (in_array('correo', $canales, true))
                                El correo trae además un enlace que confirma con un toque.
                            @endif
                        </p>
                    </div>
                @endif

                <x-palette-receipt.tarjeta :troquel="false">
                    <form method="POST" action="{{ route('registro.codigo') }}" class="flex flex-col gap-4" data-una-vez>
                        @csrf

                        <x-palette-receipt.campo nombre="codigo"
                                                 etiqueta="Código de 6 dígitos"
                                                 inputmode="numeric"
                                                 autocomplete="one-time-code"
                                                 maxlength="6"
                                                 class="max-w-48"
                                                 required
                                                 autofocus />

                        <x-palette-receipt.boton type="submit" class="w-full sm:w-auto min-h-12">Confirmar mi registro</x-palette-receipt.boton>
                    </form>
                </x-palette-receipt.tarjeta>

                <div class="flex flex-col gap-1.5">
                    <form method="POST" action="{{ route('registro.reenviar') }}">
                        @csrf
                        <button type="submit" class="min-h-11 text-sm font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">
                            Mandar otro código
                        </button>
                    </form>
                    @error('reenvio')
                        <p class="text-xs font-medium text-sello">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-grafito/70">El código y el enlace sirven {{ \App\Support\Registro\ConfirmacionDelRegistro::VIGENCIA_HORAS }} horas.</p>
                </div>
            </div>
        @endif
    </x-palette-receipt.seccion>
</x-layout.app>
