<?php

declare(strict_types=1);

namespace Tests\Feature\Propuesta;

use App\Models\Comentario;
use App\Models\RecepcionDeComentarios;
use App\Support\Otp\ArrayOtpSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El interruptor decide *si se puede escribir*. Nunca *qué se publica* — eso es
 * de la Cola de moderación.
 */
class RecepcionDeComentariosTest extends TestCase
{
    use RefreshDatabase;

    private const TELEFONO = '4421234567';

    private const COOKIE = 'telefono_validado';

    protected function setUp(): void
    {
        parent::setUp();

        ArrayOtpSender::$enviados = [];
    }

    public function test_nace_abierta(): void
    {
        $this->assertTrue(RecepcionDeComentarios::estaAbierta());

        $this->get(route('propuesta'))->assertSee('Enviarme el código');
    }

    public function test_cerrada_retira_el_formulario_de_la_pagina(): void
    {
        RecepcionDeComentarios::cerrar();

        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertOk();
        $respuesta->assertDontSee('Enviarme el código');
        $respuesta->assertSee('cerró la recepción de comentarios', escape: false);
    }

    public function test_cerrada_no_despublica_nada(): void
    {
        Comentario::factory()->publicado()->create(['comentario' => 'Comentario ya publicado.']);

        RecepcionDeComentarios::cerrar();

        $this->get(route('propuesta'))->assertSee('Comentario ya publicado.');
    }

    public function test_cerrada_rechaza_el_envio_de_un_comentario(): void
    {
        RecepcionDeComentarios::cerrar();

        $respuesta = $this->withCookie(self::COOKIE, self::TELEFONO)
            ->post(route('comentarios.store'), [
                'nombre' => 'Laura Medina',
                'comentario' => 'Una pregunta que llega tarde.',
                'visibilidad' => 'publico',
            ]);

        $respuesta->assertSessionHas('comentario.aviso');
        $this->assertDatabaseCount('comentarios', 0);
    }

    public function test_cerrada_no_manda_codigos_ni_valida_telefonos(): void
    {
        RecepcionDeComentarios::cerrar();

        $this->post(route('comentarios.codigo'), ['telefono' => self::TELEFONO])
            ->assertSessionHas('comentario.aviso');

        $this->assertSame([], ArrayOtpSender::$enviados);
        $this->assertDatabaseCount('otps', 0);

        $this->post(route('comentarios.validar'), ['codigo' => '123456'])
            ->assertCookieMissing(self::COOKIE);
    }

    public function test_se_puede_volver_a_abrir(): void
    {
        RecepcionDeComentarios::cerrar();
        RecepcionDeComentarios::abrir();

        $this->assertTrue(RecepcionDeComentarios::estaAbierta());
        $this->assertDatabaseCount('recepcion_de_comentarios', 1);

        $this->get(route('propuesta'))->assertSee('Enviarme el código');
    }
}
