@props([
    'rotulo' => null,
    'titulo' => null,
    // Ancho de lectura por omisión; `false` deja el ancho completo del contenedor.
    'lectura' => true,
])

<section {{ $attributes->class(['mx-auto w-full max-w-5xl px-5 py-10 sm:px-8 sm:py-14']) }}>
    <div @class(['max-w-(--container-lectura)' => $lectura])>
        @if ($rotulo)
            <x-palette-receipt.rotulo>{{ $rotulo }}</x-palette-receipt.rotulo>
        @endif

        @if ($titulo)
            <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">{{ $titulo }}</h2>
        @endif

        <div @class(['mt-6' => $rotulo || $titulo])>
            {{ $slot }}
        </div>
    </div>
</section>
