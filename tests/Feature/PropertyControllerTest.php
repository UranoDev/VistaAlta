<?php

namespace Tests\Feature;

use App\Models\Fraccionamiento;
use App\Models\Owner;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'superadmin']);
    }

    public function test_can_list_properties(): void
    {
        Property::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('properties.index'));

        $response->assertStatus(200);
        $response->assertViewHas('properties');
    }

    public function test_can_show_create_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('properties.create'));

        $response->assertStatus(200);
    }

    public function test_can_store_property(): void
    {
        $fraccionamiento = Fraccionamiento::factory()->create();
        $owner = Owner::factory()->create(['fraccionamiento_id' => $fraccionamiento->id]);

        $data = [
            'fraccionamiento_id' => $fraccionamiento->id,
            'owner_id' => $owner->id,
            'section' => 'A',
            'unit' => '12',
        ];

        $response = $this->actingAs($this->user)->post(route('properties.store'), $data);

        $response->assertRedirect(route('properties.index'));
        $this->assertDatabaseHas('properties', $data);
    }

    public function test_can_store_property_without_owner(): void
    {
        $fraccionamiento = Fraccionamiento::factory()->create();

        $data = [
            'fraccionamiento_id' => $fraccionamiento->id,
            'owner_id' => null,
            'section' => 'B',
            'unit' => '05',
        ];

        $response = $this->actingAs($this->user)->post(route('properties.store'), $data);

        $response->assertRedirect(route('properties.index'));
        $this->assertDatabaseHas('properties', [
            'fraccionamiento_id' => $fraccionamiento->id,
            'unit' => '05',
            'owner_id' => null,
        ]);
    }

    public function test_unit_is_required(): void
    {
        $fraccionamiento = Fraccionamiento::factory()->create();

        $response = $this->actingAs($this->user)->post(route('properties.store'), [
            'fraccionamiento_id' => $fraccionamiento->id,
            'unit' => '',
        ]);

        $response->assertSessionHasErrors('unit');
    }

    public function test_fraccionamiento_is_required(): void
    {
        $response = $this->actingAs($this->user)->post(route('properties.store'), [
            'fraccionamiento_id' => '',
            'unit' => 'A1',
        ]);

        $response->assertSessionHasErrors('fraccionamiento_id');
    }

    public function test_property_belongs_to_single_owner(): void
    {
        $fraccionamiento = Fraccionamiento::factory()->create();
        $owner = Owner::factory()->create(['fraccionamiento_id' => $fraccionamiento->id]);

        $property = Property::factory()->create([
            'fraccionamiento_id' => $fraccionamiento->id,
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals($owner->id, $property->owner_id);
        $this->assertInstanceOf(Owner::class, $property->owner);
    }

    public function test_can_show_edit_page(): void
    {
        $property = Property::factory()->create();

        $response = $this->actingAs($this->user)->get(route('properties.edit', $property));

        $response->assertStatus(200);
    }

    public function test_can_update_property(): void
    {
        $property = Property::factory()->create();
        $newFraccionamiento = Fraccionamiento::factory()->create();

        $data = [
            'fraccionamiento_id' => $newFraccionamiento->id,
            'owner_id' => null,
            'section' => 'C',
            'unit' => '99',
        ];

        $response = $this->actingAs($this->user)->patch(route('properties.update', $property), $data);

        $response->assertRedirect(route('properties.index'));
        $this->assertDatabaseHas('properties', array_merge(['id' => $property->id], $data));
    }

    public function test_can_delete_property(): void
    {
        $property = Property::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('properties.destroy', $property));

        $response->assertRedirect(route('properties.index'));
        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
    }

    public function test_admin_only_sees_assigned_fraccionamientos_properties(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $assigned = Fraccionamiento::factory()->create();
        $other = Fraccionamiento::factory()->create();
        $admin->fraccionamientos()->attach($assigned);

        $visibleProperty = Property::factory()->create(['fraccionamiento_id' => $assigned->id]);
        $hiddenProperty = Property::factory()->create(['fraccionamiento_id' => $other->id]);

        $response = $this->actingAs($admin)->get(route('properties.index'));

        $response->assertStatus(200);

        $properties = $response->viewData('properties');
        $ids = $properties->pluck('id');

        $this->assertTrue($ids->contains($visibleProperty->id));
        $this->assertFalse($ids->contains($hiddenProperty->id));
    }

    public function test_superadmin_sees_all_properties(): void
    {
        $frac1 = Fraccionamiento::factory()->create();
        $frac2 = Fraccionamiento::factory()->create();

        Property::factory()->create(['fraccionamiento_id' => $frac1->id]);
        Property::factory()->create(['fraccionamiento_id' => $frac2->id]);

        $response = $this->actingAs($this->user)->get(route('properties.index'));

        $response->assertStatus(200);
        $this->assertEquals(2, $response->viewData('properties')->total());
    }
}
