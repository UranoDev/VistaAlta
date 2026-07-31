<?php

declare(strict_types=1);

namespace Tests\Feature\Propuesta;

use App\Models\Comentario;
use App\Models\RecepcionDeComentarios;
use App\Models\ViaDeRecepcion;
use App\Support\Otp\ArrayOtpSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La Vía de recepción decide *por dónde llegan* los Comentarios. No decide si se
 * admiten —eso es la Recepción de comentarios, que manda sobre ésta— ni qué se
 * publica —eso es la Cola de moderación—.
 *
 * Corre con OTP_CHANNEL=array (phpunit.xml): ningún SMS sale de aquí, y por eso
 * se puede afirmar que en la vía de WhatsApp no se manda ninguno.
 */
class ViaDeRecepcionTest extends TestCase
{
    use RefreshDatabase;

    private const TELEFONO = '4421234567';

    private const COOKIE = 'telefono_validado';

    protected function setUp(): void
    {
        parent::setUp();

        ArrayOtpSender::$enviados = [];
    }

    // ── De fábrica ────────────────────────────────────────────────────────────

    /**
     * El modo con el que esto sale al aire. En producción el SMS no se entrega,
     * así que arrancar en `otp` sería publicar un formulario que no funciona.
     */
    public function test_de_fabrica_los_comentarios_se_reciben_por_whatsapp(): void
    {
        $this->assertTrue(ViaDeRecepcion::actual()->esWhatsApp());

        $this->get(route('propuesta'))->assertSee('Escribir por WhatsApp');
    }

    /**
     * Leer la página no escribe en la base, igual que con el otro interruptor:
     * una visita no tiene por qué provocar un INSERT.
     */
    public function test_leer_la_pagina_no_siembra_el_renglon(): void
    {
        $this->get(route('propuesta'))->assertOk();

        $this->assertDatabaseCount('via_de_recepcion', 0);
    }

    // ── Modo WhatsApp ─────────────────────────────────────────────────────────

    public function test_en_whatsapp_la_pagina_no_pide_telefono_ni_promete_ningun_sms(): void
    {
        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertOk();
        $respuesta->assertDontSee('Enviarme el código', escape: false);
        $respuesta->assertDontSee('Tu celular');
        $respuesta->assertDontSee('código por SMS', escape: false);
        $respuesta->assertSee('Los comentarios se reciben por WhatsApp');
    }

    public function test_el_enlace_abre_la_conversacion_con_el_numero_configurado(): void
    {
        ViaDeRecepcion::cambiarNumeroDeWhatsApp('+52 33 1111 2222');

        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertSee('https://wa.me/523311112222?text=', escape: false);
        // El mismo número, escrito como se lee, para quien lo va a anotar.
        $respuesta->assertSee('+52 33 1111 2222');
    }

    public function test_sin_numero_capturado_el_enlace_usa_el_de_fabrica(): void
    {
        $this->get(route('propuesta'))
            ->assertSee('https://wa.me/'.ViaDeRecepcion::NUMERO_DE_FABRICA.'?text=', escape: false);
    }

    /**
     * Lo que sostiene la garantía del Comentario privado en esta vía: el
     * comentario lo captura la Mesa Directiva, así que la intención de
     * visibilidad tiene que venir escrita por el autor y no interpretada.
     */
    public function test_el_texto_prellenado_pide_la_intencion_de_visibilidad(): void
    {
        $respuesta = $this->get(route('propuesta'));

        // Van urlencodificadas dentro del href: es el texto que el colono manda.
        $respuesta->assertSee('P%C3%9ABLICO', escape: false);
        $respuesta->assertSee('PRIVADO', escape: false);
        $respuesta->assertSee('Mi%20comentario', escape: false);
    }

    /**
     * Esconder el formulario no apaga una ruta pública. Sin este rechazo, un POST
     * armado a mano sigue mandando SMS por Twilio —que cuesta— contra un canal
     * que ni siquiera está activo.
     */
    public function test_en_whatsapp_las_rutas_del_otp_rechazan_y_no_sale_ningun_sms(): void
    {
        $this->post(route('comentarios.codigo'), ['telefono' => self::TELEFONO])
            ->assertSessionHas('comentario.aviso');

        $this->assertSame([], ArrayOtpSender::$enviados);
        $this->assertDatabaseCount('otps', 0);

        $this->post(route('comentarios.validar'), ['codigo' => '123456'])
            ->assertCookieMissing(self::COOKIE);
    }

    /**
     * Las otras dos rutas del flujo tampoco quedan vivas: en esta vía el sitio no
     * es por donde se reciben comentarios, y una Ventana de validación abierta
     * antes de mover el interruptor no debería colar uno por la puerta de atrás.
     */
    public function test_en_whatsapp_tampoco_se_cuela_un_comentario_por_la_ruta_del_sitio(): void
    {
        $respuesta = $this->withCookie(self::COOKIE, self::TELEFONO)
            ->post(route('comentarios.store'), [
                'nombre' => 'Laura Medina',
                'comentario' => 'Un comentario escrito en el sitio.',
                'visibilidad' => 'publico',
            ]);

        $respuesta->assertSessionHas('comentario.aviso');
        $this->assertDatabaseCount('comentarios', 0);

        $this->post(route('comentarios.cambiar-telefono'))->assertSessionHas('comentario.aviso');
    }

    /**
     * El rechazo no deja un callejón sin salida: manda a donde sí se recibe.
     */
    public function test_el_rechazo_apunta_a_whatsapp_en_la_propia_pagina(): void
    {
        $this->post(route('comentarios.codigo'), ['telefono' => self::TELEFONO]);

        $this->followingRedirects()
            ->post(route('comentarios.codigo'), ['telefono' => self::TELEFONO])
            ->assertSee('los comentarios se reciben por WhatsApp', escape: false);
    }

    // ── Modo OTP ──────────────────────────────────────────────────────────────

    public function test_en_otp_el_flujo_de_hoy_funciona_igual(): void
    {
        ViaDeRecepcion::usarOtp();

        $this->get(route('propuesta'))
            ->assertSee('Enviarme el código', escape: false)
            ->assertDontSee('Escribir por WhatsApp');

        $this->post(route('comentarios.codigo'), ['telefono' => self::TELEFONO])
            ->assertSessionHas('comentario.info');

        $this->assertNotNull(ArrayOtpSender::ultimoCodigoPara(self::TELEFONO));
    }

    /**
     * El punto entero del interruptor: volver al canal bueno es moverlo, no
     * desplegar.
     */
    public function test_se_puede_ir_y_volver_entre_las_dos_vias(): void
    {
        ViaDeRecepcion::usarOtp();
        $this->get(route('propuesta'))->assertSee('Enviarme el código', escape: false);

        ViaDeRecepcion::usarWhatsApp();
        $this->get(route('propuesta'))->assertSee('Escribir por WhatsApp');

        ViaDeRecepcion::usarOtp();
        $this->get(route('propuesta'))->assertSee('Enviarme el código', escape: false);

        $this->assertDatabaseCount('via_de_recepcion', 1);
    }

    // ── El interruptor de Recepción manda sobre la vía ─────────────────────────

    /**
     * Los dos últimos renglones de la tabla del issue son idénticos a propósito:
     * con la recepción cerrada la vía es irrelevante.
     */
    public function test_con_la_recepcion_cerrada_no_se_muestra_ninguno_de_los_dos(): void
    {
        RecepcionDeComentarios::cerrar();

        foreach ([fn () => ViaDeRecepcion::usarOtp(), fn () => ViaDeRecepcion::usarWhatsApp()] as $ponerVia) {
            $ponerVia();

            $respuesta = $this->get(route('propuesta'));

            $respuesta->assertOk();
            $respuesta->assertDontSee('Enviarme el código', escape: false);
            $respuesta->assertDontSee('Escribir por WhatsApp');
            $respuesta->assertSee('cerró la recepción de comentarios', escape: false);
        }
    }

    /**
     * Y el aviso que se lleva quien insista es el de la recepción cerrada, no el
     * de la vía: decir por cuál habría llegado sería contestar otra pregunta.
     */
    public function test_cerrada_el_aviso_es_el_de_la_recepcion_y_no_el_de_la_via(): void
    {
        RecepcionDeComentarios::cerrar();

        $this->followingRedirects()
            ->post(route('comentarios.codigo'), ['telefono' => self::TELEFONO])
            ->assertSee('cerró la recepción de comentarios', escape: false)
            ->assertDontSee('se reciben por WhatsApp');
    }

    // ── Lo publicado no se toca ───────────────────────────────────────────────

    public function test_los_comentarios_publicados_se_siguen_mostrando_en_ambas_vias(): void
    {
        Comentario::factory()->publicado()->create(['comentario' => 'Comentario ya publicado.']);

        ViaDeRecepcion::usarWhatsApp();
        $this->get(route('propuesta'))->assertSee('Comentario ya publicado.');

        ViaDeRecepcion::usarOtp();
        $this->get(route('propuesta'))->assertSee('Comentario ya publicado.');
    }

    // ── El número ─────────────────────────────────────────────────────────────

    public function test_el_numero_se_guarda_en_digitos_venga_como_venga(): void
    {
        ViaDeRecepcion::cambiarNumeroDeWhatsApp('+52 (55) 3126-9267');

        $this->assertSame('525531269267', ViaDeRecepcion::actual()->numeroDeWhatsApp());
        $this->assertDatabaseHas('via_de_recepcion', ['numero_whatsapp' => '525531269267']);
    }

    /**
     * Un número que no es el formato mexicano se muestra tal cual antes que
     * partirlo mal: la Mesa Directiva puede capturar otra lada algún día.
     */
    public function test_un_numero_de_otra_lada_se_muestra_sin_despiezar(): void
    {
        ViaDeRecepcion::cambiarNumeroDeWhatsApp('1 415 555 0100');

        $this->assertSame('+14155550100', ViaDeRecepcion::actual()->numeroLegible());
    }

    /**
     * Cambiar el número no cambia la vía, y cambiar la vía no borra el número.
     */
    public function test_el_numero_y_la_via_no_se_pisan(): void
    {
        ViaDeRecepcion::usarOtp();
        ViaDeRecepcion::cambiarNumeroDeWhatsApp('523311112222');

        $this->assertTrue(ViaDeRecepcion::actual()->esOtp());

        ViaDeRecepcion::usarWhatsApp();

        $this->assertSame('523311112222', ViaDeRecepcion::actual()->numeroDeWhatsApp());
        $this->assertDatabaseCount('via_de_recepcion', 1);
    }
}
