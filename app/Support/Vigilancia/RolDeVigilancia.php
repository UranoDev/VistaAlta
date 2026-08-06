<?php

declare(strict_types=1);

namespace App\Support\Vigilancia;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * El rol completo de guardias: las cuatro personas y quién está en el acceso a
 * una hora dada.
 *
 * ## La hora la pone el fraccionamiento, no el visitante
 *
 * `config/app.php` deja la aplicación en UTC, así que preguntar `now()` a secas
 * contestaría con una hora que no es la del acceso, y a media tarde eso ya
 * cambia de turno. La zona sale de `contenido.vigilancia.zona_horaria`.
 *
 * Tampoco se usa el reloj del visitante. El turno es un hecho del
 * fraccionamiento: quien consulte la página desde un teléfono con la hora mal
 * puesta —o desde otro país— tiene que ver quién está *aquí*, no quién estaría
 * si el acceso siguiera su reloj.
 *
 * ## Que no haya nadie es un estado posible
 *
 * `deGuardia()` devuelve `null` cuando ningún turno cubre el momento. Hoy no
 * puede pasar —los cuatro turnos cubren la semana entera, y hay una prueba que
 * barre los siete días para exigirlo—, pero la configuración se llena a mano y
 * el día que alguien recorte un horario la página tiene que decir que no sabe,
 * en vez de inventar a alguien o reventar con un 500.
 */
final class RolDeVigilancia
{
    /**
     * @param  list<Vigilante>  $vigilantes
     */
    private function __construct(private array $vigilantes) {}

    public static function deLaConfiguracion(): self
    {
        /** @var list<array{nombre: string, etiqueta: string, foto?: string|null, desde?: string|null, turnos: list<array{dias: list<int>, entra: string, sale: string}>}> $configurados */
        $configurados = config('contenido.vigilancia.vigilantes', []);

        return new self(array_map(Vigilante::desdeArreglo(...), $configurados));
    }

    /**
     * @return list<Vigilante>
     */
    public function vigilantes(): array
    {
        return $this->vigilantes;
    }

    public function deGuardia(CarbonInterface $momento): ?Vigilante
    {
        foreach ($this->vigilantes as $vigilante) {
            if ($vigilante->estaDeGuardia($momento)) {
                return $vigilante;
            }
        }

        return null;
    }

    /**
     * El momento presente en la hora del acceso.
     */
    public static function ahora(): CarbonImmutable
    {
        return CarbonImmutable::now(config('contenido.vigilancia.zona_horaria'));
    }
}
