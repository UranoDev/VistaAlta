<?php

declare(strict_types=1);

namespace App\Support\Vigilancia;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * El rol completo de guardias: las personas y quién está en el acceso a una hora
 * dada.
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
 * ## Un descanso no reescribe el rol
 *
 * Cuando alguien falta unos días, sus turnos los cubre otro durante ese tramo
 * (`Suplencia`). El rol de `vigilantes()` no cambia —las tarjetas siguen siendo
 * las mismas personas con los mismos rótulos— y lo único que se mueve es a quién
 * anuncia `deGuardia()`: al suplente, con el rótulo del turno que cubre.
 *
 * Si la suplencia nombra a alguien que no está en la configuración, `deGuardia()`
 * devuelve `null` y la página dice que no sabe. Anunciar al ausente sería peor:
 * es dar el nombre y la cara de quien sabemos que no está en el acceso.
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
     * @param  list<Suplencia>  $suplencias
     */
    private function __construct(private array $vigilantes, private array $suplencias = []) {}

    public static function deLaConfiguracion(): self
    {
        /** @var list<array{nombre: string, etiqueta: string, foto?: string|null, desde?: string|null, turnos: list<array{dias: list<int>, entra: string, sale: string}>}> $configurados */
        $configurados = config('contenido.vigilancia.vigilantes', []);

        return new self(
            array_map(Vigilante::desdeArreglo(...), $configurados),
            self::suplenciasDeLaConfiguracion(),
        );
    }

    /**
     * Las suplencias vienen del `.env` y no de `config/contenido.php`: son unas
     * fechas de ausencia, el repositorio es público y su historial no se depura.
     * Por eso llegan como texto JSON; en las pruebas llegan ya como arreglo.
     *
     * Un valor que no se puede leer se reporta y se ignora, y el rol sale sin
     * suplencias. Es la opción menos mala: tumbar `/vigilancia` por un error de
     * dedo en una variable opcional dejaría a todo el fraccionamiento sin saber
     * quién cuida, y el error queda en el registro para quien lo busque.
     *
     * @return list<Suplencia>
     */
    private static function suplenciasDeLaConfiguracion(): array
    {
        $configuradas = config('contenido.vigilancia.suplencias');

        if ($configuradas === null) {
            return [];
        }

        // `json_decode` devuelve `null` tanto para el texto `null` como para uno
        // ilegible, así que la lectura se comprueba aparte: si no, un JSON roto
        // pasaría por «nadie falta» y el error se perdería en silencio.
        $legible = true;

        if (is_string($configuradas)) {
            if (trim($configuradas) === '') {
                return [];
            }

            $configuradas = json_decode($configuradas, true);
            $legible = json_last_error() === JSON_ERROR_NONE;
        }

        if (! $legible || ! is_array($configuradas)) {
            report(new InvalidArgumentException(
                'VIGILANCIA_SUPLENCIAS no es un JSON válido: se ignora y el rol sale sin suplencias.'
            ));

            return [];
        }

        return array_map(
            static fn (mixed $suplencia): Suplencia => Suplencia::desdeArreglo(is_array($suplencia) ? $suplencia : []),
            array_values($configuradas),
        );
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
            $entrada = $vigilante->entradaDelTurno($momento);

            if ($entrada === null) {
                continue;
            }

            foreach ($this->suplencias as $suplencia) {
                if ($suplencia->alcanzaA($vigilante->nombre, $entrada)) {
                    return $this->buscar($suplencia->cubre)?->conEtiqueta($vigilante->etiqueta);
                }
            }

            return $vigilante;
        }

        return null;
    }

    private function buscar(string $nombre): ?Vigilante
    {
        foreach ($this->vigilantes as $vigilante) {
            if ($vigilante->nombre === $nombre) {
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
