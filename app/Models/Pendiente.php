<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\SeMarcaComoNuevo;
use Database\Factories\PendienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Algo que la Mesa Directiva todavía no ha hecho, publicado en «Lo que sigue»
 * como la contraparte de la Bitácora de Actividades.
 *
 * Hermano de `Actividad` y con la misma disciplina de campos, pero la ausencia
 * que aquí importa es otra:
 *
 * - **Sin fecha comprometida.** Varios pendientes dependen de un tercero que
 *   lleva su propio paso. Una fecha que no se controla, publicada en un sitio
 *   de rendición de cuentas, se lee como promesa y se cobra como incumplida.
 * - **Sin fecha de cumplimiento comprometida**, por lo mismo.
 *
 * ## `cumplido_en`: una decisión que se revirtió
 *
 * Aquí decía que un pendiente cumplido no se archivaba —se convertía en
 * Actividad y se retiraba— porque conservarlo «obligaría a filtrar en cada
 * consulta a cambio de una trazabilidad que nadie ha pedido».
 *
 * Se pidió. Un renglón que desaparece de un día para otro no le dice nada a
 * quien vuelve a la página cada tantas semanas: no puede distinguir entre «esto
 * se cumplió» y «esto ya no lo van a hacer». Tachado durante unos días sí lo
 * dice, y para tachar algo hace falta que el renglón siga existiendo.
 *
 * El costo previsto se pagó y es exactamente el que estaba escrito: `enOrden`
 * ahora filtra. La ventana la fija `contenido.novedades.dias`, así que el
 * cumplido deja de publicarse solo — nadie tiene que volver a borrarlo.
 */
#[Fillable(['titulo', 'detalle', 'orden'])]
class Pendiente extends Model
{
    /** @use HasFactory<PendienteFactory> */
    use HasFactory;
    use SeMarcaComoNuevo;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cumplido_en' => 'datetime',
        ];
    }

    /**
     * En el orden que la Mesa Directiva les dio desde el panel, no en el que se
     * capturaron: el primer renglón es el pendiente del que cuelgan los demás.
     * El desempate por `id` mantiene la lista estable cuando dos comparten
     * `orden` —lo que pasa apenas se agrega uno, que nace en 0.
     *
     * Los cumplidos siguen apareciendo mientras estén dentro de la ventana de
     * novedad, y en su lugar de siempre: tachado donde el lector lo recuerda es
     * lo que hace que se lea como «esto se cerró» y no como una línea que se
     * borró. Pasada la ventana se caen solos de la lista.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function enOrden(Builder $query): void
    {
        $query->vigentes()->orderBy('orden')->orderBy('id');
    }

    /**
     * Los que todavía tienen algo que decir: los abiertos, más los cumplidos
     * hace poco —que se siguen mostrando tachados—.
     *
     * Va aparte de `enOrden` porque el panel necesita el mismo filtro sin el
     * ordenamiento: allá el orden lo pone el arrastre de la tabla. Escribirlo
     * dos veces sería dejar que la lista pública y la del panel se separen sin
     * que nadie lo note.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function vigentes(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query
                ->whereNull('cumplido_en')
                ->orWhere('cumplido_en', '>=', now()->subDays((int) config('contenido.novedades.dias', 0)));
        });
    }

    public function estaCumplido(): bool
    {
        return $this->cumplido_en !== null;
    }
}
