<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * La respuesta a una solicitud de acceso a los datos personales: un ZIP con todo lo
 * que el sitio tiene de la persona (derechos ARCO, sección 5 del Aviso).
 */
class EntregaDeDatos extends Mailable
{
    public function __construct(
        /** Ruta del ZIP. Quien manda el correo se encarga de borrarlo después. */
        public readonly string $zip,
        /** Si el ZIP lleva clave, el correo lo dice (la clave se da por otro medio). */
        public readonly bool $conClave = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tus datos personales en Vista Alta');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.entrega-de-datos');
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->zip)->as('datos-vista-alta.zip')->withMime('application/zip'),
        ];
    }
}
