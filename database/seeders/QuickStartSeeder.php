<?php

namespace Database\Seeders;

use App\Models\Fraccionamiento;
use App\Models\Owner;
use Illuminate\Database\Seeder;

class QuickStartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear algunos fraccionamientos
        $vistaAlta = Fraccionamiento::create([
            'name' => 'Vista Alta Residencial',
            'slug' => 'vista-alta',
            'address' => 'Tequisquiapan, Querétaro',
            'contact' => 'Admin Vista Alta - 4421234567',
        ]);

        $laCantera = Fraccionamiento::create([
            'name' => 'La Cantera',
            'slug' => 'la-cantera',
            'address' => 'Centro, Tequisquiapan',
            'contact' => 'Oficina La Cantera - 4429876543',
        ]);

        // Crear propietarios para Vista Alta
        Owner::create([
            'fraccionamiento_id' => $vistaAlta->id,
            'name' => 'Juan Pérez',
            'email' => 'juan.perez@example.com',
            'phone' => '4421112233',
            'is_committee_member' => true,
        ]);

        Owner::create([
            'fraccionamiento_id' => $vistaAlta->id,
            'name' => 'María García',
            'email' => 'maria.g@example.com',
            'phone' => '4423334455',
            'is_committee_member' => false,
        ]);

        // Crear propietarios para La Cantera
        Owner::create([
            'fraccionamiento_id' => $laCantera->id,
            'name' => 'Roberto Sánchez',
            'email' => 'roberto.s@example.com',
            'phone' => '4425556677',
            'is_committee_member' => false,
        ]);
    }
}
