<?php

declare(strict_types=1);

namespace Tests\Feature\Registro;

use App\Enums\MedioDeConfirmacion;
use App\Mail\ConfirmacionDeRegistro;
use App\Models\RegistroDePropietario;
use App\Support\Otp\ArrayOtpSender;
use App\Support\Otp\LimiteDeEnvioDeOtp;
use App\Support\Registro\ConfirmacionDelRegistro;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Las dos verificaciones del registro, cada una por su lado: el correo (código y
 * enlace) y el celular (código por SMS). Una no verifica a la otra, y cada una
 * deja su propia marca en la base.
 */
class ConfirmacionDelRegistroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ArrayOtpSender::$enviados = [];
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
     * Envía el formulario y devuelve el correo que salió (de donde se leen el
     * código y el enlace) y el código del SMS.
     *
     * @return array{0: ConfirmacionDeRegistro, 1: string}
     */
    private function registrar(): array
    {
        $this->post(route('registro.store'), $this->envio())->assertRedirect(route('registro.confirmar'));

        $correo = null;
        Mail::assertSent(ConfirmacionDeRegistro::class, function (ConfirmacionDeRegistro $enviado) use (&$correo): bool {
            $correo = $enviado;

            return $enviado->hasTo('marta@correo.com');
        });

        return [$correo, (string) ArrayOtpSender::ultimoCodigoPara('5512345678')];
    }

    /**
     * Fija los dos códigos para poder probar que uno no sirve en el otro medio:
     * con códigos al azar, dos iguales por casualidad volverían la prueba
     * intermitente.
     */
    private function fijarCodigos(RegistroDePropietario $registro, string $correo = '222222', string $telefono = '111111'): void
    {
        $registro->forceFill([
            'correo_codigo_hash' => Hash::make($correo),
            'telefono_codigo_hash' => Hash::make($telefono),
        ])->save();
    }

    public function test_al_enviar_sale_un_codigo_por_correo_y_otro_por_sms(): void
    {
        [$correo, $sms] = $this->registrar();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $correo->codigo);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $sms);
        $this->assertStringContainsString('/registro/confirmar/', $correo->enlace);
    }

    public function test_el_registro_nace_sin_nada_verificado_y_sin_guardar_los_codigos_en_claro(): void
    {
        [$correo, $sms] = $this->registrar();

        $registro = RegistroDePropietario::query()->sole();

        $this->assertFalse($registro->correoVerificado());
        $this->assertFalse($registro->telefonoVerificado());
        $this->assertFalse($registro->estaVerificado());
        $this->assertNull($registro->correo_verificado_en);
        $this->assertNull($registro->telefono_verificado_en);
        $this->assertNotSame($correo->codigo, $registro->correo_codigo_hash);
        $this->assertNotSame($sms, $registro->telefono_codigo_hash);
        $this->assertArrayNotHasKey('correo_codigo_hash', $registro->toArray());
        $this->assertArrayNotHasKey('telefono_codigo_hash', $registro->toArray());
    }

    public function test_el_codigo_del_correo_verifica_solo_el_correo(): void
    {
        [$correo] = $this->registrar();

        $this->post(route('registro.codigo', 'correo'), ['codigo' => $correo->codigo])
            ->assertRedirect(route('registro.confirmar'));

        $registro = RegistroDePropietario::query()->sole();

        $this->assertTrue($registro->correoVerificado());
        $this->assertSame(MedioDeConfirmacion::Codigo, $registro->correo_verificado_por);
        $this->assertFalse($registro->telefonoVerificado());
        $this->assertFalse($registro->estaVerificado());
    }

    public function test_el_codigo_del_sms_verifica_solo_el_celular(): void
    {
        [, $sms] = $this->registrar();

        $this->post(route('registro.codigo', 'telefono'), ['codigo' => $sms])
            ->assertRedirect(route('registro.confirmar'));

        $registro = RegistroDePropietario::query()->sole();

        $this->assertTrue($registro->telefonoVerificado());
        $this->assertFalse($registro->correoVerificado());
        $this->assertFalse($registro->estaVerificado());
    }

    public function test_con_los_dos_codigos_el_registro_queda_verificado(): void
    {
        [$correo, $sms] = $this->registrar();

        $this->post(route('registro.codigo', 'correo'), ['codigo' => $correo->codigo]);
        $this->post(route('registro.codigo', 'telefono'), ['codigo' => $sms]);

        $this->assertTrue(RegistroDePropietario::query()->sole()->estaVerificado());
        $this->assertSame(1, RegistroDePropietario::query()->verificados()->count());

        $this->get(route('registro.confirmar'))
            ->assertSee('Tu registro quedó verificado')
            ->assertSee('Tu correo y tu celular quedaron verificados');
    }

    public function test_el_codigo_de_un_medio_no_sirve_en_el_otro(): void
    {
        $this->registrar();
        $registro = RegistroDePropietario::query()->sole();
        $this->fijarCodigos($registro, correo: '222222', telefono: '111111');

        // El código del correo, escrito en el celular, no verifica nada.
        $this->post(route('registro.codigo', 'telefono'), ['codigo' => '222222'])->assertSessionHasErrors('codigo_telefono');
        // Y el del SMS, escrito en el correo, tampoco.
        $this->post(route('registro.codigo', 'correo'), ['codigo' => '111111'])->assertSessionHasErrors('codigo_correo');

        $registro->refresh();
        $this->assertFalse($registro->correoVerificado());
        $this->assertFalse($registro->telefonoVerificado());
    }

    public function test_los_intentos_fallidos_se_cuentan_por_medio(): void
    {
        $this->registrar();
        $registro = RegistroDePropietario::query()->sole();
        $this->fijarCodigos($registro);

        $this->post(route('registro.codigo', 'telefono'), ['codigo' => '000000'])
            ->assertSessionHasErrors(['codigo_telefono' => 'El código es incorrecto o ya venció. Si ya lo intentaste varias veces, pide uno nuevo.']);

        $registro->refresh();
        $this->assertSame(1, $registro->telefono_intentos);
        $this->assertSame(0, $registro->correo_intentos);
    }

    public function test_tras_cinco_fallos_ese_medio_se_cierra_pero_el_otro_sigue_sirviendo(): void
    {
        $this->registrar();
        $registro = RegistroDePropietario::query()->sole();
        $this->fijarCodigos($registro);

        for ($i = 0; $i < ConfirmacionDelRegistro::MAX_INTENTOS; $i++) {
            $this->post(route('registro.codigo', 'telefono'), ['codigo' => '000000']);
        }

        // Ni el código bueno sirve en el celular...
        $this->post(route('registro.codigo', 'telefono'), ['codigo' => '111111'])->assertSessionHasErrors('codigo_telefono');
        $this->assertFalse($registro->fresh()->telefonoVerificado());

        // ...pero el correo no se enteró.
        $this->post(route('registro.codigo', 'correo'), ['codigo' => '222222'])->assertSessionHasNoErrors();
        $this->assertTrue($registro->fresh()->correoVerificado());
    }

    public function test_el_codigo_del_sms_vence_a_los_quince_minutos_y_el_del_correo_sigue_vigente(): void
    {
        $this->registrar();
        $registro = RegistroDePropietario::query()->sole();
        $this->fijarCodigos($registro);

        $this->travel(ConfirmacionDelRegistro::VIGENCIA_SMS_MINUTOS + 1)->minutes();

        $this->post(route('registro.codigo', 'telefono'), ['codigo' => '111111'])->assertSessionHasErrors('codigo_telefono');
        // El del correo dura 24 horas: a esa hora todavía sirve.
        $this->post(route('registro.codigo', 'correo'), ['codigo' => '222222'])->assertSessionHasNoErrors();
        $this->assertTrue($registro->fresh()->correoVerificado());
    }

    public function test_un_codigo_del_correo_vencido_no_verifica(): void
    {
        [$correo] = $this->registrar();

        $this->travel(ConfirmacionDelRegistro::VIGENCIA_CORREO_HORAS + 1)->hours();

        $this->post(route('registro.codigo', 'correo'), ['codigo' => $correo->codigo])->assertSessionHasErrors('codigo_correo');
        $this->assertFalse(RegistroDePropietario::query()->sole()->correoVerificado());
    }

    public function test_el_codigo_tiene_que_ser_de_seis_digitos(): void
    {
        $this->registrar();

        $this->post(route('registro.codigo', 'correo'), ['codigo' => 'abc'])->assertSessionHasErrors('codigo_correo');
        $this->post(route('registro.codigo', 'telefono'), [])->assertSessionHasErrors('codigo_telefono');
    }

    public function test_solo_hay_dos_medios(): void
    {
        $this->registrar();

        $this->post('/registro/confirmar/whatsapp/codigo', ['codigo' => '123456'])->assertNotFound();
        $this->post('/registro/confirmar/whatsapp/reenviar')->assertNotFound();
    }

    public function test_abrir_el_enlace_no_verifica_solo_muestra_el_boton(): void
    {
        [$correo] = $this->registrar();

        $this->get($correo->enlace)
            ->assertOk()
            ->assertSee('Verificar mi correo');

        $this->assertFalse(RegistroDePropietario::query()->sole()->correoVerificado());
    }

    public function test_tocar_el_boton_del_enlace_verifica_el_correo_y_deja_el_celular_por_hacer(): void
    {
        [$correo] = $this->registrar();

        // Otro aparato: ninguna sesión previa.
        $this->flushSession();

        $this->post($correo->enlace)->assertRedirect(route('registro.confirmar'));

        $registro = RegistroDePropietario::query()->sole();
        $this->assertTrue($registro->correoVerificado());
        $this->assertSame(MedioDeConfirmacion::Enlace, $registro->correo_verificado_por);
        $this->assertFalse($registro->telefonoVerificado());

        // La sesión se abrió con el enlace: ahí mismo queda el SMS por hacer.
        $this->get(route('registro.confirmar'))
            ->assertSee('Verifica tu correo y tu celular')
            ->assertSee('Código del SMS')
            ->assertDontSee('Código del correo');
    }

    public function test_un_enlace_ya_usado_lleva_a_lo_que_falta_en_vez_de_mostrar_el_boton(): void
    {
        [$correo] = $this->registrar();
        $this->post($correo->enlace);
        $this->flushSession();

        $this->get($correo->enlace)->assertRedirect(route('registro.confirmar'));
    }

    public function test_un_enlace_con_otro_token_no_existe(): void
    {
        [$correo] = $this->registrar();
        $falso = preg_replace('/[A-Za-z0-9]{40}$/', str_repeat('a', 40), $correo->enlace);

        $this->get($falso)->assertNotFound()->assertSee('Este enlace ya no sirve');
        $this->post($falso)->assertRedirect($falso);

        $this->assertFalse(RegistroDePropietario::query()->sole()->correoVerificado());
    }

    public function test_un_enlace_vencido_no_verifica(): void
    {
        [$correo] = $this->registrar();

        $this->travel(ConfirmacionDelRegistro::VIGENCIA_CORREO_HORAS + 1)->hours();

        $this->get($correo->enlace)->assertSee('ya venció');
        $this->post($correo->enlace);

        $this->assertFalse(RegistroDePropietario::query()->sole()->correoVerificado());
    }

    public function test_reenviar_el_correo_manda_un_codigo_nuevo_y_no_toca_el_sms(): void
    {
        [$viejo, $sms] = $this->registrar();

        $this->travel(ConfirmacionDelRegistro::ESPERA_ENTRE_ENVIOS + 1)->seconds();

        $this->post(route('registro.reenviar', 'correo'))->assertSessionHas('registro.info');

        Mail::assertSent(ConfirmacionDeRegistro::class, 2);

        // El SMS no se reenvió: su código sigue siendo el mismo.
        $this->assertSame($sms, ArrayOtpSender::ultimoCodigoPara('5512345678'));
        $this->post(route('registro.codigo', 'telefono'), ['codigo' => $sms])->assertSessionHasNoErrors();

        // Y el código viejo del correo ya no sirve.
        $registro = RegistroDePropietario::query()->sole();
        $this->assertFalse(Hash::check($viejo->codigo, (string) $registro->correo_codigo_hash));
    }

    public function test_reenviar_el_sms_manda_un_codigo_nuevo_y_solo_el_nuevo_sirve(): void
    {
        $this->registrar();

        $this->travel(ConfirmacionDelRegistro::ESPERA_ENTRE_ENVIOS + 1)->seconds();

        $this->post(route('registro.reenviar', 'telefono'))->assertSessionHas('registro.info');

        $nuevo = (string) ArrayOtpSender::ultimoCodigoPara('5512345678');
        $registro = RegistroDePropietario::query()->sole();

        $this->assertTrue(Hash::check($nuevo, (string) $registro->telefono_codigo_hash));
        $this->assertSame(0, $registro->telefono_intentos);
    }

    public function test_reenviar_pide_esperar_un_minuto_por_medio(): void
    {
        $this->registrar();

        $this->post(route('registro.reenviar', 'correo'))->assertSessionHasErrors('reenvio_correo');
        $this->post(route('registro.reenviar', 'telefono'))->assertSessionHasErrors('reenvio_telefono');
    }

    public function test_el_tope_de_sms_corta_los_reenvios_con_un_mensaje_claro(): void
    {
        // El registro ya gastó un SMS; el tope por teléfono es de tres.
        $this->registrar();

        foreach ([1, 2] as $vuelta) {
            $this->travel(ConfirmacionDelRegistro::ESPERA_ENTRE_ENVIOS + 1)->seconds();
            $this->post(route('registro.reenviar', 'telefono'))->assertSessionHasNoErrors();
        }

        $this->travel(ConfirmacionDelRegistro::ESPERA_ENTRE_ENVIOS + 1)->seconds();
        $this->post(route('registro.reenviar', 'telefono'))->assertSessionHasErrors(['reenvio_telefono']);

        $this->assertStringContainsString('Pediste demasiados códigos', session('errors')->first('reenvio_telefono'));
    }

    public function test_si_el_tope_de_sms_ya_se_agoto_el_registro_se_guarda_y_el_correo_sale(): void
    {
        $limite = app(LimiteDeEnvioDeOtp::class);
        foreach ([1, 2, 3] as $i) {
            $limite->consumir('5512345678', '10.0.0.1');
        }

        $this->post(route('registro.store'), $this->envio())->assertRedirect(route('registro.confirmar'));

        $this->assertDatabaseCount('registros_de_propietarios', 1);
        Mail::assertSent(ConfirmacionDeRegistro::class);
        $this->assertNull(ArrayOtpSender::ultimoCodigoPara('5512345678'));

        $this->get(route('registro.confirmar'))
            ->assertSee('No pudimos mandarte el código por SMS')
            ->assertDontSee('No pudimos mandarte el código por correo');
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function registrarCon(array $cambios): void
    {
        $this->post(route('registro.store'), [...$this->envio(), ...$cambios])->assertRedirect(route('registro.confirmar'));
    }

    public function test_quien_da_solo_el_correo_recibe_solo_el_correo_y_no_tiene_celular_que_verificar(): void
    {
        $this->registrarCon(['telefono' => '']);

        Mail::assertSent(ConfirmacionDeRegistro::class, fn (ConfirmacionDeRegistro $correo) => $correo->conCelular === false);
        $this->assertSame([], ArrayOtpSender::$enviados);

        $this->get(route('registro.confirmar'))
            ->assertSee('Verifica tu correo')
            ->assertSee('Código del correo')
            ->assertDontSee('Código del SMS')
            ->assertDontSee('Son dos verificaciones separadas');

        // Pedir un SMS que nadie va a recibir no hace nada.
        $this->post(route('registro.reenviar', 'telefono'))->assertRedirect(route('registro.confirmar'));
        $this->assertSame([], ArrayOtpSender::$enviados);
    }

    public function test_con_el_unico_medio_verificado_el_registro_queda_verificado(): void
    {
        $this->registrarCon(['telefono' => '']);

        $registro = RegistroDePropietario::query()->sole();
        $this->assertFalse($registro->estaVerificado());

        $this->fijarCodigos($registro);
        $this->post(route('registro.codigo', 'correo'), ['codigo' => '222222']);

        $this->assertTrue($registro->fresh()->estaVerificado());
        $this->get(route('registro.confirmar'))
            ->assertSee('Tu registro quedó verificado')
            ->assertSee('Tu correo quedó verificado');
    }

    public function test_quien_da_solo_el_celular_recibe_solo_el_sms(): void
    {
        $this->registrarCon(['correo' => '']);

        Mail::assertNothingSent();
        $this->assertNotNull(ArrayOtpSender::ultimoCodigoPara('5512345678'));

        $this->get(route('registro.confirmar'))
            ->assertSee('Verifica tu celular')
            ->assertSee('Código del SMS')
            ->assertDontSee('Código del correo');

        $this->post(route('registro.codigo', 'telefono'), ['codigo' => (string) ArrayOtpSender::ultimoCodigoPara('5512345678')]);

        $this->assertTrue(RegistroDePropietario::query()->sole()->estaVerificado());
    }

    public function test_quien_da_los_dos_tiene_que_verificar_los_dos(): void
    {
        [$correo, $sms] = $this->registrar();

        $this->post(route('registro.codigo', 'correo'), ['codigo' => $correo->codigo]);
        $this->assertFalse(RegistroDePropietario::query()->sole()->estaVerificado());

        $this->post(route('registro.codigo', 'telefono'), ['codigo' => $sms]);
        $this->assertTrue(RegistroDePropietario::query()->sole()->estaVerificado());
    }

    public function test_la_pantalla_dice_que_son_dos_verificaciones_separadas(): void
    {
        $this->registrar();

        $this->get(route('registro.confirmar'))
            ->assertSee('Son dos verificaciones separadas')
            ->assertSee('Código del correo')
            ->assertSee('Código del SMS');
    }

    public function test_la_pantalla_no_muestra_el_correo_ni_el_telefono_completos(): void
    {
        $this->registrar();

        $this->get(route('registro.confirmar'))
            ->assertSee('ma***@correo.com')
            ->assertSee('terminación 5678')
            ->assertDontSee('marta@correo.com')
            ->assertDontSee('5512345678');
    }

    public function test_la_pantalla_sin_registro_pendiente_regresa_al_formulario(): void
    {
        $this->get(route('registro.confirmar'))->assertRedirect(route('registro'));
        $this->post(route('registro.codigo', 'correo'), ['codigo' => '123456'])->assertRedirect(route('registro'));
    }

    /**
     * Si el registro que se estaba verificando desaparece (lo borraron del panel,
     * o la sesión venció), escribir el código no puede mandar al formulario en
     * silencio: la persona no sabría si su código salió bien.
     */
    public function test_si_el_registro_pendiente_ya_no_existe_se_avisa_en_vez_de_volver_en_silencio(): void
    {
        $this->registrar();

        RegistroDePropietario::query()->delete();

        $this->post(route('registro.codigo', 'correo'), ['codigo' => '123456'])
            ->assertRedirect(route('registro'))
            ->assertSessionHas('registro.aviso');

        $this->post(route('registro.reenviar', 'telefono'))->assertSessionHas('registro.aviso');
        $this->get(route('registro.confirmar'))->assertRedirect(route('registro'))->assertSessionHas('registro.aviso');

        $this->followingRedirects()->get(route('registro.confirmar'))->assertSee('Llena el formulario otra vez');
    }

    /**
     * En producción un correo o un SMS configurados en `log` o `array` no fallan:
     * dejan el código en el log del servidor y nadie lo recibe. La pantalla no
     * puede decir «te mandamos un código» en ese caso.
     */
    public function test_en_produccion_con_los_dos_medios_en_log_no_dice_que_los_mando(): void
    {
        $this->app['env'] = 'production';
        // En producción Laravel exige el token CSRF, que en pruebas no se manda.
        $this->withoutMiddleware(PreventRequestForgery::class);
        config(['mail.default' => 'log', 'services.otp.channel' => 'log']);

        $this->post(route('registro.store'), $this->envio())->assertRedirect(route('registro.confirmar'));

        $this->get(route('registro.confirmar'))
            ->assertSee('No pudimos mandarte el código por correo')
            ->assertSee('No pudimos mandarte el código por SMS')
            ->assertDontSee('Te mandamos un SMS con un código');
    }

    public function test_en_produccion_con_el_correo_real_y_el_sms_en_log_solo_dice_el_correo(): void
    {
        $this->app['env'] = 'production';
        $this->withoutMiddleware(PreventRequestForgery::class);
        config(['mail.default' => 'smtp', 'services.otp.channel' => 'log']);

        $this->post(route('registro.store'), $this->envio());

        $this->get(route('registro.confirmar'))
            ->assertSee('Te mandamos un código de 6 dígitos a ma***@correo.com')
            ->assertSee('No pudimos mandarte el código por SMS')
            ->assertDontSee('No pudimos mandarte el código por correo');
    }
}
