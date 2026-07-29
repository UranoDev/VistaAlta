<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * El interruptor que la Mesa Directiva controla desde su panel para dejar de
 * admitir Comentarios nuevos.
 *
 * Nace abierta y así se queda hasta que alguien la cierre. Cerrarla no oculta
 * nada de lo ya publicado: solo retira el formulario y hace que el envío se
 * rechace. No confundirlo con la Cola de moderación — ésta decide *si se puede
 * escribir*, la cola decide *qué se publica*.
 *
 * Es una tabla de un solo renglón. Mientras nadie lo haya tocado no hay
 * renglón, y la lectura responde "abierta" por omisión: así el sitio arranca
 * admitiendo comentarios sin depender de que se haya sembrado nada.
 *
 * Lo mueve el panel de la Mesa Directiva, desde el encabezado de la pantalla de
 * Comentarios (URVA-7, fundida en URVA-14); aquí está solo el interruptor y su
 * lectura, que es lo que la página de la Propuesta necesita para obedecerlo.
 */
class RecepcionDeComentarios extends Model
{
    protected $table = 'recepcion_de_comentarios';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abierta' => 'boolean',
        ];
    }

    /**
     * Lectura barata y sin efectos: una página pública no debería escribir en la
     * base solo por preguntar si el formulario va.
     */
    public static function estaAbierta(): bool
    {
        return (bool) (static::query()->value('abierta') ?? true);
    }

    public static function abrir(): void
    {
        static::mover(true);
    }

    public static function cerrar(): void
    {
        static::mover(false);
    }

    /**
     * El renglón único, ya exista o no. Es el punto por el que el panel se
     * cuelga del interruptor.
     *
     * `abierta` no es asignable en masa a propósito: el interruptor solo se
     * mueve por abrir()/cerrar(), nunca con datos de una petición.
     */
    public static function interruptor(): self
    {
        $interruptor = static::query()->first();

        if ($interruptor === null) {
            $interruptor = new self;
            $interruptor->abierta = true;
        }

        return $interruptor;
    }

    private static function mover(bool $abierta): void
    {
        $interruptor = static::interruptor();
        $interruptor->abierta = $abierta;
        $interruptor->save();
    }
}
