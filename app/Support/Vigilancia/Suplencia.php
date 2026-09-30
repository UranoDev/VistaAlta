<?php

declare(strict_types=1);

namespace App\Support\Vigilancia;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Los días en que alguien cubre los turnos de otro: un descanso, unas
 * vacaciones, una incapacidad.
 *
 * Es una capa encima del rol y no una edición del rol. Los turnos de
 * `config/contenido.php` son la semana normal y no se tocan: mientras la
 * suplencia dura, `RolDeVigilancia` anuncia al suplente donde habría anunciado
 * al ausente, y cuando termina el rol vuelve solo, sin nada que deshacer. Por
 * eso caduca sola y por eso no hay que acordarse de reponer nada.
 *
 * ## Los días son de entrada, como en `Turno`
 *
 * `desde` cuenta como el primer día y `dias` incluye ese primero. Lo que se
 * compara es el día en que **entró** el turno, no el día de la hora que se
 * consulta: el turno de noche que entra el último día se cubre entero, hasta
 * que sale a la mañana siguiente, en vez de partirse a la medianoche con el
 * ausente terminando lo que el suplente ya no cubre. Es la misma regla que
 * hace que el domingo a la 01:00 esté quien entró el sábado.
 *
 * ## Qué no hace
 *
 * No dice por qué falta nadie ni lo publica: la página solo cambia a quién
 * anuncia en «En este momento». Unas fechas de ausencia son la situación
 * laboral de una persona identificada, y saber cuándo el acceso queda con otra
 * es parte del rol que la página se niega a imprimir (URVA-79).
 */
final readonly class Suplencia
{
    /**
     * @param  string  $ausente  Nombre tal como está en la configuración de vigilantes.
     * @param  string  $cubre  Quien lo suple, con el nombre tal como está en la configuración.
     * @param  string  $desde  `AAAA-MM-DD`. Ese día cuenta como el primero.
     * @param  int  $dias  Cuántos días dura, contando el primero.
     */
    public function __construct(
        public string $ausente,
        public string $cubre,
        public string $desde,
        public int $dias,
    ) {}

    /**
     * Tolerante a propósito: la suplencia se escribe a mano en el `.env` del
     * servidor, y un renglón incompleto no debe tumbar la página pública. Uno
     * sin datos suficientes simplemente no alcanza a nadie.
     *
     * @param  array<string, mixed>  $suplencia
     */
    public static function desdeArreglo(array $suplencia): self
    {
        return new self(
            ausente: (string) ($suplencia['ausente'] ?? ''),
            cubre: (string) ($suplencia['cubre'] ?? ''),
            desde: (string) ($suplencia['desde'] ?? ''),
            dias: (int) ($suplencia['dias'] ?? 0),
        );
    }

    /**
     * Si el turno de `$nombre` que entró en `$entrada` es de los que se cubren.
     */
    public function alcanzaA(string $nombre, CarbonInterface $entrada): bool
    {
        if ($this->dias < 1 || $nombre === '' || $nombre !== $this->ausente) {
            return false;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->desde) !== 1) {
            return false;
        }

        $dia = $entrada->toDateString();

        return $dia >= $this->desde && $dia <= $this->ultimoDia();
    }

    /**
     * El último día que se cubre, ya incluido: `desde` más `dias - 1`.
     */
    public function ultimoDia(): string
    {
        return CarbonImmutable::parse($this->desde)->addDays($this->dias - 1)->toDateString();
    }
}
