<?php

declare(strict_types=1);

namespace Tests\Feature\Registro;

use App\Enums\MedioDeConfirmacion;
use App\Mail\ConfirmacionDeRegistro;
use App\Models\RegistroDePropietario;
use App\Support\Otp\ArrayWhatsAppOtpSender;
use App\Support\Otp\CloudApiWhatsAppOtpSender;
use App\Support\Registro\ConfirmacionDelRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Lo que pasa después de enviar el registro: el código por correo y por
 * WhatsApp, el enlace del correo, y qué queda marcado en la base.
 */
class ConfirmacionDelRegistroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ArrayWhatsAppOtpSender::reiniciar();
        Mail::fake();
    }

    /**
     * @return array<string, mixed>
     */
    private function envio(): array
    {
        return [
            'nombre' => 'Marta Ejemplo',
            'telefono' => '5512345678',
            'correo' => 'marta@correo.com',
            'lotes' => [['calle' => 'Nube', 'numero_oficial' => '45', 'manzana' => '9', 'lote' => '3', 'situacion' => 'terreno']],
            'acepto_aviso' => '1',
        ];
    }

    /**
     * Envía el formulario y devuelve el correo que salió, de donde se leen el
     * código y el enlace.
     */
    private function registrarYLeerCorreo(): ConfirmacionDeRegistro
    {
        $this->post(route('registro.store'), $this->envio())->assertRedirect(route('registro.confirmar'));

        $correo = null;
        Mail::assertSent(ConfirmacionDeRegistro::class, function (ConfirmacionDeRegistro $enviado) use (&$correo): bool {
            $correo = $enviado;

            return $enviado->hasTo('marta@correo.com');
        });

        return $correo;
    }

    public function test_al_enviar_sale_el_mismo_codigo_por_correo_y_por_whatsapp(): void
    {
        $correo = $this->registrarYLeerCorreo();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $correo->codigo);
        $this->assertSame($correo->codigo, ArrayWhatsAppOtpSender::ultimoCodigoPara('5512345678'));
        $this->assertStringContainsString('/registro/confirmar/', $correo->enlace);
    }

    public function test_el_registro_nace_sin_confirmar_y_sin_guardar_el_codigo_en_claro(): void
    {
        $correo = $this->registrarYLeerCorreo();

        $registro = RegistroDePropietario::query()->sole();

        $this->assertFalse($registro->estaConfirmado());
        $this->assertNull($registro->confirmado_en);
        $this->assertNotSame($correo->codigo, $registro->confirmacion_codigo_hash);
        $this->assertStringNotContainsString(substr($correo->enlace, -40), (string) $registro->confirmacion_enlace_hash);
        $this->assertArrayNotHasKey('confirmacion_codigo_hash', $registro->toArray());
    }

    public function test_el_codigo_correcto_confirma_y_queda_marcado_en_la_base(): void
    {
        $correo = $this->registrarYLeerCorreo();

        $this->post(route('registro.codigo'), ['codigo' => $correo->codigo])
            ->assertRedirect(route('registro.confirmar'));

        $registro = RegistroDePropietario::query()->sole();

        $this->assertTrue($registro->estaConfirmado());
        $this->assertSame(MedioDeConfirmacion::Codigo, $registro->confirmado_por);
        $this->assertDatabaseMissing('registros_de_propietarios', ['confirmado_en' => null]);

        $this->get(route('registro.confirmar'))->assertSee('Tu registro quedó confirmado');
    }

    public function test_un_codigo_equivocado_no_confirma_y_se_cuentan_los_intentos(): void
    {
        $correo = $this->registrarYLeerCorreo();
        $malo = $correo->codigo === '000000' ? '111111' : '000000';

        $this->post(route('registro.codigo'), ['codigo' => $malo])->assertSessionHasErrors('codigo');

        $registro = RegistroDePropietario::query()->sole();
        $this->assertFalse($registro->estaConfirmado());
        $this->assertSame(1, $registro->confirmacion_intentos);
    }

    public function test_tras_cinco_fallos_ni_el_codigo_bueno_sirve(): void
    {
        $correo = $this->registrarYLeerCorreo();
        $malo = $correo->codigo === '000000' ? '111111' : '000000';

        for ($i = 0; $i < ConfirmacionDelRegistro::MAX_INTENTOS; $i++) {
            $this->post(route('registro.codigo'), ['codigo' => $malo]);
        }

        $this->post(route('registro.codigo'), ['codigo' => $correo->codigo])->assertSessionHasErrors('codigo');

        $this->assertFalse(RegistroDePropietario::query()->sole()->estaConfirmado());
    }

    public function test_un_codigo_vencido_no_confirma(): void
    {
        $correo = $this->registrarYLeerCorreo();

        $this->travel(ConfirmacionDelRegistro::VIGENCIA_HORAS + 1)->hours();

        $this->post(route('registro.codigo'), ['codigo' => $correo->codigo])->assertSessionHasErrors('codigo');

        $this->assertFalse(RegistroDePropietario::query()->sole()->estaConfirmado());
    }

    public function test_el_codigo_tiene_que_ser_de_seis_digitos(): void
    {
        $this->registrarYLeerCorreo();

        $this->post(route('registro.codigo'), ['codigo' => 'abc'])->assertSessionHasErrors('codigo');
    }

    public function test_abrir_el_enlace_no_confirma_solo_muestra_el_boton(): void
    {
        $correo = $this->registrarYLeerCorreo();

        $this->get($correo->enlace)
            ->assertOk()
            ->assertSee('Confirmar mi registro');

        $this->assertFalse(RegistroDePropietario::query()->sole()->estaConfirmado());
    }

    public function test_tocar_el_boton_del_enlace_confirma_desde_otro_aparato_sin_sesion(): void
    {
        $correo = $this->registrarYLeerCorreo();

        // Otro aparato: ninguna sesión previa.
        $this->flushSession();

        $this->post($correo->enlace)->assertRedirect($correo->enlace);

        $registro = RegistroDePropietario::query()->sole();
        $this->assertTrue($registro->estaConfirmado());
        $this->assertSame(MedioDeConfirmacion::Enlace, $registro->confirmado_por);

        $this->get($correo->enlace)->assertSee('Tu registro quedó confirmado');
    }

    public function test_un_enlace_con_otro_token_no_existe(): void
    {
        $correo = $this->registrarYLeerCorreo();
        $falso = preg_replace('/[A-Za-z0-9]{40}$/', str_repeat('a', 40), $correo->enlace);

        $this->get($falso)->assertNotFound()->assertSee('Este enlace ya no sirve');
        $this->post($falso)->assertRedirect($falso);

        $this->assertFalse(RegistroDePropietario::query()->sole()->estaConfirmado());
    }

    public function test_un_enlace_vencido_no_confirma(): void
    {
        $correo = $this->registrarYLeerCorreo();

        $this->travel(ConfirmacionDelRegistro::VIGENCIA_HORAS + 1)->hours();

        $this->get($correo->enlace)->assertSee('ya venció');
        $this->post($correo->enlace);

        $this->assertFalse(RegistroDePropietario::query()->sole()->estaConfirmado());
    }

    public function test_reenviar_manda_un_codigo_nuevo_y_el_anterior_deja_de_servir(): void
    {
        $viejo = $this->registrarYLeerCorreo();

        $this->travel(ConfirmacionDelRegistro::ESPERA_ENTRE_ENVIOS + 1)->seconds();

        $this->post(route('registro.reenviar'))->assertSessionHas('registro.info');

        $nuevo = ArrayWhatsAppOtpSender::ultimoCodigoPara('5512345678');
        $this->assertNotSame($viejo->codigo, $nuevo);

        $malo = $viejo->codigo === $nuevo ? '000001' : $viejo->codigo;
        $this->post(route('registro.codigo'), ['codigo' => $malo])->assertSessionHasErrors('codigo');
        $this->post(route('registro.codigo'), ['codigo' => $nuevo])->assertSessionHasNoErrors();

        $this->assertTrue(RegistroDePropietario::query()->sole()->estaConfirmado());
    }

    public function test_reenviar_pide_esperar_un_minuto(): void
    {
        $this->registrarYLeerCorreo();

        $this->post(route('registro.reenviar'))->assertSessionHasErrors('reenvio');
    }

    public function test_si_whatsapp_falla_el_registro_se_guarda_y_el_correo_sale(): void
    {
        ArrayWhatsAppOtpSender::$fallar = true;

        $this->post(route('registro.store'), $this->envio())->assertRedirect(route('registro.confirmar'));

        $this->assertDatabaseCount('registros_de_propietarios', 1);
        Mail::assertSent(ConfirmacionDeRegistro::class);

        $this->get(route('registro.confirmar'))->assertSee('a tu correo');
    }

    public function test_sin_correo_el_codigo_sale_solo_por_whatsapp(): void
    {
        $this->post(route('registro.store'), [...$this->envio(), 'correo' => ''])->assertRedirect(route('registro.confirmar'));

        Mail::assertNothingSent();
        $this->assertNotNull(ArrayWhatsAppOtpSender::ultimoCodigoPara('5512345678'));
    }

    public function test_sin_telefono_el_codigo_sale_solo_por_correo(): void
    {
        $this->post(route('registro.store'), [...$this->envio(), 'telefono' => '']);

        Mail::assertSent(ConfirmacionDeRegistro::class);
        $this->assertSame([], ArrayWhatsAppOtpSender::$enviados);
    }

    public function test_la_pantalla_sin_registro_pendiente_regresa_al_formulario(): void
    {
        $this->get(route('registro.confirmar'))->assertRedirect(route('registro'));
        $this->post(route('registro.codigo'), ['codigo' => '123456'])->assertRedirect(route('registro'));
    }

    public function test_la_pantalla_no_muestra_el_correo_ni_el_telefono_completos(): void
    {
        $this->post(route('registro.store'), $this->envio());

        $this->get(route('registro.confirmar'))
            ->assertSee('ma***@correo.com')
            ->assertSee('terminación 5678')
            ->assertDontSee('marta@correo.com')
            ->assertDontSee('5512345678');
    }

    public function test_el_cuerpo_para_la_api_de_whatsapp_lleva_el_codigo_en_cuerpo_y_boton(): void
    {
        $cuerpo = CloudApiWhatsAppOtpSender::cuerpo('525512345678', '123456', 'codigo_verificacion', 'es_MX');

        $this->assertSame('whatsapp', $cuerpo['messaging_product']);
        $this->assertSame('525512345678', $cuerpo['to']);
        $this->assertSame('codigo_verificacion', $cuerpo['template']['name']);
        $this->assertSame('es_MX', $cuerpo['template']['language']['code']);
        $this->assertSame('123456', $cuerpo['template']['components'][0]['parameters'][0]['text']);
        $this->assertSame('url', $cuerpo['template']['components'][1]['sub_type']);
        $this->assertSame('123456', $cuerpo['template']['components'][1]['parameters'][0]['text']);
    }

    public function test_el_sender_de_la_api_en_la_nube_llama_al_endpoint_con_lada_y_token(): void
    {
        config([
            'services.whatsapp.token' => 'token-de-prueba',
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.version' => 'v23.0',
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        (new CloudApiWhatsAppOtpSender)->send('5512345678', '654321');

        Http::assertSent(fn ($peticion) => $peticion->url() === 'https://graph.facebook.com/v23.0/1234567890/messages'
            && $peticion->hasHeader('Authorization', 'Bearer token-de-prueba')
            && $peticion['to'] === '525512345678'
            && $peticion['template']['components'][0]['parameters'][0]['text'] === '654321');
    }
}
