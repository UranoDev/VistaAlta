<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Filament\Resources\Actividades\Pages\CreateActividad;
use App\Filament\Resources\Actividades\Pages\EditActividad;
use App\Filament\Resources\Actividades\Pages\ListActividades;
use App\Models\Actividad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El CRUD de Actividades en el panel de la Mesa Directiva. Lo que se da de alta
 * aquí sale de inmediato en la página pública: no hay borradores ni moderación
 * de por medio.
 */
class ActividadesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_el_panel_lista_las_actividades(): void
    {
        $actividad = Actividad::factory()->create(['descripcion' => 'Se contrató la vigilancia nocturna.']);

        Livewire::test(ListActividades::class)
            ->assertCanSeeTableRecords([$actividad])
            ->assertSee('Se contrató la vigilancia nocturna.');
    }

    public function test_dar_de_alta_una_actividad_la_publica_en_el_sitio(): void
    {
        Livewire::test(CreateActividad::class)
            ->fillForm([
                'fecha' => '2026-06-15',
                'descripcion' => 'Se podaron las áreas verdes.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('actividades', ['descripcion' => 'Se podaron las áreas verdes.']);

        auth()->logout();
        $this->get(route('actividades'))->assertSee('Se podaron las áreas verdes.');
    }

    public function test_la_fecha_y_la_descripcion_son_obligatorias(): void
    {
        Livewire::test(CreateActividad::class)
            ->fillForm(['fecha' => null, 'descripcion' => null])
            ->call('create')
            ->assertHasFormErrors(['fecha' => 'required', 'descripcion' => 'required']);
    }

    public function test_editar_una_actividad_cambia_lo_que_lee_la_asamblea(): void
    {
        $actividad = Actividad::factory()->create(['descripcion' => 'Redacción con un error.']);

        Livewire::test(EditActividad::class, ['record' => $actividad->getKey()])
            ->fillForm(['descripcion' => 'Redacción corregida.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Redacción corregida.', $actividad->refresh()->descripcion);

        auth()->logout();
        $this->get(route('actividades'))
            ->assertSee('Redacción corregida.')
            ->assertDontSee('Redacción con un error.');
    }

    public function test_borrar_una_actividad_la_saca_del_sitio(): void
    {
        $actividad = Actividad::factory()->create(['descripcion' => 'Publicada por equivocación.']);

        Livewire::test(ListActividades::class)
            ->callTableAction('delete', $actividad)
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('actividades', ['id' => $actividad->getKey()]);

        auth()->logout();
        $this->get(route('actividades'))->assertDontSee('Publicada por equivocación.');
    }

    /**
     * El formulario tiene dos campos y nada más. Si algún día aparece un tercero
     * llamado costo o archivo, esta prueba lo detiene antes de que llegue a la
     * Asamblea una segunda cuenta del mismo gasto.
     */
    public function test_el_formulario_no_ofrece_costo_ni_adjunto(): void
    {
        Livewire::test(CreateActividad::class)
            ->assertFormFieldExists('fecha')
            ->assertFormFieldExists('descripcion')
            ->assertFormFieldDoesNotExist('costo')
            ->assertFormFieldDoesNotExist('archivo');
    }

    public function test_el_panel_pide_autenticacion(): void
    {
        auth()->logout();

        $this->get('/admin/actividades')->assertRedirect();
    }
}
