<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * Lo que se publicó hace poco y todavía se señala como nuevo en la página.
 *
 * Lo comparten `Actividad` y `Pendiente` porque las dos mitades de
 * `/actividades` tienen el mismo problema: la página se lee de corrido, crece
 * por arriba y no hay nada que le diga a quien vuelve cada tantas semanas qué
 * apareció desde la última vez.
 *
 * ## Se mide con `created_at`, no con la fecha de la Actividad
 *
 * Son cosas distintas y la diferencia importa. La fecha de una Actividad dice
 * cuándo ocurrió; `created_at` dice cuándo se capturó. Algo que pasó en junio y
 * se captura hoy es novedad para el lector aunque su fecha sea vieja — y al
 * revés, volver a guardar una entrada vieja no debería revivirla, que es la
 * razón de no usar `updated_at`: una corrección de dedo la volvería a anunciar.
 *
 * ## Caduca sola
 *
 * La ventana sale de `contenido.novedades.dias`. Que se apague sola es el punto:
 * una marca que hay que ir a quitar a mano se queda puesta para siempre, y una
 * página donde todo está marcado como nuevo no marca nada.
 */
trait SeMarcaComoNuevo
{
    public function esNuevo(): bool
    {
        $capturado = $this->created_at;

        if ($capturado === null) {
            return false;
        }

        $dias = (int) config('contenido.novedades.dias', 0);

        if ($dias <= 0) {
            return false;
        }

        return $capturado->greaterThanOrEqualTo(Carbon::now()->subDays($dias));
    }
}
