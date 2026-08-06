<?php

declare(strict_types=1);

namespace Tests\Feature\Actividades;

use App\Models\Actividad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La página pública de Actividades: la lista con sus fechas, más reciente
 * primero, leída sobre la propia página.
 */
class PaginaActividadesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_las_actividades_con_su_fecha(): void
    {
        Actividad::factory()->enFecha('2026-05-14')->create([
            'descripcion' => 'Se repararon las luminarias de la calle principal.',
        ]);

        $respuesta = $this->get(route('actividades'));

        $respuesta->assertOk();
        $respuesta->assertSee('Se repararon las luminarias de la calle principal.');
        $respuesta->assertSee('2026-05-14');
    }

    /**
     * La Bitácora enlaza hacia adentro del sitio: la entrada que cuenta cómo se
     * cerró el turno de vigilancia manda a `/vigilancia` con una frase, no con
     * una URL pegada a media línea.
     *
     * Se prueba sobre la página y no solo sobre `TextoConLigas` porque el helper
     * puede seguir intacto mientras alguien devuelve la vista al `{{ }}` de
     * antes, y entonces el lector vería los corchetes en crudo.
     */
    public function test_la_descripcion_puede_enlazar_a_otra_pagina_del_sitio(): void
    {
        Actividad::factory()->enFecha('2026-08-02')->create([
            'descripcion' => 'Se consulta en [la página de Vigilancia](/vigilancia).',
        ]);

        $this->get(route('actividades'))
            ->assertSee('<a href="/vigilancia"', escape: false)
            ->assertSee('la página de Vigilancia</a>', escape: false)
            ->assertDontSee('[la página de Vigilancia]', escape: false);
    }

    /**
     * Y lo que se captura sigue sin poder salir como HTML.
     */
    public function test_la_descripcion_no_puede_meter_html(): void
    {
        Actividad::factory()->enFecha('2026-08-02')->create([
            'descripcion' => 'Ojo <script>alert(1)</script>',
        ]);

        $this->get(route('actividades'))
            ->assertDontSee('<script>alert(1)</script>', escape: false)
            ->assertSee('&lt;script&gt;', escape: false);
    }

    public function test_van_ordenadas_por_fecha_descendente(): void
    {
        Actividad::factory()->enFecha('2026-04-01')->create(['descripcion' => 'La más vieja.']);
        Actividad::factory()->enFecha('2026-06-20')->create(['descripcion' => 'La más nueva.']);
        Actividad::factory()->enFecha('2026-05-10')->create(['descripcion' => 'La de en medio.']);

        $respuesta = $this->get(route('actividades'));

        $respuesta->assertSeeInOrder(['La más nueva.', 'La de en medio.', 'La más vieja.']);
    }

    /**
     * El orden no puede depender de en qué orden se capturaron: dos Actividades
     * del mismo día tienen que salir igual en cada carga.
     */
    public function test_dos_del_mismo_dia_conservan_un_orden_estable(): void
    {
        Actividad::factory()->enFecha('2026-06-01')->create(['descripcion' => 'Capturada primero.']);
        Actividad::factory()->enFecha('2026-06-01')->create(['descripcion' => 'Capturada después.']);

        $this->get(route('actividades'))
            ->assertSeeInOrder(['Capturada después.', 'Capturada primero.']);

        $this->get(route('actividades'))
            ->assertSeeInOrder(['Capturada después.', 'Capturada primero.']);
    }

    /**
     * La Bitácora se lee por día: la fecha encabeza lo que pasó ese día en vez
     * de repetirse renglón por renglón. El contador del encabezado, en cambio,
     * sigue contando Actividades — dos el mismo día son dos, no una.
     */
    public function test_dos_del_mismo_dia_muestran_la_fecha_una_sola_vez(): void
    {
        Actividad::factory()->enFecha('2026-06-01')->create(['descripcion' => 'Se aplica mata hierba en los jardines.']);
        Actividad::factory()->enFecha('2026-06-01')->create(['descripcion' => 'Poda exterior, pasto y árboles.']);

        $respuesta = $this->get(route('actividades'));

        $respuesta->assertOk();
        $respuesta->assertSee('Se aplica mata hierba en los jardines.');
        $respuesta->assertSee('Poda exterior, pasto y árboles.');
        $respuesta->assertSee('2 actividades');

        $this->assertSame(
            1,
            substr_count($respuesta->getContent(), 'datetime="2026-06-01"'),
            'La fecha de un día tiene que aparecer una sola vez, aunque tenga varias Actividades.',
        );
    }

    public function test_dos_de_dias_distintos_muestran_cada_una_su_fecha(): void
    {
        Actividad::factory()->enFecha('2026-06-01')->create(['descripcion' => 'La del primero de junio.']);
        Actividad::factory()->enFecha('2026-05-14')->create(['descripcion' => 'La del catorce de mayo.']);

        $respuesta = $this->get(route('actividades'));

        $respuesta->assertOk();
        $respuesta->assertSeeInOrder(['La del primero de junio.', 'La del catorce de mayo.']);

        $contenido = $respuesta->getContent();
        $this->assertSame(1, substr_count($contenido, 'datetime="2026-06-01"'));
        $this->assertSame(1, substr_count($contenido, 'datetime="2026-05-14"'));
    }

    public function test_sin_actividades_la_pagina_lo_dice_en_vez_de_quedar_en_blanco(): void
    {
        $respuesta = $this->get(route('actividades'));

        $respuesta->assertOk();
        $respuesta->assertSee('Todavía no hay actividades publicadas', escape: false);
    }

    public function test_la_pagina_no_pide_autenticacion(): void
    {
        Actividad::factory()->create();

        $this->get(route('actividades'))->assertOk();
    }

    /**
     * Ni el modelo ni la tabla llevan costo o archivo. Que no se cuele "para
     * después": el dinero se rinde solo en el Reporte financiero y en el sitio
     * no se cargan documentos.
     */
    public function test_el_modelo_no_tiene_costo_ni_archivo(): void
    {
        $columnas = (new Actividad)->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing((new Actividad)->getTable());

        $this->assertSame(['id', 'fecha', 'descripcion', 'created_at', 'updated_at'], $columnas);
    }
}
