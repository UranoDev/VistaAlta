<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Otp;

use App\Support\Otp\TwilioOtpSender;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwilioOtpSenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.twilio.sid' => 'ACtest',
            'services.twilio.token' => 'token-secreto',
            'services.twilio.from' => '+15005550006',
            'services.twilio.pais_lada' => '+521',
        ]);
    }

    public function test_manda_el_codigo_por_la_messages_api_de_twilio(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SMtest'], 201),
        ]);

        (new TwilioOtpSender)->send('4421234567', '123456');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/ACtest/Messages.json'
                && $request['To'] === '+5214421234567'
                && $request['From'] === '+15005550006'
                && $request['Body'] === 'Vista Alta: tu código de verificación es 123456';
        });
    }

    /**
     * El remitente lo reescribe el carrier —el número de EE.UU. llega como
     * `22622` y el mexicano como `Sms Twilio`—, así que si el cuerpo no nombra a
     * Vista Alta el colono no tiene de dónde saber quién le mandó seis dígitos.
     * Se mira que el nombre vaya **antes** del código: enterarse al final, tras
     * leer un SMS que ya parecía spam, llega tarde.
     */
    public function test_el_cuerpo_identifica_a_vista_alta_antes_del_codigo(): void
    {
        $cuerpo = TwilioOtpSender::cuerpo('123456');

        $this->assertStringStartsWith('Vista Alta', $cuerpo);
        $this->assertLessThan(mb_strpos($cuerpo, '123456'), mb_strpos($cuerpo, 'Vista Alta'));
    }

    /**
     * El presupuesto es de **70** caracteres, no de 160: el cuerpo conserva los
     * acentos de `código` y `verificación`, la `ó` no existe en GSM-7 y eso
     * obliga a Twilio a codificar el mensaje en UCS-2, donde un segmento son 70.
     * Rebasarlo parte el SMS en dos y duplica el costo de cada OTP del sitio.
     * Quien reescriba el mensaje mide contra esto; si le quita los acentos,
     * vuelve a GSM-7 y aquí se sube el límite a 160.
     */
    public function test_el_cuerpo_cabe_en_un_segmento_de_sms(): void
    {
        // El código siempre son seis dígitos (`OtpService::generar`), así que
        // este cuerpo mide lo mismo que cualquiera que se mande de verdad.
        $cuerpo = TwilioOtpSender::cuerpo('123456');

        $this->assertLessThanOrEqual(TwilioOtpSender::LIMITE_DE_UN_SEGMENTO, mb_strlen($cuerpo));
    }

    /**
     * El `1` de móvil no es cosmético: `+52` a diez dígitos lo acepta la API y lo
     * descarta el carrier con el error 30008, así que el código nunca llega
     * (ver docs/adr/0001). Esto se mira contra el default del repo, no contra el
     * `.env`, porque el `.env` no se commitea: una máquina nueva arranca con lo
     * que diga `config/services.php` y ahí es donde el arreglo tiene que vivir.
     */
    public function test_la_lada_que_trae_el_repo_por_defecto_lleva_el_uno_de_movil(): void
    {
        $previo = getenv('TWILIO_PAIS_LADA');
        putenv('TWILIO_PAIS_LADA');
        unset($_ENV['TWILIO_PAIS_LADA'], $_SERVER['TWILIO_PAIS_LADA']);

        try {
            $servicios = require config_path('services.php');

            $this->assertSame('+521', $servicios['twilio']['pais_lada']);
        } finally {
            if ($previo !== false) {
                putenv("TWILIO_PAIS_LADA={$previo}");
                $_ENV['TWILIO_PAIS_LADA'] = $previo;
                $_SERVER['TWILIO_PAIS_LADA'] = $previo;
            }
        }
    }

    public function test_no_altera_los_numeros_que_ya_vienen_en_formato_e164(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SMtest'], 201)]);

        (new TwilioOtpSender)->send('+14155552671', '654321');

        Http::assertSent(fn ($request) => $request['To'] === '+14155552671');
    }

    /**
     * El caso que más se presta al doble prefijo: un celular mexicano que ya viene
     * completo no debe terminar como `+521+521…`.
     */
    public function test_un_celular_mexicano_en_e164_no_recibe_la_lada_dos_veces(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SMtest'], 201)]);

        (new TwilioOtpSender)->send('+5215531269267', '654321');

        Http::assertSent(fn ($request) => $request['To'] === '+5215531269267');
    }

    public function test_revienta_cuando_twilio_responde_con_error(): void
    {
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'Invalid From number'], 400)]);

        $this->expectException(RequestException::class);

        (new TwilioOtpSender)->send('4421234567', '123456');
    }
}
