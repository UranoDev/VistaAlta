@props([
    // El índice del renglón dentro de `lotes[]`. La plantilla que usa el botón
    // «Agregar otro lote» lo trae como `__I__` y el script lo reemplaza.
    'i',
    // Lo que ya llenó el propietario, cuando la validación lo regresó aquí.
    'valores' => [],
    // Con un solo lote no hay a quién quitar: el registro necesita al menos uno.
    'quitable' => true,
    // El lugar que ocupa en la lista (el primero es 1), para el título. El
    // script lo recalcula al agregar o quitar renglones.
    'posicion' => 1,
])

@php
    $calles = \App\Enums\Calle::cases();
    $situaciones = \App\Enums\SituacionDelLote::cases();
    $error = fn (string $campo): ?string => $errors->first("lotes.$i.$campo") ?: null;
    $opcion = 'flex min-h-12 min-w-0 cursor-pointer items-center gap-2.5 border border-linea bg-papel-alto px-2.5 py-2 text-base '
        .'hover:border-tinta has-[:checked]:border-tinta has-[:checked]:bg-menta/40';
@endphp

<div data-item class="space-y-5 border border-linea bg-papel px-4 py-5">
    <div class="flex items-center justify-between gap-3">
        <p class="text-base font-bold"><span data-ordinal>{{ \App\Support\Registro\Ordinales::de($posicion) }}</span> Lote</p>

        <button type="button"
                data-quitar
                @class(['min-h-11 px-2 text-sm font-semibold text-sello underline underline-offset-2', 'hidden' => ! $quitable])>
            Quitar
        </button>
    </div>

    <fieldset class="space-y-2">
        <legend class="text-sm font-semibold">Calle *</legend>

        <div class="grid grid-cols-2 gap-2">
            @foreach ($calles as $calle)
                <label class="{{ $opcion }}">
                    <input type="radio"
                           name="lotes[{{ $i }}][calle]"
                           value="{{ $calle->value }}"
                           required
                           @checked(($valores['calle'] ?? null) === $calle->value)
                           class="size-5 shrink-0 accent-[var(--color-tinta)]">
                    {{ $calle->value }}
                </label>
            @endforeach
        </div>

        @if ($mensaje = $error('calle'))
            <p class="text-xs font-medium text-sello">{{ $mensaje }}</p>
        @endif
    </fieldset>

    <x-palette-receipt.nota variante="neutra">
        Escribe el número oficial, o bien la manzana y el lote. Con una de las dos formas basta.
    </x-palette-receipt.nota>

    <div class="grid grid-cols-3 gap-3">
        <x-palette-receipt.campo nombre="lotes[{{ $i }}][numero_oficial]"
                                 etiqueta="Núm. oficial"
                                 :error="$error('numero_oficial')"
                                 :value="$valores['numero_oficial'] ?? ''"
                                 placeholder="Ej. 128"
                                 inputmode="text"
                                 maxlength="20" />

        <x-palette-receipt.campo nombre="lotes[{{ $i }}][manzana]"
                                 etiqueta="Manzana"
                                 :error="$error('manzana')"
                                 :value="$valores['manzana'] ?? ''"
                                 placeholder="Ej. 4"
                                 maxlength="20" />

        <x-palette-receipt.campo nombre="lotes[{{ $i }}][lote]"
                                 etiqueta="Lote"
                                 :error="$error('lote')"
                                 :value="$valores['lote'] ?? ''"
                                 placeholder="Ej. 12"
                                 maxlength="20" />
    </div>

    <fieldset class="space-y-2">
        <legend class="text-sm font-semibold">Situación del lote *</legend>

        <div class="grid gap-2">
            @foreach ($situaciones as $situacion)
                <label class="{{ $opcion }}">
                    <input type="radio"
                           name="lotes[{{ $i }}][situacion]"
                           value="{{ $situacion->value }}"
                           required
                           @checked(($valores['situacion'] ?? null) === $situacion->value)
                           class="size-5 shrink-0 accent-[var(--color-tinta)]">
                    {{ $situacion->etiqueta() }}
                </label>
            @endforeach
        </div>

        @if ($mensaje = $error('situacion'))
            <p class="text-xs font-medium text-sello">{{ $mensaje }}</p>
        @endif
    </fieldset>
</div>
