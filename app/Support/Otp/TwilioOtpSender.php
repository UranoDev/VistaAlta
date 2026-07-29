<?php

declare(strict_types=1);

namespace App\Support\Otp;

use Illuminate\Support\Facades\Http;

/**
 * Envío por SMS con la Messages API de Twilio (ver docs/adr/0001).
 */
class TwilioOtpSender implements OtpSender
{
    public function send(string $destinatario, string $codigo): void
    {
        $sid = config('services.twilio.sid');

        Http::asForm()
            ->withBasicAuth($sid, config('services.twilio.token'))
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => config('services.twilio.from'),
                'To' => $this->normalizarTelefono($destinatario),
                'Body' => "Tu código de verificación es: {$codigo}",
            ])
            ->throw();
    }

    private function normalizarTelefono(string $telefono): string
    {
        return str_starts_with($telefono, '+')
            ? $telefono
            : config('services.twilio.pais_lada').$telefono;
    }
}
