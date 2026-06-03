<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Fraccionamiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_user_can_exist_with_a_defined_role(): void
    {
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        $this->assertEquals('superadmin', $user->role);
    }

    public function test_the_system_can_know_if_the_user_is_superadmin_or_admin(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->isFraccionamientoAdmin());

        $this->assertTrue($admin->isFraccionamientoAdmin());
        $this->assertFalse($admin->isSuperAdmin());
    }

    public function test_a_superadmin_can_operate_on_multiple_fraccionamientos(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        $fracc1 = Fraccionamiento::factory()->create(['name' => 'Vista Alta 1']);
        $fracc2 = Fraccionamiento::factory()->create(['name' => 'Vista Alta 2']);

        // Relationships (Superadmin doesn't necessarily need to be attached to all, 
        // but the base relationship should work)
        $superAdmin->fraccionamientos()->attach([$fracc1->id, $fracc2->id]);

        $this->assertCount(2, $superAdmin->fraccionamientos);
    }

    public function test_an_admin_can_be_associated_to_fraccionamientos(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $fracc = Fraccionamiento::factory()->create(['name' => 'Vista Alta']);

        $admin->fraccionamientos()->attach($fracc->id);

        $this->assertTrue($admin->fraccionamientos->contains($fracc));
        $this->assertTrue($fracc->users->contains($admin));
    }
}
