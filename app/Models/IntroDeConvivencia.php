<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * El texto que encabeza el índice de `/convivencia` y explica para qué es la
 * sección.
 *
 * Es una tabla de un solo renglón, con el mismo trato que `ViaDeRecepcion` y
 * `RecepcionDeComentarios`: mientras nadie lo haya capturado no hay renglón, y
 * la lectura responde `null` sin escribir nada. Una página pública no debería
 * escribir en la base solo por preguntar si hay introducción.
 *
 * Vacío es un estado legítimo y no un pendiente: el índice se dibuja sin
 * introducción, con los posts empezando directo. No hay texto de relleno a
 * propósito —lo que la Asamblea lea ahí lo escribe la Mesa Directiva o no lo
 * escribe nadie.
 *
 * `texto` no es asignable en masa: se mueve por `cambiar()`, desde el
 * encabezado de la pantalla de Convivencia del panel.
 */
class IntroDeConvivencia extends Model
{
    protected $table = 'intro_de_convivencia';

    /**
     * Lo capturado, o `null` si todavía no hay nada. Un texto en blanco cuenta
     * como nada: quien borra la introducción desde el panel la está quitando,
     * no dejando una de espacios.
     */
    public static function texto(): ?string
    {
        $texto = trim((string) static::query()->value('texto'));

        return $texto === '' ? null : $texto;
    }

    /**
     * El renglón único, ya exista o no. Es el punto por el que el panel se
     * cuelga de la introducción.
     */
    public static function actual(): self
    {
        return static::query()->first() ?? new self;
    }

    public static function cambiar(?string $texto): void
    {
        $intro = static::actual();
        $intro->texto = blank($texto) ? null : trim($texto);
        $intro->save();
    }
}
