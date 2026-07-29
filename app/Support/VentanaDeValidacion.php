<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Ventana de validación: los 30 minutos que siguen a un OTP exitoso, durante
 * los cuales ese teléfono puede comentar sin volver a validarse.
 *
 * Vive en una cookie porque aquí no hay sesión de usuario: un Teléfono validado
 * no es una cuenta. Al expirar no se pierde nada de lo ya publicado — solo se
 * vuelve a pedir un código para escribir.
 *
 * La cookie va dentro del grupo `web`, así que Laravel la cifra y la firma con
 * la APP_KEY. Por eso el teléfono que sale de aquí es el que este servidor
 * escribió al verificar el código, y no algo que el visitante pueda inventar.
 */
class VentanaDeValidacion
{
    /**
     * En nvavista la ventana dura 15 minutos; aquí son 30 (URVA-6).
     */
    public const MINUTOS = 30;

    private const COOKIE = 'telefono_validado';

    /**
     * Abre la ventana para un teléfono que acaba de completar su OTP.
     */
    public function abrir(string $telefono): Cookie
    {
        return cookie(self::COOKIE, $telefono, self::MINUTOS);
    }

    /**
     * El Teléfono validado de esta petición, o null si no hay ventana abierta.
     */
    public function telefonoValidado(Request $peticion): ?string
    {
        $telefono = $peticion->cookie(self::COOKIE);

        return is_string($telefono) && $telefono !== '' ? $telefono : null;
    }
}
