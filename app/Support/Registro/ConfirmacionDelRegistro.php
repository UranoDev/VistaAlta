<?php

declare(strict_types=1);

namespace App\Support\Registro;

use App\Enums\MedioDeConfirmacion;
use App\Exceptions\LimiteDeEnvioDeOtpExcedido;
use App\Mail\ConfirmacionDeRegistro;
use App\Models\RegistroDePropietario;
use App\Support\Otp\LimiteDeEnvioDeOtp;
use App\Support\Otp\OtpSender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Verifica por separado que quien llenó el registro controla el correo y el
 * celular que dio. Con uno de los dos basta para registrarse; el que se da, se
 * verifica.
 *
 * - **Correo:** un código de seis dígitos y un enlace en el mismo mensaje. Con
 *   cualquiera de los dos queda verificado.
 * - **Celular:** un código de seis dígitos por SMS (Twilio).
 *
 * Son **dos verificaciones separadas**, cada una con su código, su vigencia, sus
 * intentos y su marca en la base. Un código del correo no sirve en el celular ni
 * al revés, y verificar uno no verifica al otro.
 *
 * ## Por qué no usa `OtpService`
 *
 * `OtpService` guarda códigos por teléfono, con un propósito y sin más contexto.
 * Aquí cada código va pegado a un registro y a un medio, y se invalida junto con
 * él, así que viven en el propio registro. Lo que sí comparte es el tope de envíos
 * (`LimiteDeEnvioDeOtp`): el SMS cuesta dinero y la ruta es pública.
 *
 * ## El enlace nunca verifica con un GET
 *
 * Los antivirus y los previsualizadores de correo abren cada enlace del mensaje.
 * Un GET que verificara dejaría verificados correos que nadie tocó. La página del
 * enlace pide un toque más, y ese toque es un POST.
 *
 * ## Un medio que falla no tira el registro
 *
 * El registro ya está guardado cuando se manda esto. Si el SMS no sale, el correo
 * sigue sirviendo, y al revés; cada envío dice por separado si salió.
 */
class ConfirmacionDelRegistro
{
    /** Cuánto dura el código (y el enlace) del correo. */
    public const VIGENCIA_CORREO_HORAS = 24;

    /** Cuánto dura el código del SMS. Se escribe al momento, así que es corto. */
    public const VIGENCIA_SMS_MINUTOS = 15;

    public const MAX_INTENTOS = 5;

    /** Segundos que tienen que pasar entre un envío y el siguiente, por medio. */
    public const ESPERA_ENTRE_ENVIOS = 60;

    public function __construct(
        private readonly OtpSender $sms,
        private readonly LimiteDeEnvioDeOtp $limite,
    ) {}

    /**
     * Manda un código por cada medio que la persona dio. Cada uno dice si salió; el
     * medio que no dio no aparece.
     *
     * @return array{correo?: bool, telefono?: bool}
     */
    public function emitir(RegistroDePropietario $registro): array
    {
        $salieron = [];

        if ($registro->tieneCorreo()) {
            $salieron['correo'] = $this->emitirCorreo($registro);
        }

        if ($registro->tieneTelefono()) {
            try {
                $salieron['telefono'] = $this->emitirTelefono($registro);
            } catch (LimiteDeEnvioDeOtpExcedido) {
                $salieron['telefono'] = false;
            }
        }

        return $salieron;
    }

    /**
     * Código y enlace nuevos para el correo (los anteriores dejan de servir).
     */
    public function emitirCorreo(RegistroDePropietario $registro): bool
    {
        $codigo = $this->codigo();
        $token = Str::random(40);

        $registro->forceFill([
            'correo_codigo_hash' => Hash::make($codigo),
            'correo_enlace_hash' => hash('sha256', $token),
            'correo_expira_en' => now()->addHours(self::VIGENCIA_CORREO_HORAS),
            'correo_intentos' => 0,
            'correo_enviado_en' => now(),
        ])->save();

        $salio = $this->intentar(function () use ($registro, $codigo, $token): void {
            Mail::to($registro->correo)->send(new ConfirmacionDeRegistro(
                $registro->nombre,
                $codigo,
                route('registro.enlace', ['registro' => $registro->id, 'token' => $token]),
                self::VIGENCIA_CORREO_HORAS,
                conCelular: $registro->tieneTelefono(),
            ));
        });

        return $salio && $this->llegaDeVerdad(config('mail.default'));
    }

    /**
     * Código nuevo para el celular (el anterior deja de servir).
     *
     * @throws LimiteDeEnvioDeOtpExcedido Si se agotó el tope de SMS: no se manda
     *                                    nada y el código vigente sigue siendo el anterior.
     */
    public function emitirTelefono(RegistroDePropietario $registro): bool
    {
        // El tope se cobra antes de tocar nada, como en OtpService: un envío
        // rechazado no manda SMS y tampoco invalida el código que ya había.
        $this->limite->consumir($registro->telefono);

        $codigo = $this->codigo();

        $registro->forceFill([
            'telefono_codigo_hash' => Hash::make($codigo),
            'telefono_expira_en' => now()->addMinutes(self::VIGENCIA_SMS_MINUTOS),
            'telefono_intentos' => 0,
            'telefono_enviado_en' => now(),
        ])->save();

        $salio = $this->intentar(fn () => $this->sms->send($registro->telefono, $codigo));

        return $salio && $this->llegaDeVerdad(config('services.otp.channel'));
    }

    public function verificarCorreoConCodigo(RegistroDePropietario $registro, string $codigo): bool
    {
        if ($registro->correoVerificado()) {
            return true;
        }

        if (! $this->codigoCorrecto($registro, 'correo', $codigo)) {
            return false;
        }

        $this->marcarCorreo($registro, MedioDeConfirmacion::Codigo);

        return true;
    }

    public function verificarCorreoConEnlace(RegistroDePropietario $registro, string $token): bool
    {
        if (! $this->enlaceCorresponde($registro, $token)) {
            return false;
        }

        if ($registro->correoVerificado()) {
            return true;
        }

        if ($registro->correoVencido()) {
            return false;
        }

        $this->marcarCorreo($registro, MedioDeConfirmacion::Enlace);

        return true;
    }

    public function verificarTelefonoConCodigo(RegistroDePropietario $registro, string $codigo): bool
    {
        if ($registro->telefonoVerificado()) {
            return true;
        }

        if (! $this->codigoCorrecto($registro, 'telefono', $codigo)) {
            return false;
        }

        $registro->forceFill(['telefono_verificado_en' => now()])->save();

        return true;
    }

    /**
     * Si el enlace es el que se le mandó a este registro. No mira vencimiento ni
     * estado: sirve para mostrar la página correcta antes de verificar.
     */
    public function enlaceCorresponde(RegistroDePropietario $registro, string $token): bool
    {
        return $registro->correo_enlace_hash !== null
            && hash_equals($registro->correo_enlace_hash, hash('sha256', $token));
    }

    /**
     * Segundos que faltan para poder pedir otro código por ese medio, o 0.
     *
     * @param  'correo'|'telefono'  $medio
     */
    public function esperaParaReenviar(RegistroDePropietario $registro, string $medio): int
    {
        $enviado = $registro->{$medio.'_enviado_en'};

        if ($enviado === null) {
            return 0;
        }

        return max(0, self::ESPERA_ENTRE_ENVIOS - (int) $enviado->diffInSeconds(now(), true));
    }

    /**
     * La comprobación común a los dos medios: que haya código, que no haya
     * vencido, que no se hayan agotado los intentos y que coincida. Un intento
     * fallido se cuenta.
     *
     * @param  'correo'|'telefono'  $medio
     */
    private function codigoCorrecto(RegistroDePropietario $registro, string $medio, string $codigo): bool
    {
        $hash = $registro->{$medio.'_codigo_hash'};
        $vencido = $medio === 'correo' ? $registro->correoVencido() : $registro->telefonoVencido();

        if ($hash === null || $vencido || $registro->{$medio.'_intentos'} >= self::MAX_INTENTOS) {
            return false;
        }

        if (! Hash::check($codigo, $hash)) {
            $registro->increment($medio.'_intentos');

            return false;
        }

        return true;
    }

    private function marcarCorreo(RegistroDePropietario $registro, MedioDeConfirmacion $medio): void
    {
        $registro->forceFill([
            'correo_verificado_en' => now(),
            'correo_verificado_por' => $medio->value,
        ])->save();
    }

    private function codigo(): string
    {
        return (string) random_int(100000, 999999);
    }

    /**
     * Si un envío por ese medio llega a alguien. En producción un correo o un SMS
     * configurados en `log` o `array` no fallan —solo dejan el código en el log
     * del servidor—, y decirle a la persona «te lo mandamos» sería falso. En
     * desarrollo no se pregunta: ahí `log` es como se prueba, y la pantalla se
     * comporta igual que con un medio real.
     */
    private function llegaDeVerdad(mixed $medio): bool
    {
        return ! app()->isProduction() || ! in_array($medio, ['log', 'array'], true);
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
