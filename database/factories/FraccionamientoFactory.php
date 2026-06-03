<?php

namespace Database\Factories;

use App\Models\Fraccionamiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fraccionamiento>
 */
class FraccionamientoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->company();
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'address' => $this->faker->address(),
            'contact' => $this->faker->name() . ' - ' . $this->faker->phoneNumber(),
        ];
    }
}
