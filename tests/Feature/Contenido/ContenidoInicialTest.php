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

    public function test_siembra_el_reporte_financiero_con_su_resumen_y_su_hoja(): void
    {
        $this->sembrar([
            'reporte_financiero' => [
                'periodo' => 'Marzo – Mayo de 2026',
                'hoja_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
                'cifras' => [
                    ['concepto' => 'Cuotas recibidas', 'monto' => 48250.5],
                    ['concepto' => 'Saldo final', 'monto' => 12100, 'destacada' => true],
                ],
            ],
        ]);

        $reporte = ReporteFinanciero::actual();

        $this->assertSame('Marzo – Mayo de 2026', $reporte->periodo);
        $this->assertSame('https://docs.google.com/spreadsheets/d/abc123/edit', $reporte->hoja_url);
        $this->assertCount(2, $reporte->resumen());
        $this->assertTrue($reporte->resumen()->last()->destacada);

        $this->get(route('reporte-financiero'))
            ->assertSee('Cuotas recibidas')
            ->assertSee('$48,250.50');
    }

    /**
     * El Reporte es una tabla de un solo renglón: sembrarlo otra vez lo
     * actualiza, nunca deja dos reportes conviviendo.
     */
    public function test_sembrar_dos_veces_no_crea_un_segundo_reporte(): void
    {
        $this->sembrar(['reporte_financiero' => ['periodo' => 'Primera captura']]);
        $this->sembrar(['reporte_financiero' => ['periodo' => 'Segunda captura']]);

        $this->assertDatabaseCount('reporte_financiero', 1);
        $this->assertSame('Segunda captura', ReporteFinanciero::actual()->periodo);
    }

    public function test_una_cifra_sin_concepto_o_sin_monto_no_se_siembra(): void
    {
        $this->sembrar([
            'reporte_financiero' => [
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
            'reporte_financiero' => ['periodo' => null, 'hoja_url' => null, 'cifras' => []],
        ]);

        $this->assertDatabaseCount('actividades', 0);
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
        $this->assertIsArray($contenido['reporte_financiero']['cifras']);
        $this->assertArrayHasKey('periodo', $contenido['reporte_financiero']);
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
