<?php

declare(strict_types=1);

namespace App\Support\Administracion;

/**
 * Los dos órganos con los que se gobierna la asociación, armados desde
 * `config/contenido.php`.
 *
 * ## Son dos y no uno, y no se parecen
 *
 * El **Comité de Vigilancia** son tres pares: no tiene coordinador ni nadie al
 * frente, y por eso `comite()` devuelve una lista plana. La **Administración**
 * sí tiene cabeza —la Administradora— que en el organigrama cuelga un nivel
 * arriba de los otros tres, así que sale por separado en `cabeza()`.
 *
 * Que el Comité no tenga cabeza es un hecho de cómo está formado, no un dato
 * que falte: si algún día la Asamblea nombra coordinador, se le agrega su
 * `cabeza` a la configuración y el organigrama se dibuja como el otro.
 *
 * ## Que no haya nadie es un estado posible
 *
 * `cabeza()` devuelve `null` si la configuración no la trae. Hoy no pasa, pero
 * el arreglo se llena a mano y entre una renuncia y la Asamblea que la reponga
 * puede haber semanas: la página tiene que poder dibujar la Administración sin
 * cabeza en vez de reventar con un 500.
 */
final readonly class Organos
{
    /**
     * @param  list<Integrante>  $comite
     * @param  list<Integrante>  $integrantes
     */
    private function __construct(
        private array $comite,
        private ?Integrante $cabeza,
        private array $integrantes,
    ) {}

    public static function deLaConfiguracion(): self
    {
        /** @var list<array{nombre: string, cargo: string, hace?: string|null, foto?: string|null}> $comite */
        $comite = config('contenido.administracion.comite', []);

        /** @var array{nombre: string, cargo: string, hace?: string|null, foto?: string|null}|null $cabeza */
        $cabeza = config('contenido.administracion.cabeza');

        /** @var list<array{nombre: string, cargo: string, hace?: string|null, foto?: string|null}> $integrantes */
        $integrantes = config('contenido.administracion.integrantes', []);

        return new self(
            comite: array_map(Integrante::desdeArreglo(...), $comite),
            cabeza: $cabeza === null ? null : Integrante::desdeArreglo($cabeza),
            integrantes: array_map(Integrante::desdeArreglo(...), $integrantes),
        );
    }

    /**
     * @return list<Integrante>
     */
    public function comite(): array
    {
        return $this->comite;
    }

    public function cabeza(): ?Integrante
    {
        return $this->cabeza;
    }

    /**
     * La Administración sin su cabeza: los que van en el renglón de abajo del
     * organigrama.
     *
     * @return list<Integrante>
     */
    public function integrantes(): array
    {
        return $this->integrantes;
    }

    /**
     * Los cuatro cargos de la Administración con su línea de qué hace, en el
     * mismo orden en que se dibujan: primero la cabeza, luego el renglón de
     * abajo de izquierda a derecha.
     *
     * Sale de aquí y no de una llave aparte de la configuración para que el
     * cargo se escriba una sola vez: dos listas paralelas se despegan a la
     * primera renovación de la Mesa.
     *
     * @return list<Integrante>
     */
    public function cargos(): array
    {
        $cargos = $this->cabeza === null ? [] : [$this->cabeza];

        return array_values(array_filter(
            [...$cargos, ...$this->integrantes],
            static fn (Integrante $integrante): bool => filled($integrante->hace),
        ));
    }
}
