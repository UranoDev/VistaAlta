<?php

declare(strict_types=1);

namespace App\Support\Otp;

use Illuminate\Support\Facades\Log;

/**
 * Entrega por omisión para entornos sin un proveedor real configurado.
 */
class LogOtpSender implements OtpSender
{
    public function send(string $destinatario, string $codigo): void
    {
        Log::info("OTP para {$destinatario}: {$codigo}");
    }
}
