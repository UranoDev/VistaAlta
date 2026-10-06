<?php

namespace Database\Factories;

use App\Enums\Calle;
use App\Models\SolicitudDeInternet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitudDeInternet>
 */
class SolicitudDeInternetFactory extends Factory
{
    protected $model = SolicitudDeInternet::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->unique()->numberBetween(1, 100000),
            'calle' => fake()->randomElement(Calle::cases()),
            'numero_oficial' => (string) fake()->numberBetween(1, 400),
            'manzana' => (string) fake()->numberBetween(1, 20),
            'lote' => (string) fake()->numberBetween(1, 40),
            'celular' => '55'.fake()->numerify('########'),
        ];
    }
}
