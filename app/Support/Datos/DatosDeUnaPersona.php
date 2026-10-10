<?php

declare(strict_types=1);

namespace App\Support\Datos;

/**
 * Todo lo que el sitio tiene de una persona, reunido para entregárselo.
 *
 * Es un resultado, no un modelo: lo arma `ReunirDatosDeUnaPersona` y lo consumen
 * `ArchivoDeEntrega` (que lo escribe) y el comando `datos:entregar` (que lo manda).
 */
final readonly class DatosDeUnaPersona
{
    /**
     * @param  'correo'|'celular'  $tipo  Cómo se identificó a la persona.
     * @param  string  $valor  El correo (en minúsculas) o el celular (a diez dígitos).
     * @param  list<string>  $correos  Los correos que se consideran suyos: el que se dio y los que se verificaron junto con él.
     * @param  list<string>  $telefonos  Igual, con los celulares.
     * @param  list<string>  $correosVerificadosDelCelular  Cuando se identificó por celular, a qué correos verificados se le puede mandar.
     * @param  array<string, array{titulo: string, filas: list<array<string, mixed>>}>  $fuentes  Cada lugar donde hay datos suyos.
     */
    public function __construct(
        public string $tipo,
        public string $valor,
        public array $correos,
        public array $telefonos,
        public array $correosVerificadosDelCelular,
        public array $fuentes,
    ) {}

    /** Cuántos renglones de datos hay en total. */
    public function total(): int
    {
        return array_sum(array_map(fn (array $fuente): int => count($fuente['filas']), $this->fuentes));
    }

    public function estaVacio(): bool
    {
        return $this->total() === 0;
    }

    /**
     * A dónde mandar el archivo si el operador no dice otra cosa.
     *
     * - Si se identificó por correo, a ese mismo correo: quien pide sus datos los
     *   recibe donde dijo que estaba.
     * - Si se identificó por celular, solo a un correo que **se verificó junto con
     *   ese celular**. Un correo escrito por otra persona en un registro con ese
     *   celular no sirve: mandarle ahí los datos sería entregárselos a un
     *   desconocido. Si no hay exactamente uno, no se adivina.
     */
    public function correoDeEntrega(): ?string
    {
        if ($this->tipo === 'correo') {
            return $this->valor;
        }

        $correos = array_values(array_unique($this->correosVerificadosDelCelular));

        return count($correos) === 1 ? $correos[0] : null;
    }
}
