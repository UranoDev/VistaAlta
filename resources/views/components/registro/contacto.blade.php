@props([
    // El índice del renglón dentro de `contactos[]`; `__I__` en la plantilla.
    'i',
    'valores' => [],
    // El lugar que ocupa en la lista; el script lo recalcula.
    'posicion' => 1,
])

@php
    $error = fn (string $campo): ?string => $errors->first("contactos.$i.$campo") ?: null;
@endphp

<div data-item class="space-y-4 border border-linea bg-papel px-4 py-5">
    <div class="flex items-center justify-between gap-3">
        <p class="text-base font-bold">Contacto adicional <span data-numero>{{ $posicion }}</span></p>

        <button type="button" data-quitar class="min-h-11 px-2 text-sm font-semibold text-sello underline underline-offset-2">
            Quitar
        </button>
    </div>

    <x-palette-receipt.campo nombre="contactos[{{ $i }}][nombre]"
                             etiqueta="Nombre completo *"
                             :error="$error('nombre')"
                             :value="$valores['nombre'] ?? ''"
                             autocomplete="off"
                             maxlength="255"
                             required />

    <p class="text-xs text-grafito/70">Indica teléfono o correo. Con uno basta.</p>

    <x-palette-receipt.campo nombre="contactos[{{ $i }}][telefono]"
                             etiqueta="Teléfono o WhatsApp"
                             tipo="tel"
                             inputmode="tel"
                             :error="$error('telefono')"
                             :value="$valores['telefono'] ?? ''"
                             placeholder="10 dígitos" />

    <x-palette-receipt.campo nombre="contactos[{{ $i }}][correo]"
                             etiqueta="Correo electrónico"
                             tipo="email"
                             inputmode="email"
                             :error="$error('correo')"
                             :value="$valores['correo'] ?? ''"
                             placeholder="nombre@correo.com" />
</div>
