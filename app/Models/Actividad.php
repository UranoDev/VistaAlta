<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ActividadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Algo que la Mesa Directiva llevó a cabo durante el Periodo, publicado como
 * evidencia de gestión.
 *
 * El modelo tiene exactamente dos campos y no va a crecer con un tercero
 * "para después":
 *
 * - **Sin costo.** El dinero se rinde solo en el Reporte financiero. Un costo
 *   por Actividad sería una segunda cuenta del mismo gasto, y dos totales que
 *   no cuadran es lo que más rápido hace perder una Asamblea.
 * - **Sin documento adjunto.** No hay carga de archivos en ninguna parte del
 *   sitio; la Actividad se lee en la propia página.
 */
#[Fillable(['fecha', 'descripcion'])]
class Actividad extends Model
{
    /** @use HasFactory<ActividadFactory> */
    use HasFactory;

    /** El plural de «actividad» no sale de la convención de Eloquent. */
    protected $table = 'actividades';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * Lo más reciente primero, que es como se lee la gestión del Periodo. El
     * desempate por `id` mantiene el orden estable cuando dos Actividades caen
     * el mismo día.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function recientes(Builder $query): void
    {
        $query->orderByDesc('fecha')->orderByDesc('id');
    }
}
