<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * El correo con el que el propietario confirma su registro: el código de seis
 * dígitos y el enlace que hace lo mismo con un toque.
 */
class ConfirmacionDeRegistro extends Mailable
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $codigo,
        public readonly string $enlace,
        public readonly int $horas,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirma tu registro en Vista Alta');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.confirmacion-de-registro');
    }
}
