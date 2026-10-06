<?php

declare(strict_types=1);

namespace App\Support;

/**
 * La versión del sitio que está corriendo, para mostrarla en el panel.
 *
 * Sale del encabezado más reciente de `CHANGELOG.md` (`## [2026.10.06] - …`),
 * que `scripts/release.ps1` escribe dentro del mismo commit que etiqueta. Se
 * lee de ahí y no de git a propósito: en el servidor no hay que ejecutar nada,
 * y el archivo viaja con el código que describe, así que no puede decir una
 * versión distinta a la que está desplegada.
 *
 * Lo que se ha trabajado después del último corte (`## [Unreleased]`) no es una
 * versión, así que en ese caso no hay nada que mostrar.
 */
final class Version
{
    /** Cuando se omite la ruta, la del changelog del proyecto. */
    public static function actual(?string $changelog = null): ?string
    {
        $changelog ??= base_path('CHANGELOG.md');

        if (! is_file($changelog) || ! is_readable($changelog)) {
            return null;
        }

        $contenido = (string) file_get_contents($changelog);

        if (preg_match('/^## \[([^\]]+)\]/m', $contenido, $m) !== 1) {
            return null;
        }

        return strcasecmp($m[1], 'Unreleased') === 0 ? null : $m[1];
    }
}
