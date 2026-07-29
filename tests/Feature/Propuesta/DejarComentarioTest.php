<?php

declare(strict_types=1);

namespace Tests\Feature\Propuesta;

use App\Enums\EstadoModeracion;
use App\Enums\Visibilidad;
use App\Models\Comentario;
use App\Support\Otp\ArrayOtpSender;
use App\Support\VentanaDeValidacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El camino completo: OTP → Ventana de validación → Comentario.
 *
 * Corre con OTP_CHANNEL=array (phpunit.xml), así que no sale ningún SMS y el
 * código se lee del sender de pruebas.
 */
class DejarComentarioTest extends TestCase
{
    use RefreshDatabase;

    private const TELEFONO = '4421234567';

    private const COOKIE = 'telefono_validado';

    protected function setUp(): void
    {
        parent::setUp();

        ArrayOtpSender::$enviados = [];
    }

    /**
     * Manda el código y lo confirma, como lo haría alguien desde la página.
     */
    private function validarTelefono(string $telefono = self::TELEFONO): void
    {
        $this->post(route('comentarios.codigo'), ['telefono' => $telefono]);

        $this->post(route('comentarios.validar'), [
            'codigo' => ArrayOtpSender::ultimoCodigoPara($telefono),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function comentario(string $visibilidad): array
    {
        return [
            'nombre' => 'Laura Medina',
            'comentario' => '¿Los gastos notariales ya están en el reporte del periodo?',
            'visibilidad' => $visibilidad,
        ];
    }

    public function test_el_flujo_completo_deja_un_comentario_publico_en_la_cola(): void
    {
        $this->validarTelefono();

        $respuesta = $this->withCookie(self::COOKIE, self::TELEFONO)
            ->post(route('comentarios.store'), $this->comentario('publico'));

        $respuesta->assertRedirect(route('propuesta').'#comentarios');
        $respuesta->assertSessionHas('comentario.exito');

        $comentario = Comentario::sole();

        $this->assertSame(self::TELEFONO, $comentario->telefono);
        $this->assertSame('Laura Medina', $comentario->nombre);
        $this->assertSame(Visibilidad::Publico, $comentario->visibilidad);
        $this->assertSame(EstadoModeracion::EnCola, $comentario->estado);
    }

    public function test_un_comentario_publico_recien_enviado_no_aparece_en_la_pagina(): void
    {
        $this->validarTelefono();

        $this->withCookie(self::COOKIE, self::TELEFONO)
            ->post(route('comentarios.store'), $this->comentario('publico'));

        $this->get(route('propuesta'))
            ->assertDontSee('¿Los gastos notariales ya están en el reporte del periodo?', escape: false);

        // Solo después de que la Mesa Directiva lo publique.
        Comentario::sole()->publicar();

        $this->get(route('propuesta'))
            ->assertSee('¿Los gastos notariales ya están en el reporte del periodo?', escape: false);
    }

    public function test_un_comentario_privado_nace_fuera_de_la_cola(): void
    {
        $this->validarTelefono();

        $this->withCookie(self::COOKIE, self::TELEFONO)
            ->post(route('comentarios.store'), $this->comentario('privado'))
            ->assertSessionHas('comentario.exito');

        $comentario = Comentario::sole();

        $this->assertSame(Visibilidad::Privado, $comentario->visibilidad);
        $this->assertNull($comentario->estado);
    }

    public function test_el_formulario_advierte_que_la_eleccion_de_visibilidad_es_definitiva(): void
    {
        $respuesta = $this->withCookie(self::COOKIE, self::TELEFONO)->get(route('propuesta'));

        $respuesta->assertSee('¿Quién puede leer tu comentario?', escape: false);
        $respuesta->assertSee('esta decisión no se puede deshacer', escape: false);
        $respuesta->assertSee('nadie la cambia');

        // Público es público: se dice con esas palabras, y se dice que queda a
        // la vista de cualquiera y no solo de la Asamblea.
        $respuesta->assertSee('Público quiere decir público', escape: false);
        $respuesta->assertSee('Lo lee únicamente la Mesa Directiva.', escape: false);

        // Ninguna de las dos viene preseleccionada: se elige a propósito.
        $respuesta->assertDontSee('checked', escape: false);
    }

    public function test_la_visibilidad_es_obligatoria_y_no_admite_otro_valor(): void
    {
        $this->validarTelefono();

        $sinVisibilidad = $this->comentario('publico');
        unset($sinVisibilidad['visibilidad']);

        $this->withCookie(self::COOKIE, self::TELEFONO)
            ->post(route('comentarios.store'), $sinVisibilidad)
            ->assertSessionHasErrors('visibilidad');

        $this->withCookie(self::COOKIE, self::TELEFONO)
            ->post(route('comentarios.store'), $this->comentario('anonimo'))
            ->assertSessionHasErrors('visibilidad');

        $this->assertDatabaseCount('comentarios', 0);
    }

    public function test_sin_telefono_validado_no_se_puede_comentar(): void
    {
        $respuesta = $this->post(route('comentarios.store'), $this->comentario('publico'));

        $respuesta->assertSessionHasErrors('telefono');
        $this->assertDatabaseCount('comentarios', 0);
    }

    public function test_una_cookie_inventada_no_habilita_a_comentar(): void
    {
        // La cookie de la Ventana de validación va cifrada y firmada con la
        // APP_KEY: escribir el teléfono a mano no alcanza para saltarse el OTP.
        $respuesta = $this->withUnencryptedCookie(self::COOKIE, self::TELEFONO)
            ->post(route('comentarios.store'), $this->comentario('publico'));

        $respuesta->assertSessionHasErrors('telefono');
        $this->assertDatabaseCount('comentarios', 0);
    }

    public function test_un_codigo_incorrecto_no_abre_la_ventana_de_validacion(): void
    {
        $this->post(route('comentarios.codigo'), ['telefono' => self::TELEFONO]);

        $respuesta = $this->post(route('comentarios.validar'), ['codigo' => '000000']);

        $respuesta->assertSessionHasErrors('codigo');
        $respuesta->assertCookieMissing(self::COOKIE);
    }

    public function test_el_codigo_correcto_abre_la_ventana_por_treinta_minutos(): void
    {
        $this->post(route('comentarios.codigo'), ['telefono' => self::TELEFONO]);

        $respuesta = $this->post(route('comentarios.validar'), [
            'codigo' => ArrayOtpSender::ultimoCodigoPara(self::TELEFONO),
        ]);

        $respuesta->assertCookie(self::COOKIE, self::TELEFONO);

        $cookie = collect($respuesta->headers->getCookies())
            ->firstOrFail(fn ($cookie) => $cookie->getName() === self::COOKIE);

        $this->assertSame(30, VentanaDeValidacion::MINUTOS);
        $this->assertEqualsWithDelta(
            now()->addMinutes(VentanaDeValidacion::MINUTOS)->getTimestamp(),
            $cookie->getExpiresTime(),
            5,
        );
    }

    public function test_el_telefono_se_normaliza_a_puros_digitos(): void
    {
        $this->post(route('comentarios.codigo'), ['telefono' => '(442) 123-4567']);

        $this->assertNotNull(ArrayOtpSender::ultimoCodigoPara(self::TELEFONO));
        $this->assertDatabaseHas('otps', ['telefono' => self::TELEFONO]);
    }

    public function test_un_telefono_mal_escrito_no_dispara_ningun_sms(): void
    {
        $respuesta = $this->post(route('comentarios.codigo'), ['telefono' => '442']);

        $respuesta->assertSessionHasErrors('telefono');
        $this->assertSame([], ArrayOtpSender::$enviados);
    }
}
