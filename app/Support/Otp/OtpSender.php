<?php

declare(strict_types=1);

namespace App\Support\Otp;

interface OtpSender
{
    /**
     * Entrega un código de un solo uso al teléfono indicado.
     */
    public function send(string $destinatario, string $codigo): void;
}
