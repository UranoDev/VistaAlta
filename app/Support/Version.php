<?php

declare(strict_types=1);

namespace App\Support;

/**
 * La versión del sitio que está corriendo, para mostrarla en el panel.
 *
 * Sale del archivo `VERSION` de la raíz, que `scripts/release.ps1` reescribe en
 * cada corte dentro del mismo commit que etiqueta. Se lee de ahí y no de git a
 * propósito: en el servidor no hay que ejecutar nada, y el archivo viaja con el
 * código que describe, así que no puede decir una versión distinta a la
 * desplegada.
 *
 * Tampoco sale del `CHANGELOG.md`, que parecía la fuente obvia: ese solo trae
 * encabezado de una versión que tuvo issues cerrados, y un corte sin ellos
 * (2026.10.06) no deja rastro. Con el changelog el panel habría dicho la versión
 * anterior.
 *
 * Una versión es lo que `release.ps1` produce —CalVer, `2026.10.06` o
 * `2026.10.06.1`—. Cualquier otra cosa en el archivo no se muestra.
 */
final class Version
{
    /** Cuando se omite la ruta, el `VERSION` del proyecto. */
    public static function actual(?string $archivo = null): ?string
    {
        $archivo ??= base_path('VERSION');

        if (! is_file($archivo) || ! is_readable($archivo)) {
            return null;
        }

        $version = trim((string) file_get_contents($archivo));

        return preg_match('/^\d{4}\.\d{2}\.\d{2}(\.\d+)?$/', $version) === 1 ? $version : null;
    }
}
