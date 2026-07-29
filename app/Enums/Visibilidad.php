<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * La elige el autor al escribir su Comentario y es definitiva: la Mesa Directiva
 * no puede volver público un comentario privado.
 */
enum Visibilidad: string
{
    case Publico = 'publico';
    case Privado = 'privado';
}
