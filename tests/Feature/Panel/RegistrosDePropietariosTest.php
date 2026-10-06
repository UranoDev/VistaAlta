<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Enums\Calle;
use App\Enums\SituacionDelLote;
use App\Filament\Resources\RegistrosDePropietarios\Pages\ListRegistrosDePropietarios;
use App\Filament\Resources\RegistrosDePropietarios\Widgets\ResumenDeRegistros;
use App\Models\RegistroDePropietario;
use App\Models\User;
use App\Support\Registro\ListaCsv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La lista de registros de propietarios en el panel: la única pantalla donde se
 * ven sus teléfonos y correos.
 */
class RegistrosDePropietariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_panel_la_exige_con_sesion(): void
    {
        $this->get('/admin/registros')->assertRedirect('/admin/login');
    }

    public function test_lista_los_registros_con_sus_lotes(): void
    {
        $this->actingAs(User::factory()->create());

        $registro = RegistroDePropietario::factory()->conLote(Calle::Margarita, '128', SituacionDelLote::CasaTerminada)->create(['nombre' => 'Marta Ejemplo']);

        Livewire::test(ListRegistrosDePropietarios::class)
            ->assertCanSeeTableRecords([$registro])
            ->assertSee('Marta Ejemplo')
            ->assertSee('Margarita 128');
    }

    public function test_se_busca_por_calle_y_numero_nombre_o_telefono(): void
    {
        $this->actingAs(User::factory()->create());

        $marta = RegistroDePropietario::factory()->conLote(Calle::Margarita, '128')->create(['nombre' => 'Marta Ejemplo', 'telefono' => '5511112222']);
        $luis = RegistroDePropietario::factory()->conLote(Calle::Nube, '45')->create(['nombre' => 'Luis Ejemplo', 'telefono' => '5533334444']);

        Livewire::test(ListRegistrosDePropietarios::class)
            ->searchTable('Margarita 128')
            ->assertCanSeeTableRecords([$marta])
            ->assertCanNotSeeTableRecords([$luis])
            ->searchTable('Luis')
            ->assertCanSeeTableRecords([$luis])
            ->assertCanNotSeeTableRecords([$marta])
            ->searchTable('5511112222')
            ->assertCanSeeTableRecords([$marta])
            ->assertCanNotSeeTableRecords([$luis]);
    }

    public function test_se_filtra_por_calle(): void
    {
        $this->actingAs(User::factory()->create());

        $marta = RegistroDePropietario::factory()->conLote(Calle::Margarita, '128')->create();
        $luis = RegistroDePropietario::factory()->conLote(Calle::Nube, '45')->create();

        Livewire::test(ListRegistrosDePropietarios::class)
            ->filterTable('calle', 'Nube')
            ->assertCanSeeTableRecords([$luis])
            ->assertCanNotSeeTableRecords([$marta]);
    }

    public function test_las_cifras_cuentan_propietarios_y_lotes(): void
    {
        $this->actingAs(User::factory()->create());

        RegistroDePropietario::factory()->conLote(Calle::Clavel, '12', SituacionDelLote::CasaTerminada)->conLote(Calle::Clavel, '14')->create();
        RegistroDePropietario::factory()->conLote(Calle::Malva, '21', SituacionDelLote::EnConstruccion)->create();

        RegistroDePropietario::factory()->confirmado()->create();

        Livewire::test(ResumenDeRegistros::class)
            ->assertSee('Propietarios registrados')
            ->assertSee('1 confirmados')
            ->assertSee('Lotes registrados')
            ->assertSee('1 más en construcción');

        $this->assertSame(3, RegistroDePropietario::query()->count());
    }

    public function test_se_puede_ver_el_detalle_y_borrar_un_registro(): void
    {
        $this->actingAs(User::factory()->create());

        $registro = RegistroDePropietario::factory()->conContactoAdicional('Luis Ejemplo')->create(['nombre' => 'Marta Ejemplo']);

        // El contenido del panel lateral lo pinta el navegador; aquí solo se
        // comprueba que la acción existe y se puede abrir sobre el registro.
        Livewire::test(ListRegistrosDePropietarios::class)
            ->assertTableActionExists('view')
            ->mountTableAction('view', $registro)
            ->assertHasNoErrors();

        Livewire::test(ListRegistrosDePropietarios::class)
            ->callTableAction('delete', $registro);

        $this->assertModelMissing($registro);
        $this->assertDatabaseCount('lotes_registrados', 0);
        $this->assertDatabaseCount('contactos_del_registro', 0);
    }

    public function test_la_lista_distingue_confirmados_y_se_filtra_por_confirmacion(): void
    {
        $this->actingAs(User::factory()->create());

        $confirmado = RegistroDePropietario::factory()->confirmado()->create();
        $pendiente = RegistroDePropietario::factory()->create();

        Livewire::test(ListRegistrosDePropietarios::class)
            ->assertCanSeeTableRecords([$confirmado, $pendiente])
            ->filterTable('confirmado_en', true)
            ->assertCanSeeTableRecords([$confirmado])
            ->assertCanNotSeeTableRecords([$pendiente])
            ->filterTable('confirmado_en', false)
            ->assertCanSeeTableRecords([$pendiente])
            ->assertCanNotSeeTableRecords([$confirmado]);
    }

    public function test_el_csv_marca_si_el_registro_esta_confirmado(): void
    {
        RegistroDePropietario::factory()->confirmado()->create(['nombre' => 'Con Confirmacion']);
        RegistroDePropietario::factory()->create(['nombre' => 'Sin Confirmacion']);

        $texto = stream_get_contents(ListaCsv::flujo(RegistroDePropietario::query()->with(['lotes', 'contactos'])->oldest()->get()));

        $this->assertMatchesRegularExpression('/Con Confirmacion.*\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}"?\s*$/m', $texto);
        $this->assertMatchesRegularExpression('/Sin Confirmacion.*Pendiente\s*$/m', $texto);
    }

    public function test_no_se_puede_dar_de_alta_desde_el_panel(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/registros/nuevo')->assertNotFound();
        $this->get('/admin/registros/create')->assertNotFound();
    }

    public function test_el_csv_trae_un_renglon_por_lote_con_acentos_y_sin_formulas(): void
    {
        $this->actingAs(User::factory()->create());

        RegistroDePropietario::factory()
            ->conLote(Calle::Margarita, '128', SituacionDelLote::CasaTerminada)
            ->conLote(Calle::Nube, '45')
            ->conContactoAdicional('Luis Ejemplo')
            ->create(['nombre' => '=HYPERLINK("http://malo.example","Pérez")', 'telefono' => '5511112222', 'correo' => null]);

        $registros = RegistroDePropietario::query()->with(['lotes', 'contactos'])->get();
        $texto = stream_get_contents(ListaCsv::flujo($registros));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $texto);
        $this->assertStringContainsString('Núm. oficial', $texto);
        $this->assertStringContainsString("'=HYPERLINK", $texto);
        $this->assertStringNotContainsString(',=HYPERLINK', $texto);
        $this->assertStringContainsString('Casa terminada', $texto);
        // Un renglón por lote: el encabezado y los dos lotes pedidos.
        $this->assertCount(3, array_filter(explode("\n", trim($texto))));

        Livewire::test(ListRegistrosDePropietarios::class)
            ->callAction('descargarCsv')
            ->assertFileDownloaded();
    }

    public function test_celda_neutraliza_solo_lo_que_empieza_como_formula(): void
    {
        $this->assertSame("'=1+1", ListaCsv::celda('=1+1'));
        $this->assertSame("'+52", ListaCsv::celda('+52'));
        $this->assertSame("'-3", ListaCsv::celda('-3'));
        $this->assertSame("'@cuenta", ListaCsv::celda('@cuenta'));
        $this->assertSame('Pérez', ListaCsv::celda('Pérez'));
        $this->assertSame('', ListaCsv::celda(null));
        $this->assertSame('4', ListaCsv::celda(4));
    }
}
