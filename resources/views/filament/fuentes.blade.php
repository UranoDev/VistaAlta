{{--
    Las IBM Plex auto-hospedadas, las mismas del sitio público. El panel no pasa
    por `layout/app.blade.php`, así que el directivo se monta en su `<head>` con
    un render hook (ver AdminPanelProvider).

    Es esto y no `->font(url: ...)` porque el archivo que emite `@fonts` lleva
    hash de compilación —no hay URL fija que pasarle— y porque de aquí salen las
    `--font-plex-*` que el tema necesita para conservar los respaldos por métrica.
--}}
@fonts
