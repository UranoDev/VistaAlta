<?php

declare(strict_types=1);

namespace App\Support\Otp;

/**
 * Sender en memoria para las pruebas, igual que `ArrayOtpSender`.
 */
class ArrayWhatsAppOtpSender implements WhatsAppOtpSender
{
    /** @var array<string, string> */
    public static array $enviados = [];

    /** Para probar qué pasa cuando WhatsApp falla. */
    public static bool $fallar = false;

    public function send(string $telefono, string $codigo): void
    {
        if (static::$fallar) {
            throw new \RuntimeException('WhatsApp no respondió.');
        }

        static::$enviados[$telefono] = $codigo;
    }

    public static function ultimoCodigoPara(string $telefono): ?string
    {
        return static::$enviados[$telefono] ?? null;
    }

    public static function reiniciar(): void
    {
        static::$enviados = [];
        static::$fallar = false;
    }
}
