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

    /**
     * Todo reporte nace con un mes, así que las pruebas también: sin él no hay
     * dirección ni orden en el histórico. Por omisión, junio de 2026.
     */
    private function sembrar(array $atributos = []): ReporteFinanciero
    {
        return ReporteFinanciero::query()->create($atributos + ['mes' => '2026-06']);
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

    /**
     * El periodo se deriva del mes, no se captura: el título de la página y su
     * dirección salen del mismo dato y no pueden contradecirse.
     */
    public function test_muestra_el_periodo_que_cubre_el_reporte(): void
    {
        $this->sembrar([
            'mes' => '2026-04',
            'cifras' => [['concepto' => 'Saldo final', 'monto' => 1]],
        ]);

        $this->get(route('reporte-financiero'))->assertSee('Periodo: Abril de 2026');
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

    /*
    |--------------------------------------------------------------------------
    | El histórico (docs/adr/0005)
    |--------------------------------------------------------------------------
    */

    /**
     * Lo que hace que el archivo sirva de algo: capturar un mes nuevo no borra
     * al anterior, lo empuja al histórico. Antes esto era imposible — la tabla
     * era de un solo renglón.
     */
    public function test_capturar_un_mes_nuevo_no_borra_el_anterior(): void
    {
        $this->sembrar(['mes' => '2026-06', 'cifras' => [['concepto' => 'Cuotas de junio', 'monto' => 100]]]);
        $this->sembrar(['mes' => '2026-07', 'cifras' => [['concepto' => 'Cuotas de julio', 'monto' => 200]]]);

        $this->assertDatabaseCount('reporte_financiero', 2);

        // La raíz publica el más reciente.
        $this->get(route('reporte-financiero'))
            ->assertSee('Cuotas de julio')
            ->assertDontSee('Cuotas de junio');

        // Y junio sigue en pie, en su propia dirección.
        $this->get(route('reporte-financiero.mes', ['mes' => '2026-06']))
            ->assertOk()
            ->assertSee('Cuotas de junio');
    }

    /**
     * El mes vigente no depende del calendario sino de lo capturado: mientras
     * nadie capture julio, junio sigue siendo lo que se publica.
     */
    public function test_el_vigente_es_el_mes_mas_reciente_capturado_sin_importar_el_orden_de_captura(): void
    {
        $this->sembrar(['mes' => '2026-07', 'cifras' => [['concepto' => 'Cuotas de julio', 'monto' => 200]]]);
        $this->sembrar(['mes' => '2026-05', 'cifras' => [['concepto' => 'Cuotas de mayo', 'monto' => 50]]]);

        $this->get(route('reporte-financiero'))->assertSee('Cuotas de julio');
    }

    /**
     * Un mes del archivo se ve idéntico al vigente. Si no lo dijera, alguien
     * leería cifras de hace medio año como si fueran las de hoy.
     */
    public function test_un_mes_del_archivo_avisa_que_ya_no_es_el_mas_reciente(): void
    {
        $this->sembrar(['mes' => '2026-06', 'cifras' => [['concepto' => 'Cuotas de junio', 'monto' => 100]]]);
        $this->sembrar(['mes' => '2026-07', 'cifras' => [['concepto' => 'Cuotas de julio', 'monto' => 200]]]);

        $this->get(route('reporte-financiero.mes', ['mes' => '2026-06']))
            ->assertSee('ya no es el más reciente')
            ->assertSee('Ver el reporte vigente')
            ->assertSee(route('reporte-financiero'));

        $this->get(route('reporte-financiero'))->assertDontSee('ya no es el más reciente');
    }

    public function test_un_mes_sin_reporte_es_404(): void
    {
        $this->sembrar(['mes' => '2026-06']);

        $this->get(route('reporte-financiero.mes', ['mes' => '2026-01']))->assertNotFound();
    }

    /**
     * La ruta solo acepta dígitos, así que un mes fuera de rango llega hasta el
     * controlador. Sin el corte, Carbon desbordaría `2026-13` a enero de 2027 y
     * esa URL serviría un reporte que no le corresponde.
     */
    public function test_un_mes_que_no_existe_en_el_calendario_es_404(): void
    {
        $this->sembrar(['mes' => '2027-01']);

        $this->get('/reporte-financiero/2026-13')->assertNotFound();
    }

    /**
     * La restricción de la ruta existe para que el parámetro no se trague
     * cualquier ruta hermana que se agregue después bajo `/reporte-financiero/`.
     */
    public function test_lo_que_no_tiene_forma_de_mes_ni_siquiera_entra_a_la_ruta(): void
    {
        $this->get('/reporte-financiero/indice')->assertNotFound();
    }

    /**
     * El histórico al que nadie llega es peso muerto: desde cualquier mes se
     * ven todos los demás.
     */
    public function test_el_indice_lista_los_meses_publicados(): void
    {
        $this->sembrar(['mes' => '2026-05', 'cifras' => [['concepto' => 'Mayo', 'monto' => 1]]]);
        $this->sembrar(['mes' => '2026-06', 'cifras' => [['concepto' => 'Junio', 'monto' => 1]]]);
        $this->sembrar(['mes' => '2026-07', 'cifras' => [['concepto' => 'Julio', 'monto' => 1]]]);

        $this->get(route('reporte-financiero'))
            ->assertSee('Meses publicados')
            // Del más reciente al más viejo.
            ->assertSeeInOrder(['Julio de 2026', 'Junio de 2026', 'Mayo de 2026'])
            ->assertSee(route('reporte-financiero.mes', ['mes' => '2026-06']))
            ->assertSee(route('reporte-financiero.mes', ['mes' => '2026-05']));
    }

    /**
     * Con un solo mes publicado el índice es una lista de un renglón que repite
     * lo que ya está arriba: ruido, no navegación.
     */
    public function test_con_un_solo_mes_no_aparece_el_indice(): void
    {
        $this->sembrar(['cifras' => [['concepto' => 'Saldo final', 'monto' => 1]]]);

        $this->get(route('reporte-financiero'))->assertDontSee('Meses publicados');
    }

    /**
     * El mes vigente se sirve en dos direcciones. La página declara cuál de las
     * dos vale, o los buscadores reparten entre ambas lo que vale una.
     */
    public function test_el_mes_vigente_declara_la_raiz_como_su_direccion_canonica(): void
    {
        $this->sembrar(['mes' => '2026-06', 'cifras' => [['concepto' => 'Junio', 'monto' => 1]]]);

        $canonica = '<link rel="canonical" href="'.route('reporte-financiero').'">';

        $this->get(route('reporte-financiero'))->assertSee($canonica, escape: false);
        $this->get(route('reporte-financiero.mes', ['mes' => '2026-06']))->assertSee($canonica, escape: false);
    }

    /**
     * La URL con fecha del mes vigente no redirige: tiene que seguir
     * funcionando igual el día que ese mes deje de serlo.
     */
    public function test_la_url_con_fecha_del_mes_vigente_sirve_la_pagina_en_vez_de_redirigir(): void
    {
        $this->sembrar(['mes' => '2026-06', 'cifras' => [['concepto' => 'Junio', 'monto' => 1]]]);

        $this->get(route('reporte-financiero.mes', ['mes' => '2026-06']))
            ->assertOk()
            ->assertSee('Junio');
    }

    /**
     * Un mes del archivo es su propia dirección canónica: la raíz ya publica
     * otro contenido.
     */
    public function test_un_mes_del_archivo_es_su_propia_direccion_canonica(): void
    {
        $this->sembrar(['mes' => '2026-06', 'cifras' => [['concepto' => 'Junio', 'monto' => 1]]]);
        $this->sembrar(['mes' => '2026-07', 'cifras' => [['concepto' => 'Julio', 'monto' => 1]]]);

        $this->get(route('reporte-financiero.mes', ['mes' => '2026-06']))
            ->assertSee('<link rel="canonical" href="'.route('reporte-financiero.mes', ['mes' => '2026-06']).'">', escape: false);
    }

    /**
     * El índice también se lee desde el archivo, sin barrera: el histórico
     * completo es público (docs/adr/0005).
     */
    public function test_los_meses_del_archivo_no_piden_nada_para_leerse(): void
    {
        $this->sembrar(['mes' => '2026-06', 'cifras' => [['concepto' => 'Junio', 'monto' => 1]]]);
        $this->sembrar(['mes' => '2026-07', 'cifras' => [['concepto' => 'Julio', 'monto' => 1]]]);

        $this->get(route('reporte-financiero.mes', ['mes' => '2026-06']))->assertOk();
    }

    /**
     * El día se descarta al guardar. Si se colara, dos capturas del mismo mes
     * con día distinto burlarían el índice único y la Asamblea acabaría con dos
     * reportes de junio sin saber cuál vale.
     */
    public function test_el_mes_se_guarda_normalizado_al_dia_uno(): void
    {
        $reporte = ReporteFinanciero::query()->create(['mes' => '2026-06-17']);

        $this->assertDatabaseHas('reporte_financiero', ['mes' => '2026-06-01']);
        $this->assertSame('2026-06', $reporte->fresh()->mesEnUrl());
    }
}
