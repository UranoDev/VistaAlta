<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Un post de Convivencia: lo que la Mesa Directiva publica sobre cómo se vive
 * el fraccionamiento —el manejo de la basura, el uso de las áreas comunes—.
 *
 * **No es una Actividad.** La Actividad rinde cuentas de algo que ya se hizo
 * durante el Periodo y se lee entera en la lista; un post no rinde cuentas de
 * nada, dice cómo se convive, y tiene su propia página porque es largo y se
 * comparte por enlace.
 *
 * Tampoco se somete a consideración: un post no lleva Comentarios (URVA-96). La
 * Propuesta es lo que se vota; esto se lee.
 *
 * El contenido es Markdown, capturado con el editor del panel, y se convierte a
 * HTML al pintarlo (`App\Support\Contenido\Markdown`). Es el único texto del
 * sitio con formato de verdad —títulos, listas, imágenes—; lo que se captura en
 * Actividades y Pendientes sigue siendo texto plano con ligas
 * (`App\Support\Contenido\TextoConLigas`), y esa diferencia es a propósito.
 */
#[Fillable(['titulo', 'slug', 'contenido', 'publicado_en'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publicado_en' => 'date',
        ];
    }

    /**
     * La dirección pública del post sale de su `slug`, no de su `id`: es lo que
     * se pega en el grupo de vecinos, y `/convivencia/manejo-de-la-basura` dice
     * de qué se trata antes de abrirlo.
     *
     * El slug se declara **en la ruta** (`{post:slug}`) y no aquí con un
     * `getRouteKeyName()`: eso lo volvería la llave de todas las rutas del
     * modelo, incluidas las del panel, y dejaría a la pantalla de edición
     * colgando de un campo que esa misma pantalla existe para cambiar. El panel
     * sigue con `id`, que es lo único del post que no se edita.
     */
    public function urlPublica(): string
    {
        return route('convivencia.post', $this);
    }

    /**
     * Lo más reciente primero, que es como se lee un índice de publicaciones. El
     * desempate por `id` mantiene el orden estable cuando dos posts se publican
     * el mismo día.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function recientes(Builder $query): void
    {
        $query->orderByDesc('publicado_en')->orderByDesc('id');
    }
}
