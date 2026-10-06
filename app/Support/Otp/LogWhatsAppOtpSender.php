<?php

declare(strict_types=1);

namespace App\Support\Otp;

use Illuminate\Support\Facades\Log;

/**
 * Entrega por omisión para entornos sin WhatsApp configurado: deja el código en
 * el log, como `LogOtpSender` con el SMS.
 */
class LogWhatsAppOtpSender implements WhatsAppOtpSender
{
    public function send(string $telefono, string $codigo): void
    {
        Log::info("Código por WhatsApp para {$telefono}: {$codigo}");
    }
}
