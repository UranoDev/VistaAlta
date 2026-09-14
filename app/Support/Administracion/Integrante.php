<?php

declare(strict_types=1);

namespace App\Support\Administracion;

use Illuminate\Support\Str;

/**
 * Una de las siete personas que ocupan un cargo en el fraccionamiento: los tres
 * del Comité de Vigilancia y los cuatro de la Administración.
 *
 * Vive en `config/contenido.php` y no en la base por lo mismo que el
 * `Vigilante`: son siete personas con un cargo, no un catálogo que crezca, y no
 * hay pantalla en el panel que las mantenga.
 *
 * Se parece al `Vigilante` y no es el mismo objeto, con dos diferencias que no
 * conviene borrar juntándolos:
 *
 * - **Aquí el nombre va completo.** Son cargos electos ante la Asamblea, y el
 *   acta constitutiva los asienta así. En Vigilancia es nombre de pila e
 *   inicial justamente para no volver buscable a quien no pidió serlo.
 * - **No hay turnos ni reloj.** Un integrante no está «de guardia»: ocupa un
 *   cargo hasta que la Asamblea lo renueve.
 *
 * Lo que sí se comparte es la regla de la foto: es opcional por persona, y quien
 * no la pone se dibuja con su monograma sin que la tarjeta se vea incompleta.
 */
final readonly class Integrante
{
    /**
     * @param  string  $nombre  Nombre completo, como lo va a asentar el acta.
     * @param  string  $cargo  El rótulo del cargo tal como se lee en la tarjeta.
     * @param  string|null  $hace  Qué hace ese cargo, en un renglón. `null` cuando el órgano lo explica entero.
     * @param  string|null  $foto  Archivo dentro de `public/img/administracion/`, o `null` para el monograma.
     */
    public function __construct(
        public string $nombre,
        public string $cargo,
        public ?string $hace,
        public ?string $foto,
    ) {}

    /**
     * @param  array{nombre: string, cargo: string, hace?: string|null, foto?: string|null}  $integrante
     */
    public static function desdeArreglo(array $integrante): self
    {
        return new self(
            nombre: $integrante['nombre'],
            cargo: $integrante['cargo'],
            hace: $integrante['hace'] ?? null,
            foto: $integrante['foto'] ?? null,
        );
    }

    public function tieneFoto(): bool
    {
        return filled($this->foto);
    }

    /**
     * Con qué se dibuja la tarjeta cuando no hay foto: la inicial del nombre y
     * la del primer apellido.
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
