<?php

declare(strict_types=1);

namespace Tests\Feature\Contenido;

use App\Models\ReporteFinanciero;
use Database\Seeders\ContenidoInicialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La siembra del contenido con el que el sitio sale al aire.
 *
 * Lo que se protege aquí es que el archivo de contenido se pueda pegar dos
 * veces sin ensuciar la base, y que un renglón a medias no llegue a la página
 * pública.
 */
class ContenidoInicialTest extends TestCase
{
    use RefreshDatabase;

    public function test_siembra_las_actividades_del_archivo(): void
    {
        $this->sembrar([
            'actividades' => [
                ['fecha' => '2026-05-14', 'descripcion' => 'Se repararon las luminarias.'],
                ['fecha' => '2026-06-02', 'descripcion' => 'Se renovó el contrato de vigilancia.'],
            ],
        ]);

        $this->assertDatabaseCount('actividades', 2);
        $this->assertDatabaseHas('actividades', ['descripcion' => 'Se repararon las luminarias.']);

        $this->get(route('actividades'))->assertSee('Se renovó el contrato de vigilancia.');
    }

    /**
     * El archivo se siembra en un despliegue y se vuelve a sembrar en el
     * siguiente. Si eso duplicara la bitácora, la Asamblea leería dos veces
     * cada cosa que se hizo.
     */
    public function test_sembrar_dos_veces_no_duplica_actividades(): void
    {
        $contenido = [
            'actividades' => [
                ['fecha' => '2026-05-14', 'descripcion' => 'Se repararon las luminarias.'],
            ],
        ];

        $this->sembrar($contenido);
        $this->sembrar($contenido);

        $this->assertDatabaseCount('actividades', 1);
    }

    public function test_una_actividad_sin_fecha_o_sin_texto_no_se_siembra(): void
    {
        $this->sembrar([
            'actividades' => [
                ['fecha' => '2026-05-14', 'descripcion' => ''],
                ['fecha' => '', 'descripcion' => 'Sin fecha.'],
                ['fecha' => '2026-05-20', 'descripcion' => 'La única completa.'],
            ],
        ]);

        $this->assertDatabaseCount('actividades', 1);
        $this->assertDatabaseHas('actividades', ['descripcion' => 'La única completa.']);
    }

    public function test_siembra_los_pendientes_en_el_orden_del_archivo(): void
    {
        $this->sembrar([
            'pendientes' => [
                ['titulo' => 'Constituir la Asociación Civil', 'detalle' => 'De ahí sale la cuenta a nombre del fraccionamiento.'],
                ['titulo' => 'Alumbrado público al 100%', 'detalle' => 'Reponer lo que está apagado y mantenerlo así.'],
            ],
        ]);

        $this->assertDatabaseCount('pendientes', 2);
        $this->assertDatabaseHas('pendientes', ['titulo' => 'Constituir la Asociación Civil', 'orden' => 0]);
        $this->assertDatabaseHas('pendientes', ['titulo' => 'Alumbrado público al 100%', 'orden' => 1]);

        $this->get(route('actividades'))->assertSeeInOrder([
            'Constituir la Asociación Civil',
            'Alumbrado público al 100%',
        ]);
    }

    public function test_sembrar_dos_veces_no_duplica_pendientes(): void
    {
        $contenido = [
            'pendientes' => [
                ['titulo' => 'Coladera repuesta', 'detalle' => 'Le corresponde a la Fraccionadora.'],
            ],
        ];

        $this->sembrar($contenido);
        $this->sembrar($contenido);

        $this->assertDatabaseCount('pendientes', 1);
    }

    public function test_un_pendiente_sin_titulo_o_sin_detalle_no_se_siembra(): void
    {
        $this->sembrar([
            'pendientes' => [
                ['titulo' => 'Sin detalle', 'detalle' => ''],
                ['titulo' => '', 'detalle' => 'Sin título.'],
                ['titulo' => 'El único completo', 'detalle' => 'Con las dos partes.'],
            ],
        ]);

        $this->assertDatabaseCount('pendientes', 1);
        $this->assertDatabaseHas('pendientes', ['titulo' => 'El único completo']);
    }

    public function test_siembra_el_reporte_financiero_con_su_resumen_y_su_hoja(): void
    {
        $this->sembrar([
            'reporte_financiero' => [
                'mes' => '2026-05',
                'hoja_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
                'cifras' => [
                    ['concepto' => 'Cuotas recibidas', 'monto' => 48250.5],
                    ['concepto' => 'Saldo final', 'monto' => 12100, 'destacada' => true],
                ],
            ],
        ]);

        $reporte = ReporteFinanciero::actual();

        $this->assertSame('2026-05', $reporte->mesEnUrl());
        $this->assertSame('Mayo de 2026', $reporte->periodo);
        $this->assertSame('https://docs.google.com/spreadsheets/d/abc123/edit', $reporte->hoja_url);
        $this->assertCount(2, $reporte->resumen());
        $this->assertTrue($reporte->resumen()->last()->destacada);

        $this->get(route('reporte-financiero'))
            ->assertSee('Cuotas recibidas')
            ->assertSee('$48,250.50');
    }

    /**
     * Un Reporte se identifica por el mes que cubre: sembrar el mismo mes otra
     * vez lo corrige, nunca deja dos junios conviviendo sin que se sepa cuál
     * vale.
     */
    public function test_sembrar_dos_veces_el_mismo_mes_lo_corrige_en_vez_de_duplicarlo(): void
    {
        $this->sembrar(['reporte_financiero' => [
            'mes' => '2026-06',
            'cifras' => [['concepto' => 'Cifra con un error', 'monto' => 1]],
        ]]);
        $this->sembrar(['reporte_financiero' => [
            'mes' => '2026-06',
            'cifras' => [['concepto' => 'Cifra corregida', 'monto' => 2]],
        ]]);

        $this->assertDatabaseCount('reporte_financiero', 1);
        $this->assertSame('Cifra corregida', ReporteFinanciero::actual()->resumen()->first()->concepto);
    }

    /**
     * El contrato inverso, y el que hace posible el histórico (docs/adr/0005):
     * sembrar otro mes lo agrega en vez de pisar al anterior.
     */
    public function test_sembrar_otro_mes_lo_agrega_al_historico(): void
    {
        $this->sembrar(['reporte_financiero' => [
            'mes' => '2026-06',
            'cifras' => [['concepto' => 'Cuotas de junio', 'monto' => 1]],
        ]]);
        $this->sembrar(['reporte_financiero' => [
            'mes' => '2026-07',
            'cifras' => [['concepto' => 'Cuotas de julio', 'monto' => 2]],
        ]]);

        $this->assertDatabaseCount('reporte_financiero', 2);
        $this->assertSame('2026-07', ReporteFinanciero::actual()->mesEnUrl());

        $this->get(route('reporte-financiero.mes', ['mes' => '2026-06']))
            ->assertOk()
            ->assertSee('Cuotas de junio');
    }

    /**
     * Sin mes no hay dónde publicarlo: de él salen la dirección del reporte y
     * su lugar en el histórico. Se salta y se avisa, igual que una Actividad
     * sin fecha.
     */
    public function test_un_reporte_sin_mes_o_con_un_mes_que_no_se_entiende_no_se_siembra(): void
    {
        $this->sembrar(['reporte_financiero' => [
            'cifras' => [['concepto' => 'Sin mes', 'monto' => 1]],
        ]]);

        $this->sembrar(['reporte_financiero' => [
            'mes' => 'Junio',
            'cifras' => [['concepto' => 'Con un mes que no se entiende', 'monto' => 1]],
        ]]);

        $this->assertDatabaseCount('reporte_financiero', 0);
    }

    public function test_una_cifra_sin_concepto_o_sin_monto_no_se_siembra(): void
    {
        $this->sembrar([
            'reporte_financiero' => [
                'mes' => '2026-06',
                'cifras' => [
                    ['concepto' => 'Sin monto'],
                    ['concepto' => '', 'monto' => 100],
                    ['concepto' => 'La única completa', 'monto' => 100],
                ],
            ],
        ]);

        $this->assertCount(1, ReporteFinanciero::actual()->resumen());
    }

    /**
     * Mientras la Mesa Directiva no mande el material, sembrar no debe dejar
     * al sitio publicando un reporte en blanco ni una bitácora vacía: la
     * página ya sabe decir que ese contenido todavía no se publica.
     */
    public function test_con_el_archivo_vacio_no_escribe_nada(): void
    {
        $this->sembrar([
            'actividades' => [],
            'pendientes' => [],
            'reporte_financiero' => ['mes' => null, 'hoja_url' => null, 'cifras' => []],
        ]);

        $this->assertDatabaseCount('actividades', 0);
        $this->assertDatabaseCount('pendientes', 0);
        $this->assertDatabaseCount('reporte_financiero', 0);
    }

    /**
     * El archivo que se despliega tiene que seguir siendo cargable y con la
     * forma que el seeder espera, por más que lo edite alguien que no
     * escribe PHP a diario.
     */
    public function test_el_archivo_de_contenido_tiene_la_forma_esperada(): void
    {
        $contenido = require database_path('seeders/contenido/contenido-inicial.php');

        $this->assertIsArray($contenido['actividades']);
        $this->assertIsArray($contenido['pendientes']);
        $this->assertIsArray($contenido['reporte_financiero']['cifras']);
        $this->assertArrayHasKey('mes', $contenido['reporte_financiero']);
        $this->assertArrayHasKey('hoja_url', $contenido['reporte_financiero']);

        // Y tiene que poder sembrarse tal como está en el repo.
        (new ContenidoInicialSeeder)->run();
    }

    /**
     * @param  array<string, mixed>  $contenido
     */
    private function sembrar(array $contenido): void
    {
        $seeder = new ContenidoInicialSeeder;
        $seeder->contenido = $contenido;
        $seeder->run();
    }
}
