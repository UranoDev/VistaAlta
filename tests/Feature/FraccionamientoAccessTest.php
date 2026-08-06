<?php

namespace Tests\Feature;

use App\Models\Fraccionamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraccionamientoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_see_all_fraccionamientos(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);
        Fraccionamiento::factory()->count(3)->create();

        $response = $this->actingAs($superAdmin)->get(route('fraccionamientos.index'));

        $response->assertStatus(200);
        $this->assertCount(3, $response->viewData('fraccionamientos'));
    }

    public function test_admin_only_sees_assigned_fraccionamientos(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $assigned = Fraccionamiento::factory()->create(['name' => 'Assigned']);
        $notAssigned = Fraccionamiento::factory()->create(['name' => 'Not Assigned']);

        $admin->fraccionamientos()->attach($assigned);

        $response = $this->actingAs($admin)->get(route('fraccionamientos.index'));

        $response->assertStatus(200);
        $response->assertSee('Assigned');
        $response->assertDontSee('Not Assigned');
        $this->assertCount(1, $response->viewData('fraccionamientos'));
    }

    public function test_admin_cannot_show_unassigned_fraccionamiento(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $notAssigned = Fraccionamiento::factory()->create();

        $response = $this->actingAs($admin)->get(route('fraccionamientos.show', $notAssigned));

        $response->assertStatus(403);
    }

    public function test_admin_cannot_edit_unassigned_fraccionamiento(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $notAssigned = Fraccionamiento::factory()->create();

        $response = $this->actingAs($admin)->get(route('fraccionamientos.edit', $notAssigned));

        $response->assertStatus(403);
    }
}
