<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Enums\Calle;
use App\Filament\Resources\SolicitudesDeInternet\Pages\ListSolicitudesDeInternet;
use App\Models\SolicitudDeInternet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La lista de espera de internet en el panel: es donde se ve el celular de cada
 * propiedad, y no se publica en ninguna parte.
 */
class SolicitudesDeInternetTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_panel_la_exige_con_sesion(): void
    {
        $this->get('/admin/internet')->assertRedirect('/admin/login');
    }

    public function test_lista_las_solicitudes_con_su_folio(): void
    {
        $this->actingAs(User::factory()->create());

        $solicitud = SolicitudDeInternet::factory()->create(['numero' => 7, 'calle' => Calle::Nube, 'numero_oficial' => '45']);

        Livewire::test(ListSolicitudesDeInternet::class)
            ->assertCanSeeTableRecords([$solicitud])
            ->assertSee('INT-007')
            ->assertSee('Nube');
    }

    public function test_se_busca_por_folio_calle_o_celular(): void
    {
        $this->actingAs(User::factory()->create());

        $uno = SolicitudDeInternet::factory()->create(['numero' => 1, 'calle' => Calle::Clavel, 'celular' => '5511112222']);
        $dos = SolicitudDeInternet::factory()->create(['numero' => 2, 'calle' => Calle::Nube, 'celular' => '5533334444']);

        Livewire::test(ListSolicitudesDeInternet::class)
            ->searchTable('INT-002')
            ->assertCanSeeTableRecords([$dos])
            ->assertCanNotSeeTableRecords([$uno])
            ->searchTable('Clavel')
            ->assertCanSeeTableRecords([$uno])
            ->assertCanNotSeeTableRecords([$dos])
            ->searchTable('5533334444')
            ->assertCanSeeTableRecords([$dos])
            ->assertCanNotSeeTableRecords([$uno]);
    }

    public function test_se_filtra_por_calle_y_sale_en_orden_de_folio(): void
    {
        $this->actingAs(User::factory()->create());

        $segundo = SolicitudDeInternet::factory()->create(['numero' => 2, 'calle' => Calle::Nube]);
        $primero = SolicitudDeInternet::factory()->create(['numero' => 1, 'calle' => Calle::Nube]);
        $otra = SolicitudDeInternet::factory()->create(['numero' => 3, 'calle' => Calle::Malva]);

        Livewire::test(ListSolicitudesDeInternet::class)
            ->assertCanSeeTableRecords([$primero, $segundo, $otra], inOrder: true)
            ->filterTable('calle', 'Nube')
            ->assertCanSeeTableRecords([$primero, $segundo])
            ->assertCanNotSeeTableRecords([$otra]);
    }

    public function test_se_puede_borrar_una_solicitud(): void
    {
        $this->actingAs(User::factory()->create());

        $solicitud = SolicitudDeInternet::factory()->create();

        Livewire::test(ListSolicitudesDeInternet::class)->callTableAction('delete', $solicitud);

        $this->assertModelMissing($solicitud);
    }

    public function test_no_se_puede_dar_de_alta_desde_el_panel(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/internet/nuevo')->assertNotFound();
        $this->get('/admin/internet/create')->assertNotFound();
    }
}
