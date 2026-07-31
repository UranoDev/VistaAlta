<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Por dónde se reciben los Comentarios sobre la Propuesta. Una a la vez.
 *
 * No confundirla con la Recepción de comentarios: ésa decide *si* se admiten
 * comentarios nuevos, y ésta *por dónde llegan* cuando sí se admiten. Con la
 * recepción cerrada, la vía es irrelevante.
 *
 * `Otp` es la buena y la que el sitio quiere: el Colono escribe en la página y
 * elige él mismo si su comentario es público o privado, y esa elección queda
 * fija en el registro. `WhatsApp` es la que aguanta mientras las operadoras
 * filtren el SMS de Twilio (URVA-26): el comentario llega a un chat y lo captura
 * la Mesa Directiva, así que la visibilidad depende de que el autor la pida por
 * escrito en su mensaje.
 */
enum Via: string
{
    case Otp = 'otp';
    case WhatsApp = 'whatsapp';
}
