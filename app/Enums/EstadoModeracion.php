<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Dónde está un Comentario público frente a la Cola de moderación. Los privados
 * no tienen estado: nacen fuera de la cola y nunca se publican.
 */
enum EstadoModeracion: string
{
    case EnCola = 'en_cola';
    case Publicado = 'publicado';
    case Descartado = 'descartado';
}
