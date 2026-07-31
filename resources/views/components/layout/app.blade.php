@props([
    'title' => null,
    // Solo para las páginas que se sirven en más de una dirección —hoy el
    // Reporte financiero vigente, que vive en la raíz y en su URL con fecha—.
    // Sin esto los buscadores indexan las dos y reparten entre ellas lo que
    // vale una.
    'canonical' => null,
])

<!DOCTYPE html>
<html lang="es-MX" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#1E4D3B">

        <title>{{ filled($title) ? $title . ' · ' . config('app.name') : config('app.name') }}</title>

        @if ($canonical)
            <link rel="canonical" href="{{ $canonical }}">
        @endif

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen flex flex-col antialiased">
        <a href="#contenido"
           class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded focus:bg-tinta focus:px-4 focus:py-2 focus:text-papel">
            Saltar al contenido
        </a>

        <x-layout.encabezado />

        <main id="contenido" class="flex-1 w-full">
            {{ $slot }}
        </main>

        <x-layout.pie />
    </body>
</html>
