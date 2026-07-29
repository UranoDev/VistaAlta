<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Un renglón del resumen del Reporte financiero: un concepto y su monto.
 *
 * No es un asiento contable ni se suma con nada. Es una cifra que la Mesa
 * Directiva capturó a mano para que la Asamblea la lea de un vistazo; el
 * desglose que la sustenta vive en la hoja de cálculo de Google y no se copia
 * aquí. Por eso el objeto solo sabe presentarse.
 */
final readonly class Cifra
{
    public function __construct(
        public string $concepto,
        public float $monto,
        /** El renglón del total, que en un comprobante va destacado del resto. */
        public bool $destacada = false,
    ) {}

    /**
     * Construye la cifra a partir de un renglón del JSON, o devuelve `null` si
     * ese renglón quedó incompleto. Un resumen a medio capturar no debería
     * tumbar la página pública: el renglón roto simplemente no se muestra.
     *
     * @param  array<string, mixed>  $renglon
     */
    public static function desdeArreglo(mixed $renglon): ?self
    {
        if (! is_array($renglon)) {
            return null;
        }

        $concepto = trim((string) ($renglon['concepto'] ?? ''));

        if ($concepto === '' || ! is_numeric($renglon['monto'] ?? null)) {
            return null;
        }

        return new self(
            concepto: $concepto,
            monto: (float) $renglon['monto'],
            destacada: (bool) ($renglon['destacada'] ?? false),
        );
    }

    /**
     * Pesos con separador de miles y dos decimales. El signo va delante del
     * peso —`-$1,234.56`— y se usa el guion normal, no el menos tipográfico,
     * porque la columna se alinea con cifras tabulares y solo el guion tiene el
     * ancho de un dígito.
     */
    public function montoFormateado(): string
    {
        return ($this->monto < 0 ? '-' : '').'$'.number_format(abs($this->monto), 2);
    }
}
