<?php

namespace Tests\Feature;

use App\Models\Fraccionamiento;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OwnerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_owners_index_page_is_displayed(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/owners');

        $response->assertStatus(200);
    }

    public function test_owners_create_page_is_displayed(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/owners/create');

        $response->assertStatus(200);
    }

    public function test_owner_can_be_created(): void
    {
        $fraccionamiento = Fraccionamiento::factory()->create();

        $response = $this->actingAs($this->user)
            ->post('/owners', [
                'fraccionamiento_id' => $fraccionamiento->id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '1234567890',
                'is_committee_member' => true,
            ]);

        $response->assertRedirect('/owners');
        $this->assertDatabaseHas('owners', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_committee_member' => true,
        ]);
    }

    public function test_owners_edit_page_is_displayed(): void
    {
        $owner = Owner::factory()->create();

        $response = $this->actingAs($this->user)
            ->get("/owners/{$owner->id}/edit");

        $response->assertStatus(200);
    }

    public function test_owner_can_be_updated(): void
    {
        $owner = Owner::factory()->create();
        $newFraccionamiento = Fraccionamiento::factory()->create();

        $response = $this->actingAs($this->user)
            ->patch("/owners/{$owner->id}", [
                'fraccionamiento_id' => $newFraccionamiento->id,
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'phone' => '0987654321',
                'is_committee_member' => false,
            ]);

        $response->assertRedirect('/owners');
        $this->assertDatabaseHas('owners', [
            'id' => $owner->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'is_committee_member' => false,
        ]);
    }

    public function test_owner_can_be_deleted(): void
    {
        $owner = Owner::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete("/owners/{$owner->id}");

        $response->assertRedirect('/owners');
        $this->assertDatabaseMissing('owners', [
            'id' => $owner->id,
        ]);
    }
}
