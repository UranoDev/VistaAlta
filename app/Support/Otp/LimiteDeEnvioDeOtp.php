<?php

declare(strict_types=1);

namespace App\Support\Otp;

use App\Exceptions\LimiteDeEnvioDeOtpExcedido;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;

/**
 * Tope de envíos de OTP, contado por teléfono y por IP a la vez.
 *
 * Ninguna de las dos dimensiones sirve sola: contando solo el teléfono, quien
 * abuse rota números y sigue gastando SMS; contando solo la IP, un CGNAT móvil
 * o el internet compartido del fraccionamiento deja a colonos bloqueándose
 * entre sí. Los umbrales de cada una viven en `services.otp.limite` y son
 * distintos a propósito — el porqué está ahí y en docs/adr/0001.
 *
 * Vive aquí y no en un middleware porque lo invoca OtpService, que es el único
 * lugar del que sale un SMS: así ninguna ruta futura puede saltárselo.
 */
class LimiteDeEnvioDeOtp
{
    public function __construct(private readonly RateLimiter $limitador) {}

    /**
     * Aparta un envío. Si ya no queda cupo, lanza sin consumir nada — el
     * intento rechazado no cuenta, porque no gastó ningún mensaje.
     *
     * Con `$ip` nula se toma la de la petición en curso; si tampoco hay
     * (consola, cola), se cuenta solo por teléfono.
     */
    public function consumir(string $telefono, ?string $ip = null): void
    {
        $ip ??= $this->ipDeLaPeticion();

        $espera = $this->disponibleEn($telefono, $ip);

        if ($espera > 0) {
            Log::warning('Se alcanzó el límite de envío de OTP.', [
                'telefono' => $this->huella($telefono),
                'ip' => $ip,
                'segundos_restantes' => $espera,
            ]);

            throw new LimiteDeEnvioDeOtpExcedido($espera);
        }

        $this->limitador->hit($this->llave('telefono', $telefono), $this->ventana('telefono'));

        if ($ip !== null) {
            $this->limitador->hit($this->llave('ip', $ip), $this->ventana('ip'));
        }
    }

    /**
     * Segundos que faltan para poder pedir otro código, o 0 si se puede ahora.
     * Sirve para adelantar el aviso en la interfaz sin provocar el rechazo.
     */
    public function disponibleEn(string $telefono, ?string $ip = null): int
    {
        $ip ??= $this->ipDeLaPeticion();

        $espera = $this->esperaDe('telefono', $telefono);

        if ($ip !== null) {
            $espera = max($espera, $this->esperaDe('ip', $ip));
        }

        return $espera;
    }

    private function esperaDe(string $dimension, string $valor): int
    {
        $llave = $this->llave($dimension, $valor);

        return $this->limitador->tooManyAttempts($llave, $this->intentos($dimension))
            ? $this->limitador->availableIn($llave)
            : 0;
    }

    private function llave(string $dimension, string $valor): string
    {
        return 'otp:'.$dimension.':'.$this->huella($valor);
    }

    /**
     * Ni el caché ni el log guardan el teléfono en claro: para contar y para
     * avisar alcanza con poder distinguir un número de otro.
     */
    private function huella(string $valor): string
    {
        return substr(hash('sha256', $valor), 0, 32);
    }

    private function intentos(string $dimension): int
    {
        return (int) config("services.otp.limite.{$dimension}.intentos");
    }

    private function ventana(string $dimension): int
    {
        return (int) config("services.otp.limite.{$dimension}.ventana_minutos") * 60;
    }

    private function ipDeLaPeticion(): ?string
    {
        return request()->ip();
    }
}
