<?php

namespace Database\Factories;

use App\Models\Pendiente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pendiente>
 */
class PendienteFactory extends Factory
{
    protected $model = Pendiente::class;

    /**
     * Define the model's default state.
     *
     * Sin fecha, como el modelo: un pendiente no la lleva.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => rtrim(fake()->sentence(5), '.'),
            'detalle' => fake()->sentence(14),
            'orden' => 0,
        ];
    }

    /**
     * Un pendiente en una posición concreta, para las pruebas de orden.
     */
    public function enOrden(int $orden): static
    {
        return $this->state(fn (array $attributes) => ['orden' => $orden]);
    }
}
