<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Filament\Pages\ReporteFinanciero as Pantalla;
use App\Models\ReporteFinanciero;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La pantalla donde la Mesa Directiva captura el Reporte financiero. Lo que se
 * guarda aquí sale de inmediato en la página pública, que no pide nada para
 * leerse.
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
        Livewire::test(Pantalla::class)
            ->fillForm([
                'periodo' => 'Abril – Junio 2026',
                'cifras' => [
                    ['concepto' => 'Cuotas recibidas', 'monto' => 128450.5, 'destacada' => false],
                    ['concepto' => 'Saldo final', 'monto' => 32450, 'destacada' => true],
                ],
                'hoja_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        auth()->logout();

        $this->get(route('reporte-financiero'))
            ->assertSee('Abril – Junio 2026', escape: false)
            ->assertSee('Cuotas recibidas')
            ->assertSee('$128,450.50')
            ->assertSee('Saldo final')
            ->assertSee('https://docs.google.com/spreadsheets/d/abc123/edit', escape: false);
    }

    public function test_la_pantalla_llega_con_lo_que_ya_estaba_capturado(): void
    {
        ReporteFinanciero::query()->create([
            'periodo' => 'Enero – Marzo 2026',
            'cifras' => [['concepto' => 'Saldo final', 'monto' => 500, 'destacada' => true]],
            'hoja_url' => 'https://docs.google.com/spreadsheets/d/previo/edit',
        ]);

        Livewire::test(Pantalla::class)
            ->assertFormSet([
                'periodo' => 'Enero – Marzo 2026',
                'hoja_url' => 'https://docs.google.com/spreadsheets/d/previo/edit',
            ]);
    }

    /**
     * Es una tabla de un solo renglón: guardar de nuevo corrige lo publicado, no
     * apila una segunda versión que nadie sabría cuál es la vigente.
     */
    public function test_guardar_dos_veces_corrige_el_reporte_en_vez_de_apilar_otro(): void
    {
        Livewire::test(Pantalla::class)
            ->fillForm(['cifras' => [['concepto' => 'Cifra con un error', 'monto' => 1, 'destacada' => false]]])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(Pantalla::class)
            ->fillForm(['cifras' => [['concepto' => 'Cifra corregida', 'monto' => 2, 'destacada' => false]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('reporte_financiero', 1);

        auth()->logout();

        $this->get(route('reporte-financiero'))
            ->assertSee('Cifra corregida')
            ->assertDontSee('Cifra con un error');
    }

    public function test_el_enlace_tiene_que_ser_una_url(): void
    {
        Livewire::test(Pantalla::class)
            ->fillForm(['hoja_url' => 'la hoja de siempre'])
            ->call('save')
            ->assertHasFormErrors(['hoja_url' => 'url']);
    }

    public function test_cada_cifra_necesita_concepto_y_monto(): void
    {
        Livewire::test(Pantalla::class)
            ->fillForm(['cifras' => [['concepto' => null, 'monto' => null, 'destacada' => false]]])
            ->call('save')
            ->assertHasFormErrors([
                'cifras.0.concepto' => 'required',
                'cifras.0.monto' => 'required',
            ]);
    }

    public function test_el_monto_tiene_que_ser_un_numero(): void
    {
        Livewire::test(Pantalla::class)
            ->fillForm(['cifras' => [['concepto' => 'Cuotas', 'monto' => 'como cien mil', 'destacada' => false]]])
            ->call('save')
            ->assertHasFormErrors(['cifras.0.monto' => 'numeric']);
    }

    /**
     * Se puede guardar el reporte con solo la hoja, sin resumen: el detalle es
     * lo que sostiene la rendición de cuentas, el resumen es cortesía.
     */
    public function test_se_puede_publicar_solo_la_hoja_sin_capturar_cifras(): void
    {
        Livewire::test(Pantalla::class)
            ->fillForm([
                'cifras' => [],
                'hoja_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        auth()->logout();

        $this->get(route('reporte-financiero'))->assertSee('Desglose completo');
    }

    /**
     * Las dos advertencias que no se pueden deducir de la pantalla: que esto
     * queda público sin contraseña, y que las cifras no se leen de la hoja. Una
     * advertencia que solo vive en la documentación no la lee quien captura.
     */
    public function test_la_pantalla_advierte_que_esto_queda_publico(): void
    {
        Livewire::test(Pantalla::class)
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
}
