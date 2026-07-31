<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * «El panel exige autenticación; el resto del sitio no pide nada nunca.»
 */
class AccesoAlPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_panel_rechaza_a_quien_no_inicio_sesion(): void
    {
        $this->get('/admin/comentarios')->assertRedirect('/admin/login');
        $this->get('/admin/actividades')->assertRedirect('/admin/login');
        $this->get('/admin/pendientes')->assertRedirect('/admin/login');
        $this->get('/admin/reporte-financiero')->assertRedirect('/admin/login');
    }

    public function test_la_pantalla_de_ingreso_esta_disponible(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_la_contrasena_correcta_abre_la_sesion(): void
    {
        $integrante = User::factory()->create(['password' => 'una-contrasena-larga']);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $integrante->email,
                'password' => 'una-contrasena-larga',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($integrante);
    }

    public function test_la_contrasena_incorrecta_no_abre_nada(): void
    {
        $integrante = User::factory()->create(['password' => 'una-contrasena-larga']);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $integrante->email,
                'password' => 'la-que-no-es',
            ])
            ->call('authenticate')
            ->assertHasFormErrors();

        $this->assertGuest();
    }

    public function test_la_mesa_directiva_entra_al_panel(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/comentarios')->assertOk();
        $this->get('/admin/actividades')->assertOk();
        $this->get('/admin/pendientes')->assertOk();
        $this->get('/admin/reporte-financiero')->assertOk();
    }

    /**
     * El panel es el único lugar del sitio detrás de una sesión. Las tres
     * páginas públicas no piden nada, ni siquiera después de que el panel montó
     * su propio middleware de autenticación.
     */
    public function test_el_resto_del_sitio_sigue_sin_pedir_nada(): void
    {
        $this->get(route('propuesta'))->assertOk();
        $this->get(route('actividades'))->assertOk();
        $this->get(route('reporte-financiero'))->assertOk();
    }
}
