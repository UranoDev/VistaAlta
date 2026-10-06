<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Las calles de Vista Alta. Son una lista cerrada a propósito: el formulario de
 * registro las ofrece como opciones y no como texto libre, porque «Margarita»,
 * «margarita» y «Mrgarita» son tres calles para cualquier búsqueda o filtro.
 *
 * Una calle nueva se agrega aquí y nada más; el valor guardado es el nombre tal
 * cual, que es también lo que se lee en el panel.
 */
enum Calle: string
{
    case Clavel = 'Clavel';
    case Geranio = 'Geranio';
    case Malva = 'Malva';
    case Margarita = 'Margarita';
    case Nube = 'Nube';
    case Pensamiento = 'Pensamiento';

    /**
     * @return array<string, string> valor => etiqueta, para los filtros del panel.
     */
    public static function opciones(): array
    {
        return array_column(self::cases(), 'value', 'value');
    }
}
