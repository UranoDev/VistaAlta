<?php

declare(strict_types=1);

namespace App\Support;

/**
 * El celular tal como se guarda en el sitio: diez dígitos, sin lada de país.
 *
 * Lo escriben de cualquier manera —«55 1234-5678», «+52 1 55 1234 5678»— y es
 * el mismo número, así que se normaliza antes de validar y de guardar. Sirve para
 * que el mismo celular escrito de dos formas sea uno solo al buscarlo en el panel.
 */
final class Telefono
{
    /**
     * Deja solo dígitos y, si traía la lada de México (52 o 521) delante de diez
     * dígitos, se la quita.
     */
    public static function aDiezDigitos(string $telefono): string
    {
        $digitos = preg_replace('/\D/', '', $telefono) ?? '';

        if (strlen($digitos) > 10 && preg_match('/^521?(\d{10})$/', $digitos, $m) === 1) {
            return $m[1];
        }

        return $digitos;
    }
}
