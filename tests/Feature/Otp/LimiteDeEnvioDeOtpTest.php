<?php

declare(strict_types=1);

namespace Tests\Feature\Otp;

use App\Exceptions\LimiteDeEnvioDeOtpExcedido;
use App\Support\Otp\ArrayOtpSender;
use App\Support\Otp\LimiteDeEnvioDeOtp;
use App\Support\Otp\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Todo el archivo corre con OTP_CHANNEL=array (phpunit.xml): el límite se
 * prueba sin mandar un solo SMS de verdad.
 */
class LimiteDeEnvioDeOtpTest extends TestCase
{
    use RefreshDatabase;

    private const PROPOSITO = 'comentario';

    private const TELEFONO = '4421234567';

    private const OTRO_TELEFONO = '4429999999';

    private const IP = '187.190.10.20';

    private const OTRA_IP = '201.144.5.60';

    protected function setUp(): void
    {
        parent::setUp();

        ArrayOtpSender::$enviados = [];
    }

    private function servicio(): OtpService
    {
        return $this->app->make(OtpService::class);
    }

    private function limite(): LimiteDeEnvioDeOtp
    {
        return $this->app->make(LimiteDeEnvioDeOtp::class);
    }

    private function enviar(string $telefono = self::TELEFONO, ?string $ip = self::IP): void
    {
        $this->servicio()->generar($telefono, self::PROPOSITO, $ip);
    }

    private function agotarTelefono(string $telefono = self::TELEFONO, ?string $ip = self::IP): void
    {
        foreach (range(1, 3) as $ignorado) {
            $this->enviar($telefono, $ip);
        }
    }

    private function rechazoAlEnviar(string $telefono = self::TELEFONO, ?string $ip = self::IP): LimiteDeEnvioDeOtpExcedido
    {
        try {
            $this->enviar($telefono, $ip);
        } catch (LimiteDeEnvioDeOtpExcedido $rechazo) {
            return $rechazo;
        }

        $this->fail('Se esperaba que el envío se rechazara por el límite.');
    }

    public function test_el_cuarto_envio_dentro_de_la_ventana_no_manda_sms(): void
    {
        $this->agotarTelefono();

        $vigente = ArrayOtpSender::ultimoCodigoPara(self::TELEFONO);

        $this->rechazoAlEnviar();

        $this->assertSame($vigente, ArrayOtpSender::ultimoCodigoPara(self::TELEFONO));
    }

    public function test_el_mensaje_dice_cuanto_falta_para_reintentar(): void
    {
        $this->agotarTelefono();

        $this->assertSame(
            'Se alcanzó el límite de envíos de código. Vuelve a intentarlo en 10 minutos.',
            $this->rechazoAlEnviar()->getMessage(),
        );

        $this->travel(9)->minutes();

        $this->assertSame(
            'Se alcanzó el límite de envíos de código. Vuelve a intentarlo en 1 minuto.',
            $this->rechazoAlEnviar()->getMessage(),
        );

        $this->travel(30)->seconds();

        $this->assertSame(
            'Se alcanzó el límite de envíos de código. Vuelve a intentarlo en menos de un minuto.',
            $this->rechazoAlEnviar()->getMessage(),
        );
    }

    public function test_al_cerrarse_la_ventana_el_telefono_puede_de_nuevo(): void
    {
        $this->agotarTelefono();
        $vigente = ArrayOtpSender::ultimoCodigoPara(self::TELEFONO);

        $this->travel(10)->minutes();

        $this->enviar();

        $this->assertNotSame($vigente, ArrayOtpSender::ultimoCodigoPara(self::TELEFONO));
        $this->assertSame(0, $this->limite()->disponibleEn(self::TELEFONO, self::IP));
    }

    public function test_un_envio_rechazado_no_invalida_el_codigo_vigente(): void
    {
        $this->agotarTelefono();
        $vigente = ArrayOtpSender::ultimoCodigoPara(self::TELEFONO);

        $this->rechazoAlEnviar();

        $this->assertTrue($this->servicio()->verificar(self::TELEFONO, self::PROPOSITO, $vigente));
    }

    public function test_el_limite_de_un_telefono_no_alcanza_a_otro_de_la_misma_ip(): void
    {
        // La familia detrás del mismo NAT: que uno agote lo suyo no puede dejar
        // sin comentar a los demás.
        $this->agotarTelefono(self::TELEFONO);

        $this->enviar(self::OTRO_TELEFONO);

        $this->assertNotNull(ArrayOtpSender::ultimoCodigoPara(self::OTRO_TELEFONO));
    }

    public function test_el_limite_por_ip_frena_la_rotacion_de_numeros(): void
    {
        config()->set('services.otp.limite.ip.intentos', 5);

        foreach (range(1, 5) as $n) {
            $this->enviar("442100000{$n}");
        }

        $this->rechazoAlEnviar('4421000006');

        $this->assertNull(ArrayOtpSender::ultimoCodigoPara('4421000006'));
    }

    public function test_el_limite_de_una_ip_no_alcanza_a_otra(): void
    {
        config()->set('services.otp.limite.ip.intentos', 2);

        $this->enviar('4421000001');
        $this->enviar('4421000002');
        $this->rechazoAlEnviar('4421000003');

        $this->enviar('4421000003', self::OTRA_IP);

        $this->assertNotNull(ArrayOtpSender::ultimoCodigoPara('4421000003'));
    }

    public function test_sin_ip_se_cuenta_solo_por_telefono(): void
    {
        // Consola o cola: no hay petición de la cual sacar la IP.
        $this->agotarTelefono(self::TELEFONO, null);

        $this->rechazoAlEnviar(self::TELEFONO, null);

        $this->enviar(self::OTRO_TELEFONO, null);

        $this->assertNotNull(ArrayOtpSender::ultimoCodigoPara(self::OTRO_TELEFONO));
    }

    public function test_el_rechazo_no_dice_nada_del_telefono_que_se_escribio(): void
    {
        // Un número que ya validó antes y uno que el sitio nunca vio: el aviso
        // tiene que ser indistinguible entre los dos.
        $this->servicio()->generar(self::TELEFONO, self::PROPOSITO, self::IP);
        $this->servicio()->verificar(
            self::TELEFONO,
            self::PROPOSITO,
            ArrayOtpSender::ultimoCodigoPara(self::TELEFONO),
        );
        $this->enviar(self::TELEFONO);
        $this->enviar(self::TELEFONO);

        $conocido = $this->rechazoAlEnviar(self::TELEFONO);

        $this->agotarTelefono(self::OTRO_TELEFONO);
        $desconocido = $this->rechazoAlEnviar(self::OTRO_TELEFONO);

        $this->assertSame($conocido->getMessage(), $desconocido->getMessage());
        $this->assertStringNotContainsString(self::TELEFONO, $conocido->getMessage());
        $this->assertStringNotContainsString(self::OTRO_TELEFONO, $desconocido->getMessage());
    }

    public function test_disponible_en_adelanta_la_espera_sin_provocar_el_rechazo(): void
    {
        $this->assertSame(0, $this->limite()->disponibleEn(self::TELEFONO, self::IP));

        $this->agotarTelefono();

        $this->assertSame(600, $this->limite()->disponibleEn(self::TELEFONO, self::IP));
        $this->assertSame(0, $this->limite()->disponibleEn(self::OTRO_TELEFONO, self::IP));
    }

    public function test_la_respuesta_html_regresa_el_aviso_al_formulario(): void
    {
        $this->rutaDePrueba();

        foreach (range(1, 3) as $ignorado) {
            $this->post('/prueba/otp', ['telefono' => self::TELEFONO])->assertNoContent();
        }

        $respuesta = $this->post('/prueba/otp', ['telefono' => self::TELEFONO]);

        $respuesta->assertRedirect();
        $respuesta->assertSessionHasErrors([
            'telefono' => 'Se alcanzó el límite de envíos de código. Vuelve a intentarlo en 10 minutos.',
        ]);
    }

    public function test_la_respuesta_json_es_429_con_el_tiempo_de_espera(): void
    {
        $this->rutaDePrueba();

        foreach (range(1, 3) as $ignorado) {
            $this->postJson('/prueba/otp', ['telefono' => self::TELEFONO])->assertNoContent();
        }

        $respuesta = $this->postJson('/prueba/otp', ['telefono' => self::TELEFONO]);

        $respuesta->assertStatus(429);
        $respuesta->assertHeader('Retry-After', '600');
        $respuesta->assertJson([
            'message' => 'Se alcanzó el límite de envíos de código. Vuelve a intentarlo en 10 minutos.',
            'segundos_restantes' => 600,
        ]);
        $this->assertStringNotContainsString(self::TELEFONO, $respuesta->getContent());
    }

    /**
     * La ruta pública del OTP la construye la página de la Propuesta; aquí solo
     * hace falta una que llame al servicio para ver qué recibe el visitante.
     */
    private function rutaDePrueba(): void
    {
        Route::middleware('web')->post('/prueba/otp', function (Request $peticion, OtpService $servicio) {
            $servicio->generar((string) $peticion->input('telefono'), self::PROPOSITO);

            return response()->noContent();
        });
    }
}
