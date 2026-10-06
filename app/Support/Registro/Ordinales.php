<?php

declare(strict_types=1);

namespace App\Support\Registro;

/**
 * Los ordinales con los que el formulario titula cada lote: «Primer Lote»,
 * «Segundo Lote», «Tercer Lote». Van apocopados (primer, tercer) porque siempre
 * preceden al sustantivo.
 *
 * La lista llega hasta el máximo de lotes que acepta un registro (20, en
 * `RegistrarPropietarioRequest`). Sale de aquí tanto para pintar los renglones
 * que ya trae el servidor como, por un atributo de la página, para el script que
 * agrega y quita renglones: el título no se escribe en dos lugares.
 */
final class Ordinales
{
    /** @var list<string> */
    public const LISTA = [
        'Primer', 'Segundo', 'Tercer', 'Cuarto', 'Quinto',
        'Sexto', 'Séptimo', 'Octavo', 'Noveno', 'Décimo',
        'Undécimo', 'Duodécimo', 'Decimotercer', 'Decimocuarto', 'Decimoquinto',
        'Decimosexto', 'Decimoséptimo', 'Decimoctavo', 'Decimonoveno', 'Vigésimo',
    ];

    /**
     * El ordinal de la posición `$posicion` (la primera es 1). Más allá de la
     * lista cae al número, que nunca debería hacer falta.
     */
    public static function de(int $posicion): string
    {
        return self::LISTA[$posicion - 1] ?? $posicion.'.º';
    }
}
