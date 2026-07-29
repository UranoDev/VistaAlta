<?php

declare(strict_types=1);

namespace Tests\Feature\ReporteFinanciero;

use App\Models\ReporteFinanciero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * La página pública del Reporte financiero: el resumen de cifras y el enlace a
 * la hoja de cálculo. Sin ninguna barrera — ver `docs/adr/0004`.
 */
class PaginaReporteFinancieroTest extends TestCase
{
    use RefreshDatabase;

    private function sembrar(array $atributos = []): ReporteFinanciero
    {
        return ReporteFinanciero::query()->create($atributos);
    }

    public function test_la_pagina_no_pide_nada_para_leerse(): void
    {
        $this->sembrar([
            'cifras' => [['concepto' => 'Saldo final', 'monto' => 1000]],
            'hoja_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
        ]);

        $respuesta = $this->get(route('reporte-financiero'));

        $respuesta->assertOk();
        $respuesta->assertSee('Saldo final');
    }

    public function test_muestra_cada_cifra_con_su_concepto_y_su_monto(): void
    {
        $this->sembrar([
            'cifras' => [
                ['concepto' => 'Cuotas recibidas', 'monto' => 128450.5],
                ['concepto' => 'Vigilancia nocturna', 'monto' => -96000],
            ],
        ]);

        $respuesta = $this->get(route('reporte-financiero'));

        $respuesta->assertSee('Cuotas recibidas');
        $respuesta->assertSee('$128,450.50');
        $respuesta->assertSee('Vigilancia nocturna');
        $respuesta->assertSee('-$96,000.00');
    }

    public function test_las_cifras_salen_en_el_orden_en_que_se_capturaron(): void
    {
        $this->sembrar([
            'cifras' => [
                ['concepto' => 'Saldo inicial', 'monto' => 10],
                ['concepto' => 'Gastos del periodo', 'monto' => -4],
                ['concepto' => 'Saldo final', 'monto' => 6, 'destacada' => true],
            ],
        ]);

        $this->get(route('reporte-financiero'))
            ->assertSeeInOrder(['Saldo inicial', 'Gastos del periodo', 'Saldo final']);
    }

    public function test_muestra_el_periodo_que_cubre_el_reporte(): void
    {
        $this->sembrar([
            'periodo' => 'Abril – Junio 2026',
            'cifras' => [['concepto' => 'Saldo final', 'monto' => 1]],
        ]);

        $this->get(route('reporte-financiero'))->assertSee('Abril – Junio 2026', escape: false);
    }

    public function test_el_enlace_a_la_hoja_abre_en_pestana_nueva_y_dice_que_sale_del_sitio(): void
    {
        $this->sembrar(['hoja_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit']);

        $respuesta = $this->get(route('reporte-financiero'));

        $respuesta->assertSee('https://docs.google.com/spreadsheets/d/abc123/edit', escape: false);
        $respuesta->assertSee('target="_blank"', escape: false);
        $respuesta->assertSee('rel="noopener noreferrer"', escape: false);
        $respuesta->assertSee('Se abre en una pestaña nueva, fuera de este sitio');
        // El dominio a la vista: se sabe a dónde lleva antes de tocarlo.
        $respuesta->assertSee('docs.google.com');
    }

    public function test_sin_hoja_capturada_no_hay_enlace_roto(): void
    {
        $this->sembrar(['cifras' => [['concepto' => 'Saldo final', 'monto' => 1]]]);

        $respuesta = $this->get(route('reporte-financiero'));

        $respuesta->assertOk();
        $respuesta->assertSee('Saldo final');
        $respuesta->assertDontSee('Desglose completo');
        $respuesta->assertDontSee('Se abre en una pestaña nueva');
    }

    public function test_sin_resumen_capturado_el_enlace_sigue_publicandose(): void
    {
        $this->sembrar(['hoja_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit']);

        $respuesta = $this->get(route('reporte-financiero'));

        $respuesta->assertOk();
        $respuesta->assertSee('Desglose completo');
        $respuesta->assertDontSee('Resumen</h3>', escape: false);
    }

    public function test_sin_nada_capturado_la_pagina_lo_dice_en_vez_de_reventar(): void
    {
        $respuesta = $this->get(route('reporte-financiero'));

        $respuesta->assertOk();
        $respuesta->assertSee('El reporte financiero de este periodo se publica aquí.');
    }

    /**
     * Un renglón capturado a medias no debería tumbar la rendición de cuentas
     * completa: se descarta ese renglón y el resto se publica.
     */
    public function test_un_renglon_incompleto_se_omite_sin_tirar_la_pagina(): void
    {
        $this->sembrar([
            'cifras' => [
                ['concepto' => 'Cuotas recibidas', 'monto' => 100],
                ['concepto' => 'Sin monto'],
                ['monto' => 50],
                ['concepto' => '   ', 'monto' => 7],
            ],
        ]);

        $respuesta = $this->get(route('reporte-financiero'));

        $respuesta->assertOk();
        $respuesta->assertSee('Cuotas recibidas');
        $respuesta->assertDontSee('Sin monto');
    }

    /**
     * Leer la página no debe escribir en la base: la tabla nace vacía y una
     * visita no tiene por qué crear el renglón.
     */
    public function test_leer_la_pagina_no_crea_ningun_renglon(): void
    {
        $this->get(route('reporte-financiero'))->assertOk();

        $this->assertDatabaseCount('reporte_financiero', 0);
    }

    /**
     * El resumen se captura a mano; la hoja es la fuente de verdad y **no** se
     * copia ni se importa. Si algún día alguien "mejora" esto leyendo la hoja
     * desde el servidor, esta prueba lo detiene.
     */
    public function test_la_pagina_no_consulta_la_hoja_de_google(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->sembrar([
            'cifras' => [['concepto' => 'Saldo final', 'monto' => 1]],
            'hoja_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
        ]);

        $this->get(route('reporte-financiero'))->assertOk();

        Http::assertNothingSent();
    }
}
