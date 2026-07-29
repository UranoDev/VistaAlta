<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Se pidió un código habiendo agotado el límite de envíos de OTP.
 *
 * El mensaje es uno solo, siempre el mismo, y solo dice cuánto falta para
 * reintentar: no distingue si el tope que se alcanzó fue el del teléfono o el
 * de la IP, ni dice nada sobre el número que se escribió. Quien lo reciba no
 * puede deducir de él si ese teléfono está en un padrón, si ya comentó, ni si
 * existe en ningún lado.
 */
class LimiteDeEnvioDeOtpExcedido extends RuntimeException
{
    public function __construct(public readonly int $segundosRestantes)
    {
        parent::__construct(self::mensaje($segundosRestantes));
    }

    /**
     * Respuesta por omisión. La página de la Propuesta puede atrapar la
     * excepción si quiere presentar el aviso de otra forma; si no lo hace,
     * el visitante igual recibe el mensaje con el tiempo de espera.
     */
    public function render(Request $peticion): Response
    {
        if ($peticion->expectsJson()) {
            return new JsonResponse(
                [
                    'message' => $this->getMessage(),
                    'segundos_restantes' => $this->segundosRestantes,
                ],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => (string) $this->segundosRestantes],
            );
        }

        return back()
            ->withInput($peticion->except('codigo'))
            ->withErrors(['telefono' => $this->getMessage()]);
    }

    /**
     * El aviso al log ya lo escribe LimiteDeEnvioDeOtp, que sí tiene el
     * contexto (IP y huella del teléfono). Reportarla de nuevo solo duplicaría
     * la línea y llenaría el log en un abuso sostenido.
     */
    public function report(): bool
    {
        return false;
    }

    private static function mensaje(int $segundos): string
    {
        if ($segundos < 60) {
            return 'Se alcanzó el límite de envíos de código. Vuelve a intentarlo en menos de un minuto.';
        }

        $minutos = (int) ceil($segundos / 60);

        return sprintf(
            'Se alcanzó el límite de envíos de código. Vuelve a intentarlo en %d %s.',
            $minutos,
            $minutos === 1 ? 'minuto' : 'minutos',
        );
    }
}
