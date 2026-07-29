<?php

declare(strict_types=1);

namespace Tests\Feature\Comentarios;

use App\Enums\EstadoModeracion;
use App\Enums\Visibilidad;
use App\Exceptions\ComentarioPrivadoNoSeModera;
use App\Exceptions\VisibilidadEsDefinitiva;
use App\Models\Comentario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComentarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function datos(): array
    {
        return [
            'telefono' => '4421234567',
            'nombre' => 'Ana Colono',
            'comentario' => '¿Cuánto costaría formalizar el fraccionamiento?',
            'url' => '/',
        ];
    }

    public function test_un_comentario_publico_nace_en_la_cola_de_moderacion(): void
    {
        $comentario = Comentario::crearPublico($this->datos());

        $this->assertSame(Visibilidad::Publico, $comentario->visibilidad);
        $this->assertSame(EstadoModeracion::EnCola, $comentario->estado);
        $this->assertTrue($comentario->esPublico());
    }

    public function test_un_comentario_privado_nace_fuera_de_la_cola_y_sin_estado(): void
    {
        $comentario = Comentario::crearPrivado($this->datos());

        $this->assertSame(Visibilidad::Privado, $comentario->visibilidad);
        $this->assertNull($comentario->estado);
        $this->assertTrue($comentario->esPrivado());
    }

    public function test_la_visibilidad_no_se_puede_asignar_en_masa(): void
    {
        $comentario = new Comentario($this->datos() + ['visibilidad' => Visibilidad::Publico]);

        $this->assertNull($comentario->visibilidad);
    }

    public function test_la_visibilidad_es_definitiva_una_vez_escrito_el_comentario(): void
    {
        $comentario = Comentario::crearPrivado($this->datos());

        $comentario->visibilidad = Visibilidad::Publico;

        $this->expectException(VisibilidadEsDefinitiva::class);

        $comentario->save();
    }

    public function test_la_visibilidad_sigue_siendo_privada_despues_del_intento(): void
    {
        $comentario = Comentario::crearPrivado($this->datos());
        $comentario->visibilidad = Visibilidad::Publico;

        try {
            $comentario->save();
        } catch (VisibilidadEsDefinitiva) {
            // Lo que importa es lo que quedó guardado.
        }

        $this->assertSame(Visibilidad::Privado, $comentario->fresh()->visibilidad);
    }

    public function test_publicar_saca_al_comentario_de_la_cola(): void
    {
        $comentario = Comentario::crearPublico($this->datos());

        $comentario->publicar();

        $this->assertSame(EstadoModeracion::Publicado, $comentario->fresh()->estado);
        $this->assertSame(0, Comentario::enCola()->count());
    }

    public function test_descartar_saca_al_comentario_de_la_cola_sin_publicarlo(): void
    {
        $comentario = Comentario::crearPublico($this->datos());

        $comentario->descartar();

        $this->assertSame(EstadoModeracion::Descartado, $comentario->fresh()->estado);
        $this->assertSame(0, Comentario::enCola()->count());
        $this->assertSame(0, Comentario::publicados()->count());
    }

    public function test_un_comentario_privado_no_se_puede_publicar(): void
    {
        $comentario = Comentario::crearPrivado($this->datos());

        $this->expectException(ComentarioPrivadoNoSeModera::class);

        $comentario->publicar();
    }

    public function test_un_comentario_privado_tampoco_se_descarta(): void
    {
        $comentario = Comentario::crearPrivado($this->datos());

        $this->expectException(ComentarioPrivadoNoSeModera::class);

        $comentario->descartar();
    }

    public function test_la_lista_publica_solo_trae_publicos_ya_publicados(): void
    {
        $publicado = Comentario::factory()->publicado()->create();
        Comentario::factory()->enCola()->create();
        Comentario::factory()->descartado()->create();
        Comentario::factory()->privado()->create();

        $lista = Comentario::publicados()->get();

        $this->assertCount(1, $lista);
        $this->assertTrue($lista->first()->is($publicado));
    }

    public function test_la_lista_publica_va_de_lo_mas_reciente_a_lo_mas_viejo(): void
    {
        $viejo = Comentario::factory()->publicado()->create(['created_at' => now()->subDays(2)]);
        $reciente = Comentario::factory()->publicado()->create(['created_at' => now()]);
        $intermedio = Comentario::factory()->publicado()->create(['created_at' => now()->subDay()]);

        $lista = Comentario::publicados()->get();

        $this->assertSame(
            [$reciente->id, $intermedio->id, $viejo->id],
            $lista->pluck('id')->all(),
        );
    }

    public function test_la_cola_de_moderacion_solo_trae_publicos_sin_publicar(): void
    {
        $enCola = Comentario::factory()->enCola()->create();
        Comentario::factory()->publicado()->create();
        Comentario::factory()->descartado()->create();
        Comentario::factory()->privado()->create();

        $cola = Comentario::enCola()->get();

        $this->assertCount(1, $cola);
        $this->assertTrue($cola->first()->is($enCola));
    }

    public function test_la_cola_de_moderacion_va_de_lo_mas_viejo_a_lo_mas_reciente(): void
    {
        $reciente = Comentario::factory()->enCola()->create(['created_at' => now()]);
        $viejo = Comentario::factory()->enCola()->create(['created_at' => now()->subDays(2)]);

        $this->assertSame(
            [$viejo->id, $reciente->id],
            Comentario::enCola()->get()->pluck('id')->all(),
        );
    }

    public function test_los_privados_tienen_su_propia_lista_y_no_se_mezclan(): void
    {
        $privado = Comentario::factory()->privado()->create();
        Comentario::factory()->publicado()->create();
        Comentario::factory()->enCola()->create();

        $privados = Comentario::privados()->get();

        $this->assertCount(1, $privados);
        $this->assertTrue($privados->first()->is($privado));
    }

    public function test_un_privado_marcado_como_publicado_a_la_fuerza_sigue_fuera_de_la_lista_publica(): void
    {
        Comentario::factory()->privado()->create(['estado' => EstadoModeracion::Publicado]);

        $this->assertSame(0, Comentario::publicados()->count());
        $this->assertSame(0, Comentario::enCola()->count());
    }
}
