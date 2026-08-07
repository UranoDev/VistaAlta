<?php

declare(strict_types=1);

namespace Tests\Feature\Actividades;

use App\Models\Pendiente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * «Lo que sigue»: la mitad de la página de Actividades que enumera lo que falta.
 * Sale de la base, no del archivo Blade, y va después de la Bitácora — primero
 * lo hecho, y solo entonces lo que falta.
 */
class PaginaPendientesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_los_pendientes_con_su_detalle(): void
    {
        Pendiente::factory()->create([
            'titulo' => 'Constituir la Asociación Civil',
            'detalle' => 'De ahí sale la cuenta a nombre del fraccionamiento.',
        ]);

        $respuesta = $this->get(route('actividades'));

        $respuesta->assertOk();
        $respuesta->assertSee('Constituir la Asociación Civil');
        $respuesta->assertSee('De ahí sale la cuenta a nombre del fraccionamiento.');
        $respuesta->assertSee('1 pendiente');
    }

    /**
     * El orden lo pone la Mesa Directiva desde el panel, no la fecha de captura:
     * el primer renglón es el pendiente del que cuelgan los demás.
     */
    public function test_van_en_el_orden_que_les_dio_la_mesa_directiva(): void
    {
        Pendiente::factory()->enOrden(2)->create(['titulo' => 'El tercero de la lista']);
        Pendiente::factory()->enOrden(0)->create(['titulo' => 'El primero de la lista']);
        Pendiente::factory()->enOrden(1)->create(['titulo' => 'El segundo de la lista']);

        $this->get(route('actividades'))->assertSeeInOrder([
            'El primero de la lista',
            'El segundo de la lista',
            'El tercero de la lista',
        ]);
    }

    /**
     * Dos capturados sin reacomodar comparten `orden`. El desempate por `id`
     * evita que la lista se baraje sola entre una carga y la siguiente.
     */
    public function test_dos_con_el_mismo_orden_conservan_una_posicion_estable(): void
    {
        Pendiente::factory()->enOrden(0)->create(['titulo' => 'Capturado primero']);
        Pendiente::factory()->enOrden(0)->create(['titulo' => 'Capturado después']);

        $this->get(route('actividades'))->assertSeeInOrder(['Capturado primero', 'Capturado después']);
        $this->get(route('actividades'))->assertSeeInOrder(['Capturado primero', 'Capturado después']);
    }

    public function test_sin_pendientes_la_pagina_lo_dice_en_vez_de_dejar_el_encabezado_colgando(): void
    {
        $respuesta = $this->get(route('actividades'));

        $respuesta->assertOk();
        $respuesta->assertSee('No hay pendientes publicados en este momento.');
    }

    /**
     * La ausencia de fecha comprometida es la decisión, no un detalle de
     * captura: si la columna aparece, en el panel se llena, y la página empieza
     * a prometer plazos que dependen de un tercero.
     *
     * `cumplido_en` no es esa fecha y no la contradice: dice cuándo algo **se
     * cumplió**, hacia atrás y como hecho, no cuándo se promete cumplirlo. La
     * lista sigue sin comprometer plazos.
     */
    public function test_el_modelo_no_tiene_fecha_comprometida(): void
    {
        $columnas = (new Pendiente)->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing((new Pendiente)->getTable());

        // Sin orden: `after()` lo respeta MySQL y lo ignora SQLite, así que
        // comparar la secuencia haría que esta prueba dijera cosas distintas en
        // local y en el servidor.
        sort($columnas);

        $esperadas = ['id', 'titulo', 'detalle', 'orden', 'cumplido_en', 'created_at', 'updated_at'];
        sort($esperadas);

        $this->assertSame($esperadas, $columnas);
    }

    public function test_la_pagina_no_pide_autenticacion(): void
    {
        Pendiente::factory()->create();

        $this->get(route('actividades'))->assertOk();
    }
}
