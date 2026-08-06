<?php

namespace Database\Factories;

use App\Models\Fraccionamiento;
use App\Models\Owner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        $fraccionamiento = Fraccionamiento::factory()->create();

        return [
            'fraccionamiento_id' => $fraccionamiento->id,
            'owner_id' => Owner::factory()->create(['fraccionamiento_id' => $fraccionamiento->id])->id,
            'section' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'unit' => fake()->unique()->numerify('##'),
        ];
    }
}
