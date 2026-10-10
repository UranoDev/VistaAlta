{{--
    Las pantallas de verificación del registro. Una sola vista con cuatro
    estados, para que todo el copy esté en un archivo:

    - `pendiente`: las dos verificaciones, cada una con su código, su estado y su
      reenvío. **Son dos verificaciones separadas**: el correo recibe un código y
      un enlace, el celular un código por SMS, y una no verifica a la otra.
    - `enlace`: se llegó desde el correo; falta tocar el botón. El GET nunca
      verifica, porque los antivirus abren los enlaces de los correos.
    - `enlace-vencido` y `enlace-invalido`: el enlace ya no sirve.
--}}
@use('App\Support\Registro\ConfirmacionDelRegistro', 'Verificacion')
@php
    $contacto = config('contenido.correo_contacto');
    $verificado = $verificado ?? ['correo' => false, 'telefono' => false];
    $enviados = $enviados ?? ['correo' => true, 'telefono' => true];
    $destinos = $destinos ?? ['correo' => null, 'telefono' => null];
    $medios = $medios ?? ['correo', 'telefono'];
    $completo = collect($medios)->every(fn ($medio) => $verificado[$medio]);
    $titulos = ['correo' => 'Correo', 'telefono' => 'Celular'];
    $encabezado = count($medios) === 1
        ? ($medios[0] === 'correo' ? 'Verifica tu correo' : 'Verifica tu celular')
        : 'Verifica tu correo y tu celular';

    $chips = [
        'si' => 'bg-menta text-tinta',
        'no' => 'border border-dashed border-linea text-grafito/55',
    ];

    $entrada = 'block w-full max-w-48 border bg-papel-alto px-3 py-2.5 text-base placeholder:text-grafito/40';
@endphp

<x-layout.app title="Verifica tu registro"
              descripcion="Verifica tu correo y tu celular para terminar tu registro de propietario."
              :noindex="true">

    <x-palette-receipt.seccion rotulo="Registro de propietarios" :titulo="match ($estado) {
        'enlace' => 'Verifica tu correo',
        'enlace-vencido', 'enlace-invalido' => 'Este enlace ya no sirve',
        default => $completo ? 'Tu registro quedó verificado' : $encabezado,
    }">

        @if ($estado === 'enlace')
            <x-palette-receipt.tarjeta>
                <form method="POST" action="{{ $accion }}" class="flex flex-col gap-4" data-una-vez>
                    @csrf
                    <p class="text-grafito/85">Toca el botón para verificar tu correo.</p>
                    <x-palette-receipt.boton type="submit" class="w-full min-h-14 text-base">Verificar mi correo</x-palette-receipt.boton>
                </form>
            </x-palette-receipt.tarjeta>

        @elseif ($estado === 'enlace-vencido')
            <x-palette-receipt.nota variante="aviso">
                El enlace duró {{ Verificacion::VIGENCIA_CORREO_HORAS }} horas y ya venció.
                Escríbenos a
                <a href="mailto:{{ $contacto }}" class="font-medium underline underline-offset-2">{{ $contacto }}</a>
                y te mandamos otro.
            </x-palette-receipt.nota>

        @elseif ($estado === 'enlace-invalido')
            <x-palette-receipt.nota variante="aviso">
                Revisa que hayas copiado la dirección completa del correo. Si sigue sin abrir, escríbenos a
                <a href="mailto:{{ $contacto }}" class="font-medium underline underline-offset-2">{{ $contacto }}</a>.
            </x-palette-receipt.nota>

        @else
            <div class="flex flex-col gap-5">
                @if (session('registro.info'))
                    <x-palette-receipt.nota variante="exito">{{ session('registro.info') }}</x-palette-receipt.nota>
                @endif

                @if ($completo)
                    <x-palette-receipt.tarjeta>
                        <div class="flex flex-col items-center gap-3 text-center">
                            <x-palette-receipt.sello>Verificado</x-palette-receipt.sello>
                            <p class="text-grafito/85">
                                {{ count($medios) === 2 ? 'Tu correo y tu celular quedaron verificados.' : ($medios[0] === 'correo' ? 'Tu correo quedó verificado.' : 'Tu celular quedó verificado.') }}
                            </p>
                            <p class="text-sm text-grafito/70">El siguiente paso es la revisión de la Administración.</p>
                        </div>
                    </x-palette-receipt.tarjeta>
                @else
                    <div class="flex flex-col gap-2 text-grafito/85">
                        @if (count($medios) === 2)
                            <p>Recibimos tu registro. Falta verificar tu correo y tu celular.</p>
                            <p class="text-sm text-grafito/70">
                                Son dos verificaciones separadas: cada una tiene su propio código, y el del correo no
                                verifica el celular ni al revés.
                            </p>
                        @else
                            <p>Recibimos tu registro. Falta verificar tu {{ $medios[0] === 'correo' ? 'correo' : 'celular' }}.</p>
                        @endif
                    </div>
                @endif

                @foreach ($medios as $medio)
                    @php $titulo = $titulos[$medio]; @endphp
                    <x-palette-receipt.tarjeta :troquel="false">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg font-bold tracking-tight">{{ $titulo }}</h3>
                                <span class="cifra flex-none px-2 py-0.5 text-[0.6875rem] font-semibold uppercase tracking-[0.08em] {{ $chips[$verificado[$medio] ? 'si' : 'no'] }}">
                                    {{ $verificado[$medio] ? 'Verificado' : 'Pendiente' }}
                                </span>
                            </div>

                            @if ($verificado[$medio])
                                <p class="text-sm text-grafito/75">
                                    {{ $medio === 'correo' ? 'Tu correo quedó verificado.' : 'Tu celular quedó verificado.' }}
                                </p>
                            @else
                                @if (! ($enviados[$medio] ?? true))
                                    <x-palette-receipt.nota variante="aviso">
                                        No pudimos mandarte el código {{ $medio === 'correo' ? 'por correo' : 'por SMS' }}.
                                        Toca «Mandar otro código»; si sigue sin llegar, escríbenos a
                                        <a href="mailto:{{ $contacto }}" class="font-medium underline underline-offset-2">{{ $contacto }}</a>.
                                    </x-palette-receipt.nota>
                                @elseif ($medio === 'correo')
                                    <p class="text-sm text-grafito/75">
                                        Te mandamos un código de 6 dígitos a {{ $destinos['correo'] }}. El correo trae además un
                                        enlace que lo verifica con un toque. Sirven {{ Verificacion::VIGENCIA_CORREO_HORAS }} horas.
                                    </p>
                                @else
                                    <p class="text-sm text-grafito/75">
                                        Te mandamos un SMS con un código de 6 dígitos al celular con {{ $destinos['telefono'] }}.
                                        Sirve {{ Verificacion::VIGENCIA_SMS_MINUTOS }} minutos.
                                    </p>
                                @endif

                                <form method="POST" action="{{ route('registro.codigo', $medio) }}" class="flex flex-col gap-3" data-una-vez>
                                    @csrf

                                    @php $error = $errors->first("codigo_{$medio}"); @endphp
                                    <div class="space-y-1.5">
                                        <label for="codigo-{{ $medio }}" class="block text-sm font-semibold">
                                            Código {{ $medio === 'correo' ? 'del correo' : 'del SMS' }}
                                        </label>
                                        <input type="text"
                                               id="codigo-{{ $medio }}"
                                               name="codigo"
                                               inputmode="numeric"
                                               autocomplete="one-time-code"
                                               maxlength="6"
                                               placeholder="6 dígitos"
                                               required
                                               @if ($error) aria-invalid="true" aria-describedby="codigo-{{ $medio }}-error" @endif
                                               class="{{ $entrada }} {{ $error ? 'border-sello' : 'border-linea' }}">
                                        @if ($error)
                                            <p id="codigo-{{ $medio }}-error" class="text-xs font-medium text-sello">{{ $error }}</p>
                                        @endif
                                    </div>

                                    <x-palette-receipt.boton type="submit" class="w-full sm:w-auto min-h-12">
                                        Verificar {{ $medio === 'correo' ? 'mi correo' : 'mi celular' }}
                                    </x-palette-receipt.boton>
                                </form>

                                <form method="POST" action="{{ route('registro.reenviar', $medio) }}">
                                    @csrf
                                    <button type="submit" class="min-h-11 text-sm font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">
                                        Mandar otro código
                                    </button>
                                    @error("reenvio_{$medio}")
                                        <p class="text-xs font-medium text-sello">{{ $message }}</p>
                                    @enderror
                                </form>
                            @endif
                        </div>
                    </x-palette-receipt.tarjeta>
                @endforeach
            </div>
        @endif
    </x-palette-receipt.seccion>
</x-layout.app>
