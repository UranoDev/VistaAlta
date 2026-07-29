<?php

declare(strict_types=1);

namespace App\Support\Otp;

/**
 * Sender en memoria para las pruebas automatizadas; espeja el driver "array"
 * de correo de Laravel para poder afirmar sobre el código que se envió.
 */
class ArrayOtpSender implements OtpSender
{
    /** @var array<string, string> */
    public static array $enviados = [];

    public function send(string $destinatario, string $codigo): void
    {
        static::$enviados[$destinatario] = $codigo;
    }

    public static function ultimoCodigoPara(string $destinatario): ?string
    {
        return static::$enviados[$destinatario] ?? null;
    }
}
