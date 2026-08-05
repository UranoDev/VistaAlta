<?php

declare(strict_types=1);

namespace App\Support\Otp;

use Illuminate\Support\Facades\Http;

/**
 * Envío por SMS con la Messages API de Twilio (ver docs/adr/0001).
 */
class TwilioOtpSender implements OtpSender
{
    /**
     * Cuántos caracteres caben en **un** segmento de SMS con este cuerpo.
     *
     * El texto lleva `código` y `verificación`, y la `ó` no está en el alfabeto
     * GSM-7: con una sola de esas vocales Twilio codifica el mensaje entero en
     * UCS-2, donde un segmento son 70 caracteres y no 160. Pasarse parte el SMS
     * en dos y duplica el costo de **cada** OTP que manda el sitio, así que el
     * cuerpo se mide contra este número antes de reescribirlo (lo verifica
     * `TwilioOtpSenderTest`). Si algún día se van los acentos, el mensaje vuelve
     * a GSM-7 y el presupuesto sube a 160.
     */
    public const LIMITE_DE_UN_SEGMENTO = 70;

    public function send(string $destinatario, string $codigo): void
    {
        $sid = config('services.twilio.sid');

        Http::asForm()
            ->withBasicAuth($sid, config('services.twilio.token'))
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => config('services.twilio.from'),
                'To' => $this->normalizarTelefono($destinatario),
                'Body' => self::cuerpo($codigo),
            ])
            ->throw();
    }

    /**
     * Abre nombrando a Vista Alta porque el remitente no lo hace: los carriers
     * mexicanos reescriben el `From` —el número de EE.UU. de la cuenta llega
     * mostrando `22622` y el mexicano llega como `Sms Twilio`— y eso no es
     * configuración nuestra, así que el cuerpo es lo único que puede decirle al
     * colono de dónde salió el código (URVA-59). Un SMS anónimo con seis dígitos,
     * a quien no está esperando nada, se lee igual que el spam de siempre.
     *
     * El nombre va escrito aquí y no sale de `app.name` a propósito: el
     * presupuesto de `LIMITE_DE_UN_SEGMENTO` solo se puede garantizar si el
     * largo del texto es fijo. Con un nombre que llega por configuración,
     * renombrar la app en el `.env` partiría el SMS en dos sin que nadie se
     * entere.
     */
    public static function cuerpo(string $codigo): string
    {
        return "Vista Alta: tu código de verificación es {$codigo}";
    }

    /**
     * Le antepone la lada a los diez dígitos que llegan del formulario. En México
     * esa lada es `+521` —con el `1` de móvil—, no `+52`: sin el `1` el carrier
     * descarta el mensaje con el error 30008 aunque Twilio lo haya aceptado
     * (ver docs/adr/0001). La lada vive en `services.twilio.pais_lada` y es la
     * única fuente del dato.
     *
     * Un número que ya viene en E.164 se respeta tal cual, para no pegarle la
     * lada dos veces.
     */
    private function normalizarTelefono(string $telefono): string
    {
        return str_starts_with($telefono, '+')
            ? $telefono
            : config('services.twilio.pais_lada').$telefono;
    }
}
