<?php

declare(strict_types=1);

namespace App\Support\Otp;

use Illuminate\Support\Facades\Http;

/**
 * Envío con la API en la nube de WhatsApp (Meta), usando la plantilla de
 * autenticación con botón «Copiar código».
 *
 * Lo que tiene que existir en Meta antes de que esto funcione —el número
 * registrado, el token de un usuario del sistema y la plantilla aprobada— está
 * en `config/services.php` (`whatsapp`) y en la guía de configuración. La
 * plantilla lleva el código dos veces: en el cuerpo y en el botón.
 */
class CloudApiWhatsAppOtpSender implements WhatsAppOtpSender
{
    public function send(string $telefono, string $codigo): void
    {
        $version = config('services.whatsapp.version');
        $numeroId = config('services.whatsapp.phone_number_id');

        Http::withToken((string) config('services.whatsapp.token'))
            ->post("https://graph.facebook.com/{$version}/{$numeroId}/messages", self::cuerpo(
                config('services.whatsapp.pais_lada').$telefono,
                $codigo,
                (string) config('services.whatsapp.plantilla'),
                (string) config('services.whatsapp.idioma'),
            ))
            ->throw();
    }

    /**
     * @return array<string, mixed>
     */
    public static function cuerpo(string $destino, string $codigo, string $plantilla, string $idioma): array
    {
        return [
            'messaging_product' => 'whatsapp',
            'to' => $destino,
            'type' => 'template',
            'template' => [
                'name' => $plantilla,
                'language' => ['code' => $idioma],
                'components' => [
                    ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $codigo]]],
                    ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => [['type' => 'text', 'text' => $codigo]]],
                ],
            ],
        ];
    }
}
