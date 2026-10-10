<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * El correo con el que el propietario verifica su correo: el código de seis
 * dígitos y el enlace que hace lo mismo con un toque. El celular se verifica
 * aparte, con otro código por SMS.
 */
class ConfirmacionDeRegistro extends Mailable
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $codigo,
        public readonly string $enlace,
        public readonly int $horas,
        // Si además dio celular, el correo avisa que ese se verifica aparte.
        public readonly bool $conCelular = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verifica tu correo en Vista Alta');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.confirmacion-de-registro');
    }
}
