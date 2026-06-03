<?php

namespace Tests\Feature;

use App\Models\Fraccionamiento;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_accessible()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Fraccionamientos');
        $response->assertSee('Propietarios');
        $response->assertSee('0 registrados');
    }

    public function test_fraccionamientos_index_has_crud_buttons()
    {
        $user = User::factory()->create();
        Fraccionamiento::factory()->create(['name' => 'Fracc Test']);

        $response = $this->actingAs($user)->get('/fraccionamientos');

        $response->assertStatus(200);
        $response->assertSee('Fracc Test');
        $response->assertSee('Nuevo Fraccionamiento');
        $response->assertSee('Editar');
        $response->assertSee('Eliminar');
    }

    public function test_owners_index_has_crud_buttons()
    {
        $user = User::factory()->create();
        $fracc = Fraccionamiento::factory()->create();
        Owner::factory()->create(['name' => 'Propietario Test', 'fraccionamiento_id' => $fracc->id]);

        $response = $this->actingAs($user)->get('/owners');

        $response->assertStatus(200);
        $response->assertSee('Propietario Test');
        $response->assertSee('Nuevo Propietario');
        $response->assertSee('Editar');
        $response->assertSee('Eliminar');
    }
}
