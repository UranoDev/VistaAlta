<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Filament\Resources\ReportesFinancieros\Pages\CreateReporteFinanciero;
use App\Filament\Resources\ReportesFinancieros\Pages\EditReporteFinanciero;
use App\Filament\Resources\ReportesFinancieros\Pages\ListReportesFinancieros;
use App\Models\ReporteFinanciero;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Las pantallas donde la Mesa Directiva captura los Reportes financieros. Lo que
 * se guarda aquí sale de inmediato en la página pública, que no pide nada para
 * leerse.
 *
 * Desde `docs/adr/0005` hay un reporte por mes y un listado: capturar un mes
 * nuevo ya no borra al anterior.
 */
class ReporteFinancieroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_lo_capturado_en_el_panel_sale_en_el_sitio(): void
    {
        Livewire::test(CreateReporteFinanciero::class)
            ->fillForm([
                'mes' => '2026-06-01',
                'cifras' => [
                    ['concepto' => 'Cuotas recibidas', 'monto' => 128450.5, 'destacada' => false],
                    ['concepto' => 'Saldo final', 'monto' => 32450, 'destacada' => true],
                ],
                'hoja_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        auth()->logout();

        $this->get(route('reporte-financiero'))
            ->assertSee('Periodo: Junio de 2026')
            ->assertSee('Cuotas recibidas')
            ->assertSee('$128,450.50')
            ->assertSee('Saldo final')
            ->assertSee('https://docs.google.com/spreadsheets/d/abc123/edit', escape: false);
    }

    public function test_la_pantalla_llega_con_lo_que_ya_estaba_capturado(): void
    {
        $reporte = ReporteFinanciero::query()->create([
            'mes' => '2026-03',
            'cifras' => [['concepto' => 'Saldo final', 'monto' => 500, 'destacada' => true]],
            'hoja_url' => 'https://docs.google.com/spreadsheets/d/previo/edit',
        ]);

        Livewire::test(EditReporteFinanciero::class, ['record' => $reporte->getKey()])
            ->assertFormSet([
                'mes' => '2026-03-01',
                'hoja_url' => 'https://docs.google.com/spreadsheets/d/previo/edit',
            ]);
    }

    /**
     * Lo que el histórico cambia: el mes siguiente se agrega, no reemplaza. La
     * página pública publica el más reciente y el anterior sigue consultable.
     */
    public function test_capturar_otro_mes_lo_agrega_en_vez_de_pisar_al_anterior(): void
    {
        $this->capturar('2026-06-01', 'Cuotas de junio');
        $this->capturar('2026-07-01', 'Cuotas de julio');

        $this->assertDatabaseCount('reporte_financiero', 2);

        auth()->logout();

        $this->get(route('reporte-financiero'))
            ->assertSee('Cuotas de julio')
            ->assertDontSee('Cuotas de junio');

        $this->get(route('reporte-financiero.mes', ['mes' => '2026-06']))
            ->assertOk()
            ->assertSee('Cuotas de junio');
    }

    /**
     * Corregir es editar el mes que ya está, no capturarlo otra vez: dos
     * reportes de junio dejarían a la Asamblea sin saber cuál vale.
     */
    public function test_no_se_pueden_capturar_dos_reportes_del_mismo_mes(): void
    {
        $this->capturar('2026-06-01', 'Cuotas de junio');

        Livewire::test(CreateReporteFinanciero::class)
            ->fillForm([
                'mes' => '2026-06-01',
                'cifras' => [['concepto' => 'Otra vez junio', 'monto' => 1, 'destacada' => false]],
            ])
            ->call('create')
            ->assertHasFormErrors(['mes' => 'unique']);

        $this->assertDatabaseCount('reporte_financiero', 1);
    }

    /**
     * Editar un reporte sin cambiarle el mes no debe chocar consigo mismo.
     */
    public function test_editar_un_mes_sin_moverlo_no_choca_con_su_propio_reporte(): void
    {
        $reporte = ReporteFinanciero::query()->create([
            'mes' => '2026-06',
            'cifras' => [['concepto' => 'Cifra con un error', 'monto' => 1]],
        ]);

        Livewire::test(EditReporteFinanciero::class, ['record' => $reporte->getKey()])
            ->fillForm(['cifras' => [['concepto' => 'Cifra corregida', 'monto' => 2, 'destacada' => false]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('reporte_financiero', 1);

        auth()->logout();

        $this->get(route('reporte-financiero'))
            ->assertSee('Cifra corregida')
            ->assertDontSee('Cifra con un error');
    }

    /**
     * El formulario ofrece una ventana de meses alrededor de hoy, pero el
     * histórico no caduca. Un reporte más viejo que esa ventana tiene que poder
     * editarse sin que el Select llegue en blanco y guardar lo mueva de mes.
     */
    public function test_un_reporte_mas_viejo_que_la_ventana_del_formulario_se_puede_editar(): void
    {
        $viejo = ReporteFinanciero::query()->create([
            'mes' => now()->startOfMonth()->subYears(5)->format('Y-m'),
            'cifras' => [['concepto' => 'Cifra con un error', 'monto' => 1]],
        ]);

        Livewire::test(EditReporteFinanciero::class, ['record' => $viejo->getKey()])
            ->assertFormSet(['mes' => $viejo->mes->toDateString()])
            ->fillForm(['cifras' => [['concepto' => 'Cifra corregida', 'monto' => 2, 'destacada' => false]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($viejo->mesEnUrl(), $viejo->fresh()->mesEnUrl());
    }

    public function test_el_mes_es_obligatorio(): void
    {
        Livewire::test(CreateReporteFinanciero::class)
            ->fillForm(['mes' => null])
            ->call('create')
            ->assertHasFormErrors(['mes' => 'required']);
    }

    public function test_el_enlace_tiene_que_ser_una_url(): void
    {
        Livewire::test(CreateReporteFinanciero::class)
            ->fillForm(['mes' => '2026-06-01', 'hoja_url' => 'la hoja de siempre'])
            ->call('create')
            ->assertHasFormErrors(['hoja_url' => 'url']);
    }

    public function test_cada_cifra_necesita_concepto_y_monto(): void
    {
        Livewire::test(CreateReporteFinanciero::class)
            ->fillForm([
                'mes' => '2026-06-01',
                'cifras' => [['concepto' => null, 'monto' => null, 'destacada' => false]],
            ])
            ->call('create')
            ->assertHasFormErrors([
                'cifras.0.concepto' => 'required',
                'cifras.0.monto' => 'required',
            ]);
    }

    public function test_el_monto_tiene_que_ser_un_numero(): void
    {
        Livewire::test(CreateReporteFinanciero::class)
            ->fillForm([
                'mes' => '2026-06-01',
                'cifras' => [['concepto' => 'Cuotas', 'monto' => 'como cien mil', 'destacada' => false]],
            ])
            ->call('create')
            ->assertHasFormErrors(['cifras.0.monto' => 'numeric']);
    }

    /**
     * Se puede guardar el reporte con solo la hoja, sin resumen: el detalle es
     * lo que sostiene la rendición de cuentas, el resumen es cortesía.
     */
    public function test_se_puede_publicar_solo_la_hoja_sin_capturar_cifras(): void
    {
        Livewire::test(CreateReporteFinanciero::class)
            ->fillForm([
                'mes' => '2026-06-01',
                'cifras' => [],
                'hoja_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        auth()->logout();

        $this->get(route('reporte-financiero'))->assertSee('Desglose completo');
    }

    /**
     * El listado es lo que hace consultable el histórico desde el panel: todos
     * los meses, el más reciente arriba.
     */
    public function test_el_listado_trae_todos_los_meses_capturados(): void
    {
        ReporteFinanciero::query()->create(['mes' => '2026-05']);
        ReporteFinanciero::query()->create(['mes' => '2026-06']);

        Livewire::test(ListReportesFinancieros::class)
            ->assertCanSeeTableRecords(ReporteFinanciero::publicados());
    }

    /**
     * Las dos advertencias que no se pueden deducir de la pantalla: que esto
     * queda público sin contraseña, y que las cifras no se leen de la hoja. Una
     * advertencia que solo vive en la documentación no la lee quien captura.
     */
    public function test_la_pantalla_advierte_que_esto_queda_publico(): void
    {
        Livewire::test(CreateReporteFinanciero::class)
            ->assertSee('Esto queda público, sin contraseña')
            ->assertSee('Revisa cómo está compartida la hoja');
    }

    public function test_la_pantalla_pide_autenticacion(): void
    {
        auth()->logout();

        $this->get('/admin/reporte-financiero')->assertRedirect();
    }

    public function test_la_pagina_publica_sigue_sin_pedir_autenticacion(): void
    {
        auth()->logout();

        $this->get(route('reporte-financiero'))->assertOk();
    }

    private function capturar(string $mes, string $concepto): void
    {
        Livewire::test(CreateReporteFinanciero::class)
            ->fillForm([
                'mes' => $mes,
                'cifras' => [['concepto' => $concepto, 'monto' => 1, 'destacada' => false]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }
}
