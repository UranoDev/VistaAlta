<?php

declare(strict_types=1);

namespace App\Models;

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
 * - **Sin marca de cumplido.** El pendiente que se cumple no se archiva: se
 *   convierte en Actividad y se retira (ver la acción «Ya se hizo» del panel).
 *   Conservarlo con un `cumplido` obligaría a filtrar en cada consulta a cambio
 *   de una trazabilidad que nadie ha pedido.
 */
#[Fillable(['titulo', 'detalle', 'orden'])]
class Pendiente extends Model
{
    /** @use HasFactory<PendienteFactory> */
    use HasFactory;

    /**
     * En el orden que la Mesa Directiva les dio desde el panel, no en el que se
     * capturaron: el primer renglón es el pendiente del que cuelgan los demás.
     * El desempate por `id` mantiene la lista estable cuando dos comparten
     * `orden` —lo que pasa apenas se agrega uno, que nace en 0.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function enOrden(Builder $query): void
    {
        $query->orderBy('orden')->orderBy('id');
    }
}
