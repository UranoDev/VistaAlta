<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoModeracion;
use App\Enums\Visibilidad;
use App\Exceptions\ComentarioPrivadoNoSeModera;
use App\Exceptions\VisibilidadEsDefinitiva;
use Database\Factories\ComentarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Ni `visibilidad` ni `estado` son asignables en masa, y no hay mutador que los
 * cambie: la visibilidad entra una sola vez por crearPublico()/crearPrivado() y
 * el estado solo se mueve por publicar()/descartar().
 */
#[Fillable(['telefono', 'nombre', 'comentario', 'url'])]
class Comentario extends Model
{
    /** @use HasFactory<ComentarioFactory> */
    use HasFactory;

    protected $table = 'comentarios';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibilidad' => Visibilidad::class,
            'estado' => EstadoModeracion::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $comentario): void {
            if ($comentario->isDirty('visibilidad')) {
                throw new VisibilidadEsDefinitiva;
            }
        });
    }

    /**
     * Un comentario público nace en la Cola de moderación: no aparece en el sitio
     * hasta que la Mesa Directiva lo publica.
     *
     * @param  array<string, mixed>  $atributos
     */
    public static function crearPublico(array $atributos): self
    {
        $comentario = new self($atributos);

        $comentario->visibilidad = Visibilidad::Publico;
        $comentario->estado = EstadoModeracion::EnCola;
        $comentario->save();

        return $comentario;
    }

    /**
     * Un comentario privado nace fuera de la Cola de moderación y sin estado:
     * está dirigido solo a la Mesa Directiva y nunca se publica.
     *
     * @param  array<string, mixed>  $atributos
     */
    public static function crearPrivado(array $atributos): self
    {
        $comentario = new self($atributos);

        $comentario->visibilidad = Visibilidad::Privado;
        $comentario->estado = null;
        $comentario->save();

        return $comentario;
    }

    public function esPublico(): bool
    {
        return $this->visibilidad === Visibilidad::Publico;
    }

    public function esPrivado(): bool
    {
        return $this->visibilidad === Visibilidad::Privado;
    }

    /**
     * Lo saca de la Cola de moderación y lo pone frente a la Asamblea.
     */
    public function publicar(): void
    {
        $this->moverA(EstadoModeracion::Publicado);
    }

    /**
     * Lo saca de la Cola de moderación sin publicarlo.
     */
    public function descartar(): void
    {
        $this->moverA(EstadoModeracion::Descartado);
    }

    private function moverA(EstadoModeracion $estado): void
    {
        if ($this->esPrivado()) {
            throw new ComentarioPrivadoNoSeModera;
        }

        $this->estado = $estado;
        $this->save();
    }

    /**
     * La lista que lee la Asamblea en el sitio.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function publicados(Builder $query): void
    {
        $query->where('visibilidad', Visibilidad::Publico)
            ->where('estado', EstadoModeracion::Publicado)
            ->latest();
    }

    /**
     * La Cola de moderación, en el orden en que llegaron.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function enCola(Builder $query): void
    {
        $query->where('visibilidad', Visibilidad::Publico)
            ->where('estado', EstadoModeracion::EnCola)
            ->oldest();
    }

    /**
     * Los que su autor dirigió únicamente a la Mesa Directiva.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function privados(Builder $query): void
    {
        $query->where('visibilidad', Visibilidad::Privado)->latest();
    }
}
