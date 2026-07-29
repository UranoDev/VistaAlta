<?php

namespace Database\Factories;

use App\Enums\EstadoModeracion;
use App\Enums\Visibilidad;
use App\Models\Comentario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comentario>
 */
class ComentarioFactory extends Factory
{
    protected $model = Comentario::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'telefono' => fake()->numerify('44#######'),
            'nombre' => fake()->name(),
            'comentario' => fake()->paragraph(),
            'url' => '/',
            'visibilidad' => Visibilidad::Publico,
            'estado' => EstadoModeracion::EnCola,
        ];
    }

    /**
     * `visibilidad` y `estado` no son asignables en masa en el modelo — es lo que
     * los deja fuera del alcance de cualquier request. La fábrica los pone a la
     * fuerza porque necesita sembrar los cuatro casos.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function newModel(array $attributes = []): Comentario
    {
        return (new Comentario)->forceFill($attributes);
    }

    /**
     * Público y publicado por la Mesa Directiva: se lee en el sitio.
     */
    public function publicado(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibilidad' => Visibilidad::Publico,
            'estado' => EstadoModeracion::Publicado,
        ]);
    }

    /**
     * Público pero todavía en la Cola de moderación.
     */
    public function enCola(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibilidad' => Visibilidad::Publico,
            'estado' => EstadoModeracion::EnCola,
        ]);
    }

    /**
     * Público y descartado por la Mesa Directiva: nunca se lee en el sitio.
     */
    public function descartado(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibilidad' => Visibilidad::Publico,
            'estado' => EstadoModeracion::Descartado,
        ]);
    }

    /**
     * Dirigido solo a la Mesa Directiva: sin estado, porque no pasa por la cola.
     */
    public function privado(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibilidad' => Visibilidad::Privado,
            'estado' => null,
        ]);
    }
}
