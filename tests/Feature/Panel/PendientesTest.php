<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Filament\Resources\Pendientes\Pages\CreatePendiente;
use App\Filament\Resources\Pendientes\Pages\EditPendiente;
use App\Filament\Resources\Pendientes\Pages\ListPendientes;
use App\Models\Actividad;
use App\Models\Pendiente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El CRUD de «Lo que sigue» en el panel. Lo que aquí se captura sale de
 * inmediato en la página pública de Actividades, debajo de la Bitácora.
 *
 * La pantalla existe para que la lista de lo que falta deje de requerir un
 * despliegue para corregirse; la acción «Ya se hizo» es la que hace cierta la
 * promesa que la propia página ya tiene escrita.
 */
class PendientesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_el_panel_lista_los_pendientes(): void
    {
        $pendiente = Pendiente::factory()->create(['titulo' => 'Constituir la Asociación Civil']);

        Livewire::test(ListPendientes::class)
            ->assertCanSeeTableRecords([$pendiente])
            ->assertSee('Constituir la Asociación Civil');
    }

    public function test_dar_de_alta_un_pendiente_lo_publica_en_el_sitio(): void
    {
        Livewire::test(CreatePendiente::class)
            ->fillForm([
                'titulo' => 'Alumbrado público al 100%',
                'detalle' => 'Reponer lo que está apagado y mantenerlo así.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pendientes', ['titulo' => 'Alumbrado público al 100%']);

        auth()->logout();
        $this->get(route('actividades'))->assertSee('Reponer lo que está apagado y mantenerlo así.');
    }

    public function test_el_titulo_y_el_detalle_son_obligatorios(): void
    {
        Livewire::test(CreatePendiente::class)
            ->fillForm(['titulo' => null, 'detalle' => null])
            ->call('create')
            ->assertHasFormErrors(['titulo' => 'required', 'detalle' => 'required']);
    }

    public function test_editar_un_pendiente_cambia_lo_que_lee_la_asamblea(): void
    {
        $pendiente = Pendiente::factory()->create(['detalle' => 'Redacción con un error.']);

        Livewire::test(EditPendiente::class, ['record' => $pendiente->getKey()])
            ->fillForm(['detalle' => 'Redacción corregida.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Redacción corregida.', $pendiente->refresh()->detalle);

        auth()->logout();
        $this->get(route('actividades'))
            ->assertSee('Redacción corregida.')
            ->assertDontSee('Redacción con un error.');
    }

    public function test_borrar_un_pendiente_lo_saca_del_sitio(): void
    {
        $pendiente = Pendiente::factory()->create(['titulo' => 'Capturado por equivocación']);

        Livewire::test(ListPendientes::class)
            ->callTableAction('delete', $pendiente)
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('pendientes', ['id' => $pendiente->getKey()]);

        auth()->logout();
        $this->get(route('actividades'))->assertDontSee('Capturado por equivocación');
    }

    /**
     * El corazón de la pantalla: el pendiente que se cumple sube a la Bitácora
     * con el día en que se hizo y sale de «Lo que sigue», en un solo movimiento.
     */
    public function test_ya_se_hizo_crea_la_actividad_del_dia_y_retira_el_pendiente(): void
    {
        Carbon::setTestNow('2026-08-11 09:30:00');

        $pendiente = Pendiente::factory()->create(['titulo' => 'Constituir la Asociación Civil']);

        Livewire::test(ListPendientes::class)
            ->callTableAction('yaSeHizo', $pendiente, ['descripcion' => 'Se constituyó la Asociación Civil ante notario.'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('pendientes', ['id' => $pendiente->getKey()]);

        $actividad = Actividad::query()->sole();
        $this->assertSame('Se constituyó la Asociación Civil ante notario.', $actividad->descripcion);
        $this->assertSame('2026-08-11', $actividad->fecha->toDateString());

        auth()->logout();
        $this->get(route('actividades'))
            ->assertSee('Se constituyó la Asociación Civil ante notario.')
            ->assertDontSee('Constituir la Asociación Civil');
    }

    /**
     * El pendiente dice qué falta; la Actividad dice qué se hizo. El título
     * llega precargado para no volver a teclearlo, pero el campo es editable
     * justamente para que la bitácora no quede escrita en futuro.
     */
    public function test_ya_se_hizo_precarga_el_titulo_del_pendiente_como_texto_editable(): void
    {
        $pendiente = Pendiente::factory()->create(['titulo' => 'Coladera repuesta']);

        Livewire::test(ListPendientes::class)
            ->mountTableAction('yaSeHizo', $pendiente)
            ->assertActionDataSet(['descripcion' => 'Coladera repuesta']);
    }

    public function test_ya_se_hizo_exige_una_descripcion(): void
    {
        $pendiente = Pendiente::factory()->create();

        Livewire::test(ListPendientes::class)
            ->callTableAction('yaSeHizo', $pendiente, ['descripcion' => ''])
            ->assertHasActionErrors(['descripcion' => 'required']);

        $this->assertDatabaseHas('pendientes', ['id' => $pendiente->getKey()]);
        $this->assertDatabaseCount('actividades', 0);
    }

    /**
     * El orden es contenido: el primer renglón es el pendiente del que cuelgan
     * los demás. Lo que se arrastra en el panel es lo que lee la Asamblea.
     */
    public function test_reacomodar_la_lista_cambia_el_orden_publicado(): void
    {
        $primero = Pendiente::factory()->enOrden(0)->create(['titulo' => 'El que iba primero']);
        $segundo = Pendiente::factory()->enOrden(1)->create(['titulo' => 'El que iba segundo']);

        Livewire::test(ListPendientes::class)
            ->call('reorderTable', [$segundo->getKey(), $primero->getKey()]);

        auth()->logout();
        $this->get(route('actividades'))
            ->assertSeeInOrder(['El que iba segundo', 'El que iba primero']);
    }

    /**
     * El formulario tiene dos campos. Si algún día aparece uno de fecha, esta
     * prueba lo detiene: la lista no compromete plazos que dependen de la
     * notaría, la Fraccionadora o un proveedor. Tampoco lleva casilla de
     * cumplido — para eso está «Ya se hizo».
     */
    public function test_el_formulario_no_ofrece_fecha_comprometida_ni_marca_de_cumplido(): void
    {
        Livewire::test(CreatePendiente::class)
            ->assertFormFieldExists('titulo')
            ->assertFormFieldExists('detalle')
            ->assertFormFieldDoesNotExist('fecha_compromiso')
            ->assertFormFieldDoesNotExist('fecha')
            ->assertFormFieldDoesNotExist('cumplido');
    }

    public function test_el_panel_pide_autenticacion(): void
    {
        auth()->logout();

        $this->get('/admin/pendientes')->assertRedirect();
    }
}
