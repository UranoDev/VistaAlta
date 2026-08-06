@props([
    'title' => null,
    // Solo para las páginas que se sirven en más de una dirección —hoy el
    // Reporte financiero vigente, que vive en la raíz y en su URL con fecha—.
    // Sin esto los buscadores indexan las dos y reparten entre ellas lo que
    // vale una.
    'canonical' => null,
    // El renglón que WhatsApp muestra debajo del título en la tarjeta, y que
    // los buscadores usan como resumen. Cada página puede dar el suyo; este es
    // el que aplica cuando no lo hace.
    'descripcion' => 'Rendición de cuentas de la Mesa Directiva de Vista Alta: lo que se hizo, lo que falta y en qué se gastó.',
    // Deja la página fuera de los buscadores. Es para lo que se publica al
    // fraccionamiento sin que tenga por qué alcanzar a quien no vive aquí —hoy
    // Vigilancia, que lleva nombre y cara de cuatro personas—.
    //
    // No es una protección y no debe usarse como si lo fuera: quien tenga la
    // dirección entra igual, y la dirección va a andar circulando en un grupo de
    // vecinos. Lo único que evita es que el contenido escale por buscadores.
    'noindex' => false,
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

        @if ($noindex)
            <meta name="robots" content="noindex, nofollow">
        @endif

        {{--
            Los íconos viven en `public/` y no en `resources/`, así que los sirve
            el servidor directo sin pasar por Vite: no dependen de que alguien
            haya corrido `npm run build` en el despliegue.

            El .ico trae 16, 32 y 48 px en un solo archivo. Es solo el pico y el
            sol del logo, sin la casa ni el texto: a 16 px el lockup completo se
            vuelve una mancha.
        --}}
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{--
            La tarjeta que arman WhatsApp, Facebook y Telegram cuando alguien
            pega la liga. Sin esto pegan la URL pelona, que en un grupo de
            vecinos se lee como spam.

            `og:image` va con URL absoluta a fuerza —los rastreadores no
            resuelven rutas relativas—, de ahí que salga de `asset()` y que
            APP_URL tenga que estar bien en producción.
        --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:locale" content="es_MX">
        <meta property="og:title" content="{{ filled($title) ? $title . ' · ' . config('app.name') : config('app.name') }}">
        <meta property="og:description" content="{{ $descripcion }}">
        <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
        <meta property="og:image" content="{{ asset('og-vista-alta.jpg') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="Logo de Vista Alta Fraccionamiento">
        <meta name="description" content="{{ $descripcion }}">
        <meta name="twitter:card" content="summary_large_image">

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
