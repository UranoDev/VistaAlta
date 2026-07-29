<?php

namespace Database\Factories;

use App\Models\Actividad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Actividad>
 */
class ActividadFactory extends Factory
{
    protected $model = Actividad::class;

    /**
     * Define the model's default state.
     *
     * Las fechas caen dentro de los últimos tres meses, que es el Periodo que
     * cubre esta edición de la rendición de cuentas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => fake()->dateTimeBetween('-3 months', 'now'),
            'descripcion' => fake()->sentence(12),
        ];
    }

    /**
     * Una Actividad en una fecha concreta, para las pruebas de orden.
     */
    public function enFecha(string $fecha): static
    {
        return $this->state(fn (array $attributes) => ['fecha' => $fecha]);
    }
}
