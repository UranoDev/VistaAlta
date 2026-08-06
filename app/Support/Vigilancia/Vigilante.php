<?php

declare(strict_types=1);

namespace App\Support\Vigilancia;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Una de las personas que hacen la vigilancia del fraccionamiento.
 *
 * Vive en `config/contenido.php` y no en la base por la misma razón que las
 * preguntas frecuentes: son cuatro personas con un horario fijo, no un catálogo
 * que crezca, y no hay pantalla en el panel que las mantenga. El día que haya
 * que capturar suplencias esto se muda a la base y la clase se queda igual.
 *
 * Tres decisiones de URVA-79 que este objeto sostiene, y que conviene no
 * deshacer por conveniencia:
 *
 * - **Nombre de pila e inicial**, nunca apellido completo. Lo que le sirve al
 *   colono para reconocer a la persona en el acceso es la cara; el apellido no
 *   aporta nada operativo y sí vuelve buscable a alguien que no pidió serlo.
 * - **La foto es opcional, por persona.** Quien no quiera aparecer se dibuja con
 *   sus iniciales y la tarjeta no se ve incompleta ni distinta de las demás.
 * - **El rótulo del turno lo escribe la configuración** —«Turno de noche»— y no
 *   se deriva de las horas, porque las horas no se publican.
 */
final readonly class Vigilante
{
    /**
     * @param  string  $nombre  Nombre de pila e inicial. Nunca el apellido completo.
     * @param  string|null  $foto  Archivo dentro de `public/img/vigilantes/`, o `null` para el monograma.
     * @param  string|null  $desde  Fecha de incorporación, `AAAA-MM-DD`, cuando valga la pena decirla.
     * @param  list<Turno>  $turnos
     */
    public function __construct(
        public string $nombre,
        public string $etiqueta,
        public ?string $foto,
        public ?string $desde,
        public array $turnos,
    ) {}

    /**
     * @param  array{nombre: string, etiqueta: string, foto?: string|null, desde?: string|null, turnos: list<array{dias: list<int>, entra: string, sale: string}>}  $vigilante
     */
    public static function desdeArreglo(array $vigilante): self
    {
        return new self(
            nombre: $vigilante['nombre'],
            etiqueta: $vigilante['etiqueta'],
            foto: $vigilante['foto'] ?? null,
            desde: $vigilante['desde'] ?? null,
            turnos: array_map(Turno::desdeArreglo(...), $vigilante['turnos']),
        );
    }

    public function estaDeGuardia(CarbonInterface $momento): bool
    {
        foreach ($this->turnos as $turno) {
            if ($turno->cubre($momento)) {
                return true;
            }
        }

        return false;
    }

    public function tieneFoto(): bool
    {
        return filled($this->foto);
    }

    /**
     * Cuándo se incorporó, ya como fecha. La vista solo la formatea: parsear una
     * cadena de configuración dentro del blade deja el manejo de un dato mal
     * capturado en el peor lugar posible para que se note.
     */
    public function incorporacion(): ?CarbonImmutable
    {
        return $this->desde === null ? null : CarbonImmutable::parse($this->desde);
    }

    /**
     * Con qué se dibuja la tarjeta cuando no hay foto: dos iniciales si el
     * nombre trae inicial de apellido, una si no.
     */
    public function iniciales(): string
    {
        $palabras = preg_split('/\s+/u', trim($this->nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return Str::upper(collect($palabras)
            ->take(2)
            ->map(static fn (string $palabra): string => Str::substr($palabra, 0, 1))
            ->implode(''));
    }
}
