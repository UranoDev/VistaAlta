<?php

declare(strict_types=1);

namespace Tests\Feature\Propuesta;

use App\Models\Otp;
use App\Models\RecepcionDeComentarios;
use App\Models\ViaDeRecepcion;
use App\Support\Otp\ArrayOtpSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La salida de la pantalla del código para quien escribió mal su celular: el
 * SMS se fue a un número que no tiene en la mano, así que reenviarlo no sirve
 * de nada.
 */
class CambiarTelefonoTest extends TestCase
{
    use RefreshDatabase;

    private const TELEFONO = '4421234567';

    private const OTRO_TELEFONO = '4429999999';

    private const COOKIE = 'telefono_validado';

    protected function setUp(): void
    {
        parent::setUp();

        ArrayOtpSender::$enviados = [];
        // La pantalla del código solo existe con la Vía de recepción en `otp`.
        ViaDeRecepcion::usarOtp();
    }

    private function pedirCodigo(string $telefono = self::TELEFONO): void
    {
        $this->post(route('comentarios.codigo'), ['telefono' => $telefono]);
    }

    public function test_la_pantalla_del_codigo_ofrece_usar_otro_numero(): void
    {
        $this->pedirCodigo();

        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertSee('Usar otro número', escape: false);
        // Sigue siendo una salida secundaria: la acción esperada no cambia.
        $respuesta->assertSee('Validar mi teléfono', escape: false);
    }

    public function test_usarlo_devuelve_el_formulario_al_campo_de_celular_vacio(): void
    {
        $this->pedirCodigo();

        $respuesta = $this->post(route('comentarios.cambiar-telefono'));

        $respuesta->assertRedirect(route('propuesta').'#comentarios');
        $respuesta->assertSessionMissing('comentario.telefono');

        $pagina = $this->get(route('propuesta'));

        $pagina->assertSee('Enviarme el código', escape: false);
        // El punto es corregir el número, no repetirlo: el campo aparece vacío.
        $pagina->assertDontSee(self::TELEFONO);
    }

    public function test_no_borra_el_codigo_que_ya_se_habia_generado(): void
    {
        $this->pedirCodigo();
        $vigente = ArrayOtpSender::ultimoCodigoPara(self::TELEFONO);

        $this->post(route('comentarios.cambiar-telefono'));

        // Cambiar de número es una decisión de la interfaz; el OTP vence solo a
        // los 5 minutos. Quien se arrepienta puede volver a escribir el mismo
        // teléfono y usar el código que ya le llegó.
        $this->assertDatabaseCount('otps', 1);
        $this->assertNotNull($vigente);

        $this->post(route('comentarios.codigo'), ['telefono' => self::TELEFONO]);
        $this->post(route('comentarios.validar'), ['codigo' => ArrayOtpSender::ultimoCodigoPara(self::TELEFONO)])
            ->assertCookie(self::COOKIE, self::TELEFONO);
    }

    public function test_con_la_recepcion_cerrada_responde_con_el_aviso(): void
    {
        $this->pedirCodigo();

        RecepcionDeComentarios::cerrar();

        $respuesta = $this->post(route('comentarios.cambiar-telefono'));

        $respuesta->assertRedirect(route('propuesta'));
        $respuesta->assertSessionHas('comentario.aviso');
    }

    public function test_no_toca_una_ventana_de_validacion_ya_abierta(): void
    {
        $this->pedirCodigo();
        $this->post(route('comentarios.validar'), [
            'codigo' => ArrayOtpSender::ultimoCodigoPara(self::TELEFONO),
        ]);

        // Con el teléfono ya validado la rama del código ni se dibuja, así que
        // no hay desde dónde cambiar de número.
        $respuesta = $this->withCookie(self::COOKIE, self::TELEFONO)->get(route('propuesta'));

        $respuesta->assertSee('Teléfono validado:', escape: false);
        $respuesta->assertDontSee('Usar otro número', escape: false);

        // Y si alguien llama la ruta de todas formas, no manda borrar la cookie:
        // la Ventana de validación es de la cookie, no de la sesión.
        $this->withCookie(self::COOKIE, self::TELEFONO)
            ->post(route('comentarios.cambiar-telefono'))
            ->assertCookieMissing(self::COOKIE);

        $this->withCookie(self::COOKIE, self::TELEFONO)
            ->post(route('comentarios.store'), [
                'nombre' => 'Laura Medina',
                'comentario' => '¿Cuándo se presenta el reporte del periodo?',
                'visibilidad' => 'publico',
            ])
            ->assertSessionHas('comentario.exito');
    }

    public function test_cambiar_de_numero_no_reinicia_el_limite_de_envio_por_ip(): void
    {
        // El tope por teléfono se cuenta por número, así que rotar de número lo
        // esquiva por diseño; el que contiene el paseo es el tope por IP.
        config()->set('services.otp.limite.ip.intentos', 2);

        $this->pedirCodigo(self::TELEFONO);
        $this->post(route('comentarios.cambiar-telefono'));

        $this->pedirCodigo(self::OTRO_TELEFONO);
        $this->post(route('comentarios.cambiar-telefono'));

        $tercero = $this->post(route('comentarios.codigo'), ['telefono' => '4425555555']);

        $tercero->assertSessionHasErrors('telefono');
        $this->assertNull(ArrayOtpSender::ultimoCodigoPara('4425555555'));
        $this->assertSame(0, Otp::where('telefono', '4425555555')->count());
    }
}
