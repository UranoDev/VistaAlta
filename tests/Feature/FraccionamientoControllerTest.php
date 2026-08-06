<?php

namespace Tests\Feature;

use App\Models\Fraccionamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraccionamientoControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'superadmin']);
    }

    public function test_can_list_fraccionamientos(): void
    {
        Fraccionamiento::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('fraccionamientos.index'));

        $response->assertStatus(200);
        $response->assertViewHas('fraccionamientos');
    }

    public function test_can_show_create_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('fraccionamientos.create'));

        $response->assertStatus(200);
    }

    public function test_can_store_fraccionamiento(): void
    {
        $data = [
            'name' => 'Vista Alta',
            'slug' => 'vista-alta',
            'address' => 'Tequisquiapan, Qro.',
            'contact' => 'Admin - 1234567890',
        ];

        $response = $this->actingAs($this->user)->post(route('fraccionamientos.store'), $data);

        $response->assertRedirect(route('fraccionamientos.index'));
        $this->assertDatabaseHas('fraccionamientos', $data);
    }

    public function test_cannot_store_duplicate_slug(): void
    {
        Fraccionamiento::factory()->create(['slug' => 'test-slug']);

        $data = [
            'name' => 'Other',
            'slug' => 'test-slug',
        ];

        $response = $this->actingAs($this->user)->post(route('fraccionamientos.store'), $data);

        $response->assertSessionHasErrors('slug');
    }

    public function test_can_show_edit_page(): void
    {
        $fraccionamiento = Fraccionamiento::factory()->create();

        $response = $this->actingAs($this->user)->get(route('fraccionamientos.edit', $fraccionamiento));

        $response->assertStatus(200);
    }

    public function test_can_update_fraccionamiento(): void
    {
        $fraccionamiento = Fraccionamiento::factory()->create();

        $data = [
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
        ];

        $response = $this->actingAs($this->user)->patch(route('fraccionamientos.update', $fraccionamiento), $data);

        $response->assertRedirect(route('fraccionamientos.index'));
        $this->assertDatabaseHas('fraccionamientos', array_merge(['id' => $fraccionamiento->id], $data));
    }

    public function test_can_delete_fraccionamiento(): void
    {
        $fraccionamiento = Fraccionamiento::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('fraccionamientos.destroy', $fraccionamiento));

        $response->assertRedirect(route('fraccionamientos.index'));
        $this->assertDatabaseMissing('fraccionamientos', ['id' => $fraccionamiento->id]);
    }
}
