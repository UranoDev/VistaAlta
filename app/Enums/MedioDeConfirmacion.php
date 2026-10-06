<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Con qué confirmó el propietario su registro. El código llega por correo y por
 * WhatsApp, así que no distingue por cuál; solo distingue que lo escribió.
 */
enum MedioDeConfirmacion: string
{
    case Enlace = 'enlace';
    case Codigo = 'codigo';
}
