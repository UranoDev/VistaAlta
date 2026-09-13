<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * El contenido sale con formato de Markdown —un título y una lista— y no
     * con un párrafo pelón: lo que distingue a un post de una Actividad es
     * justamente que se pinta con formato, y una fábrica que solo produce texto
     * plano deja esa mitad sin ejercitar.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titulo = rtrim(fake()->sentence(4), '.');

        return [
            'titulo' => $titulo,
            'slug' => Str::slug($titulo).'-'.fake()->unique()->numberBetween(1, 100000),
            'contenido' => '## '.rtrim(fake()->sentence(3), '.')."\n\n".fake()->paragraph()."\n\n- ".fake()->sentence(5)."\n- ".fake()->sentence(5)."\n",
            'publicado_en' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Un post con dirección conocida, para las pruebas que piden la página.
     */
    public function conSlug(string $slug): static
    {
        return $this->state(fn (array $attributes) => ['slug' => $slug]);
    }

    /**
     * Un post en una fecha concreta, para las pruebas de orden del índice.
     */
    public function publicadoEn(string $fecha): static
    {
        return $this->state(fn (array $attributes) => ['publicado_en' => $fecha]);
    }
}
