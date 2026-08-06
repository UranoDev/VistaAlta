<?php

declare(strict_types=1);

namespace App\Support\Vigilancia;

use Carbon\CarbonInterface;

/**
 * Un tramo de guardia: los días de la semana en que **entra**, la hora de
 * entrada y la de salida.
 *
 * El día es el de la **entrada**, nunca el de la salida, y es la única regla que
 * hay que tener presente para llenar la configuración. El turno de noche entra
 * de lunes a sábado a las 22:00 y sale a las 06:00; el domingo a la 01:00 de la
 * madrugada quien está es el del sábado, no el del domingo. Si los días fueran
 * los de salida habría que declararlo de martes a domingo, y el sábado por la
 * noche quedaría descubierto.
 *
 * De ahí que `sale` menor o igual que `entra` signifique que el tramo cruza la
 * medianoche. Iguales —06:00 a 06:00— son 24 horas corridas, que es como está el
 * turno del domingo.
 *
 * Lo que este objeto **no** hace es decir a qué hora entrega. La página no
 * publica horarios (URVA-79, decisión 3): imprimir la hora del relevo equivale a
 * publicar el rol completo, porque quien consulte cuatro veces lo reconstruye.
 * El rótulo que se lee en la tarjeta —«Turno de noche»— lo escribe la
 * configuración y no se deriva de aquí.
 */
final readonly class Turno
{
    /**
     * @param  list<int>  $dias  Días de **entrada**, en ISO-8601: 1 lunes … 7 domingo.
     * @param  string  $entra  Hora de entrada, `HH:MM`.
     * @param  string  $sale  Hora de salida, `HH:MM`. Menor o igual que `$entra` cruza la medianoche.
     */
    public function __construct(
        public array $dias,
        public string $entra,
        public string $sale,
    ) {}

    /**
     * @param  array{dias: list<int>, entra: string, sale: string}  $turno
     */
    public static function desdeArreglo(array $turno): self
    {
        return new self(
            dias: array_values(array_unique($turno['dias'])),
            entra: $turno['entra'],
            sale: $turno['sale'],
        );
    }

    /**
     * Se mira el día del momento y también el anterior: un tramo que cruza la
     * medianoche empezó ayer, y preguntando solo por hoy la madrugada saldría
     * descubierta. Con un día de atraso basta porque ninguno pasa de 24 horas.
     *
     * El intervalo es cerrado a la izquierda y abierto a la derecha. A las 14:00
     * en punto ya está el de la tarde y ya no el de la mañana: sin eso, en el
     * minuto del relevo habría dos de guardia —o, con el criterio al revés,
     * ninguno—, y es justo el instante que la prueba de cobertura revisa.
     */
    public function cubre(CarbonInterface $momento): bool
    {
        foreach ([0, 1] as $diasAtras) {
            $dia = $momento->avoidMutation()->subDays($diasAtras)->startOfDay();

            if (! in_array($dia->dayOfWeekIso, $this->dias, true)) {
                continue;
            }

            $inicio = $dia->setTimeFromTimeString($this->entra);
            $fin = $dia->setTimeFromTimeString($this->sale);

            if ($fin <= $inicio) {
                $fin = $fin->addDay();
            }

            if ($momento >= $inicio && $momento < $fin) {
                return true;
            }
        }

        return false;
    }
}
