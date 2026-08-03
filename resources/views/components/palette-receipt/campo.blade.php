@props([
    'nombre',
    'etiqueta',
    'tipo' => 'text',
    'ayuda' => null,
    // Cuando no se pasa, toma el error de validación de `$nombre`.
    'error' => null,
])

@php
    $error ??= $errors->first($nombre);
    $idAyuda = $ayuda ? $nombre . '-ayuda' : null;
    $idError = $error ? $nombre . '-error' : null;
    $descrito = trim(($idAyuda ?? '') . ' ' . ($idError ?? ''));
@endphp

<div {{ $attributes->only('class')->class(['space-y-1.5']) }}>
    <label for="{{ $nombre }}" class="block text-sm font-semibold">{{ $etiqueta }}</label>

    @if ($ayuda)
        <p id="{{ $idAyuda }}" class="text-xs text-grafito/70">{{ $ayuda }}</p>
    @endif

    <input type="{{ $tipo }}"
           id="{{ $nombre }}"
           name="{{ $nombre }}"
           @if ($descrito) aria-describedby="{{ $descrito }}" @endif
           @if ($error) aria-invalid="true" @endif
           {{ $attributes->except('class')->class([
               'block w-full border bg-papel-alto px-3 py-2 text-base',
               'placeholder:text-grafito/40',
               $error ? 'border-sello' : 'border-linea',
           ]) }}>

    @if ($error)
        <p id="{{ $idError }}" class="text-xs font-medium text-sello">{{ $error }}</p>
    @endif
</div>
