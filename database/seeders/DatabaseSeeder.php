<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        /*
         * El contenido con el que el sitio sale al aire. Va aquí para que un
         * despliegue no dependa de acordarse de una segunda orden; mientras el
         * archivo de contenido esté vacío no escribe nada.
         */
        $this->call(ContenidoInicialSeeder::class);
    }
}
