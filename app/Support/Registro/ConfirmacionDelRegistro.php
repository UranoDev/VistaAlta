<?php

declare(strict_types=1);

namespace App\Support\Registro;

use App\Enums\MedioDeConfirmacion;
use App\Mail\ConfirmacionDeRegistro;
use App\Models\RegistroDePropietario;
use App\Support\Otp\WhatsAppOtpSender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Confirma que quien llenó el registro es dueño del correo y del teléfono que
 * dio: le manda un código de seis dígitos (por correo y por WhatsApp) y un enlace
 * (por correo), y con cualquiera de los dos el registro queda confirmado.
 *
 * ## Por qué no usa `OtpService`
 *
 * `OtpService` guarda códigos por teléfono, con un propósito y un SMS de por
 * medio. Aquí el código y el enlace van pegados a un registro, viajan por dos
 * canales y se invalidan juntos, así que viven en el propio registro. Lo que sí
 * comparte es la forma: hash del código, intentos acotados, vencimiento.
 *
 * ## El enlace nunca confirma con un GET
 *
 * Los antivirus y los previsualizadores de correo abren cada enlace del mensaje.
 * Un GET que confirmara dejaría confirmados registros que nadie confirmó. La
 * página del enlace pide un toque más, y ese toque es un POST.
 *
 * ## Un canal que falla no tira el registro
 *
 * El registro ya está guardado cuando se manda esto. Si WhatsApp no responde, el
 * correo sigue siendo un camino, y al revés; `emitir()` dice por cuáles salió.
 */
class ConfirmacionDelRegistro
{
    public const VIGENCIA_HORAS = 24;

    public const MAX_INTENTOS = 5;

    /** Segundos que tienen que pasar entre un envío y el siguiente. */
    public const ESPERA_ENTRE_ENVIOS = 60;

    public function __construct(private readonly WhatsAppOtpSender $whatsapp) {}

    /**
     * Genera un código y un enlace nuevos (los anteriores dejan de servir) y los
     * manda.
     *
     * @return list<string> Los canales por los que salió: `correo`, `whatsapp`.
     */
    public function emitir(RegistroDePropietario $registro): array
    {
        $codigo = (string) random_int(100000, 999999);
        $token = Str::random(40);

        $registro->forceFill([
            'confirmacion_codigo_hash' => Hash::make($codigo),
            'confirmacion_enlace_hash' => hash('sha256', $token),
            'confirmacion_expira_en' => now()->addHours(self::VIGENCIA_HORAS),
            'confirmacion_intentos' => 0,
            'confirmacion_enviada_en' => now(),
        ])->save();

        $canales = [];

        if (filled($registro->correo)) {
            $salio = $this->intentar(function () use ($registro, $codigo, $token): void {
                Mail::to($registro->correo)->send(new ConfirmacionDeRegistro(
                    $registro->nombre,
                    $codigo,
                    route('registro.enlace', ['registro' => $registro->id, 'token' => $token]),
                    self::VIGENCIA_HORAS,
                ));
            });

            if ($salio && $this->llegaDeVerdad(config('mail.default'))) {
                $canales[] = 'correo';
            }
        }

        if (filled($registro->telefono)) {
            $salio = $this->intentar(fn () => $this->whatsapp->send($registro->telefono, $codigo));

            if ($salio && $this->llegaDeVerdad(config('services.whatsapp.channel'))) {
                $canales[] = 'whatsapp';
            }
        }

        return $canales;
    }

    /**
     * Si un envío por ese medio llega a alguien. En producción un correo o un
     * WhatsApp configurados en `log` o `array` no fallan —solo dejan el código
     * en el log del servidor—, y decirle a la persona «te lo mandamos» sería
     * falso. En desarrollo no se pregunta: ahí `log` es como se prueba, y la
     * pantalla se comporta igual que con un medio real.
     */
    private function llegaDeVerdad(mixed $medio): bool
    {
        return ! app()->isProduction() || ! in_array($medio, ['log', 'array'], true);
    }

    public function confirmarConCodigo(RegistroDePropietario $registro, string $codigo): bool
    {
        if ($registro->estaConfirmado()) {
            return true;
        }

        if ($registro->confirmacion_codigo_hash === null
            || $registro->confirmacionVencida()
            || $registro->confirmacion_intentos >= self::MAX_INTENTOS) {
            return false;
        }

        if (! Hash::check($codigo, $registro->confirmacion_codigo_hash)) {
            $registro->increment('confirmacion_intentos');

            return false;
        }

        $this->marcar($registro, MedioDeConfirmacion::Codigo);

        return true;
    }

    public function confirmarConEnlace(RegistroDePropietario $registro, string $token): bool
    {
        if ($registro->estaConfirmado()) {
            return $this->enlaceCorresponde($registro, $token);
        }

        if (! $this->enlaceCorresponde($registro, $token) || $registro->confirmacionVencida()) {
            return false;
        }

        $this->marcar($registro, MedioDeConfirmacion::Enlace);

        return true;
    }

    /**
     * Si el enlace es el que se le mandó a este registro. No mira vencimiento ni
     * estado: sirve para mostrar la página correcta antes de confirmar.
     */
    public function enlaceCorresponde(RegistroDePropietario $registro, string $token): bool
    {
        return $registro->confirmacion_enlace_hash !== null
            && hash_equals($registro->confirmacion_enlace_hash, hash('sha256', $token));
    }

    private function marcar(RegistroDePropietario $registro, MedioDeConfirmacion $medio): void
    {
        $registro->forceFill([
            'confirmado_en' => now(),
            'confirmado_por' => $medio->value,
        ])->save();
    }

    /**
     * Corre un envío y dice si salió. Un fallo se reporta y no se propaga.
     */
    private function intentar(callable $envio): bool
    {
        try {
            $envio();

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
