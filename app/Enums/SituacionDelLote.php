<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cómo está el lote el día que su dueño lo registra. Es lo que el propietario
 * declara, no algo que el sitio compruebe.
 */
enum SituacionDelLote: string
{
    case Terreno = 'terreno';
    case CasaTerminada = 'casa_terminada';
    case EnConstruccion = 'en_construccion';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Terreno => 'Terreno',
            self::CasaTerminada => 'Casa terminada',
            self::EnConstruccion => 'En construcción',
        };
    }
}
