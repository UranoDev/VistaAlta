<?php

declare(strict_types=1);

namespace Tests\Feature\Actividades;

use App\Models\Actividad;
use App\Models\Pendiente;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las marcas de novedad en las dos mitades de `/actividades`.
 *
 * Lo que hay que proteger no es que la marca aparezca —eso se ve— sino que se
 * **apague**: una página donde todo está marcado como nuevo no marca nada, y es
 * a lo que tiende sola una marca que no caduca.
 */
class MarcaDeNovedadTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_lo_capturado_hace_poco_se_marca_en_la_bitacora(): void
    {
        Actividad::factory()->enFecha('2026-05-14')->create([
            'descripcion' => 'Recién capturada.',
        ]);

        $this->get(route('actividades'))->assertSee('Se agregó', escape: false);
    }

    public function test_lo_capturado_hace_mucho_ya_no_se_marca(): void
    {
        $vieja = Actividad::factory()->enFecha('2026-05-14')->create([
            'descripcion' => 'Capturada hace meses.',
        ]);

        $vieja->forceFill(['created_at' => CarbonImmutable::now()->subDays(40)])->saveQuietly();

        $this->get(route('actividades'))
            ->assertSee('Capturada hace meses.')
            ->assertDontSee('Se agregó', escape: false);
    }

    /**
     * El borde de la ventana, que es donde una comparación mal puesta se nota:
     * dentro de la ventana todavía se marca y un día después ya no.
     */
    public function test_la_ventana_se_respeta_en_su_borde(): void
    {
        config(['contenido.novedades.dias' => 14]);

        $actividad = Actividad::factory()->enFecha('2026-05-14')->create(['descripcion' => 'En el borde.']);

        $actividad->forceFill(['created_at' => CarbonImmutable::now()->subDays(14)->addMinute()])->saveQuietly();
        $this->assertTrue($actividad->fresh()->esNuevo());

        $actividad->forceFill(['created_at' => CarbonImmutable::now()->subDays(15)])->saveQuietly();
        $this->assertFalse($actividad->fresh()->esNuevo());
    }

    public function test_un_pendiente_recien_capturado_se_marca(): void
    {
        Pendiente::factory()->create(['titulo' => 'Pendiente recién nacido']);

        $this->get(route('actividades'))->assertSee('Se agregó', escape: false);
    }

    public function test_un_pendiente_viejo_no_se_marca(): void
    {
        $pendiente = Pendiente::factory()->create(['titulo' => 'Pendiente de siempre']);

        $pendiente->forceFill(['created_at' => CarbonImmutable::now()->subDays(40)])->saveQuietly();

        $this->get(route('actividades'))
            ->assertSee('Pendiente de siempre')
            ->assertDontSee('Se agregó', escape: false);
    }

    /**
     * Se mide con `created_at` y no con la fecha de la Actividad. Ponerse al
     * corriente con la captura —algo que pasó en mayo y se registra hoy— es
     * novedad para quien lee, aunque su fecha sea vieja.
     */
    public function test_una_actividad_con_fecha_vieja_capturada_hoy_si_es_novedad(): void
    {
        $actividad = Actividad::factory()->enFecha('2026-01-03')->create(['descripcion' => 'De enero, capturada hoy.']);

        $this->assertTrue($actividad->esNuevo());
    }

    /**
     * Con la ventana en cero la marca se apaga entera, sin tener que tocar las
     * vistas.
     */
    public function test_la_marca_se_puede_apagar_desde_la_configuracion(): void
    {
        config(['contenido.novedades.dias' => 0]);

        Actividad::factory()->enFecha('2026-05-14')->create(['descripcion' => 'Recién capturada.']);

        $this->get(route('actividades'))->assertDontSee('Se agregó', escape: false);
    }
}
