<?php

declare(strict_types=1);

namespace Tests\Feature\Propuesta;

use App\Models\Comentario;
use App\Models\RecepcionDeComentarios;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo que la página muestra: el video, las preguntas frecuentes y la lista de
 * Comentarios públicos ya publicados.
 */
class PaginaPropuestaTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_video_configurado_muestra_el_marcador(): void
    {
        config(['services.asociacion_civil.video_url' => null]);

        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertOk();
        $respuesta->assertSee('El video con la explicación se publica aquí.');
        $respuesta->assertDontSee('<iframe', escape: false);
    }

    public function test_con_video_configurado_lo_incrusta(): void
    {
        config(['services.asociacion_civil.video_url' => 'https://www.youtube.com/embed/abc123']);

        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertOk();
        $respuesta->assertSee('https://www.youtube.com/embed/abc123', escape: false);
    }

    public function test_muestra_las_cinco_preguntas_frecuentes(): void
    {
        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertOk();
        $respuesta->assertSee('¿Qué es la Asociación Civil que se propone?', escape: false);
        $respuesta->assertSee('¿Por qué hacerlo ahora?', escape: false);
        $respuesta->assertSee('¿Quiénes formarían parte?', escape: false);
        $respuesta->assertSee('¿Cómo se constituye?', escape: false);
        $respuesta->assertSee('¿Tiene algún costo para los colonos?', escape: false);

        $this->assertCount(5, config('contenido.preguntas_frecuentes'));
    }

    /**
     * El texto vive en config, no dentro del controlador: la Mesa Directiva
     * puede corregir una respuesta sin que nadie toque una clase.
     */
    public function test_las_preguntas_frecuentes_salen_de_la_configuracion(): void
    {
        config(['contenido.preguntas_frecuentes' => [
            '¿Y si la Asamblea la rechaza?' => 'Todo sigue como está hoy.',
        ]]);

        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertSee('¿Y si la Asamblea la rechaza?', escape: false);
        $respuesta->assertSee('Todo sigue como está hoy.');
        $respuesta->assertDontSee('¿Tiene algún costo para los colonos?', escape: false);
    }

    /**
     * El argumento y su contraparte. Una propuesta que solo enumera ventajas se
     * lee como una venta, así que la página tiene que seguir diciendo también
     * qué no cambia — sobre todo que nadie pone su casa ni paga una cuota nueva.
     */
    public function test_argumenta_la_propuesta_y_dice_tambien_lo_que_no_cambia(): void
    {
        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertSee('Lo que cambia el día que la figura existe');
        $respuesta->assertSee('Lo que no cambia');
        $respuesta->assertSee('Tu casa sigue siendo tuya.');
        $respuesta->assertSee('No hay cuota nueva.');
    }

    /**
     * Lo primero que el formulario le pide a un colono es su celular, así que
     * ahí mismo tiene que decir para qué se usa y que no se publica. La
     * advertencia sobre público/privado va en el paso siguiente, con el
     * teléfono ya validado, y se cubre en DejarComentarioTest.
     */
    public function test_el_formulario_aclara_que_el_telefono_no_se_publica(): void
    {
        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertOk();
        $respuesta->assertSee('tu número no se publica en ninguna parte del sitio');
    }

    public function test_solo_lista_los_comentarios_publicos_ya_publicados(): void
    {
        Comentario::factory()->publicado()->create(['comentario' => 'Comentario publicado.']);
        Comentario::factory()->enCola()->create(['comentario' => 'Comentario en la cola.']);
        Comentario::factory()->descartado()->create(['comentario' => 'Comentario descartado.']);
        Comentario::factory()->privado()->create(['comentario' => 'Comentario para la Mesa Directiva.']);

        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertOk();
        $respuesta->assertSee('Comentario publicado.');
        $respuesta->assertDontSee('Comentario en la cola.');
        $respuesta->assertDontSee('Comentario descartado.');
        $respuesta->assertDontSee('Comentario para la Mesa Directiva.');
    }

    public function test_los_comentarios_publicos_van_del_mas_reciente_al_mas_viejo(): void
    {
        Comentario::factory()->publicado()->create([
            'comentario' => 'El más viejo.',
            'created_at' => now()->subDays(3),
        ]);
        Comentario::factory()->publicado()->create([
            'comentario' => 'El más reciente.',
            'created_at' => now(),
        ]);

        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertSeeInOrder(['El más reciente.', 'El más viejo.']);
    }

    public function test_sin_comentarios_publicos_explica_que_falta_publicarlos(): void
    {
        Comentario::factory()->enCola()->create();

        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertSee('Todavía no hay comentarios públicos.');
    }

    public function test_la_pagina_no_escribe_el_interruptor_de_la_recepcion(): void
    {
        $this->get(route('propuesta'))->assertOk();

        // Nace abierta sin necesidad de que exista el renglón: una página
        // pública no debería escribir en la base solo por leerse.
        $this->assertDatabaseCount('recepcion_de_comentarios', 0);
        $this->assertTrue(RecepcionDeComentarios::estaAbierta());
    }
}
