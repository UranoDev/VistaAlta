<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Cifra;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * La rendición de cuentas económica de un Periodo: un resumen de cifras que el
 * sitio muestra, más el enlace a la hoja de cálculo de Google donde vive el
 * detalle.
 *
 * El resumen existe para ser mostrado; la hoja es la fuente de verdad y **no se
 * copia aquí**. Nada en esta clase lee la hoja, la importa ni la suma: las
 * cifras las captura la Mesa Directiva desde su panel, y si la hoja cambia,
 * cambiarlas aquí es un acto deliberado suyo.
 *
 * Es una tabla de un solo renglón, como el interruptor de Recepción de
 * comentarios: el sitio publica el Reporte de un Periodo a la vez. Mientras
 * nadie lo haya llenado no hay renglón, y la lectura devuelve un reporte vacío
 * sin escribir nada — una visita a la página no provoca un INSERT.
 *
 * Se lee **sin ninguna barrera**: ver `docs/adr/0004`. Si alguien le agrega un
 * login, está revirtiendo una decisión deliberada.
 */
#[Fillable(['periodo', 'cifras', 'hoja_url'])]
class ReporteFinanciero extends Model
{
    protected $table = 'reporte_financiero';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cifras' => 'array',
        ];
    }

    /**
     * El reporte del Periodo en curso, exista el renglón o no. Es el punto por
     * el que se cuelgan tanto la página pública como la pantalla del panel.
     */
    public static function actual(): self
    {
        return static::query()->first() ?? new self;
    }

    /**
     * El resumen de cifras, en el orden en que la Mesa Directiva las capturó
     * —el de un comprobante, donde el total va al final—. Los renglones que
     * quedaron a medias se descartan en vez de romper la página.
     *
     * @return Collection<int, Cifra>
     */
    public function resumen(): Collection
    {
        return collect($this->cifras ?? [])
            ->map(fn (mixed $renglon): ?Cifra => Cifra::desdeArreglo($renglon))
            ->filter()
            ->values();
    }

    public function tieneResumen(): bool
    {
        return $this->resumen()->isNotEmpty();
    }

    public function tieneHoja(): bool
    {
        return filled($this->hoja_url);
    }

    public function estaVacio(): bool
    {
        return ! $this->tieneResumen() && ! $this->tieneHoja();
    }

    /**
     * El dominio de la hoja, para que el enlace diga a dónde lleva antes de que
     * alguien lo abra. Es la mitad del criterio «se ve claro que sale del
     * sitio»; la otra mitad es que abra en pestaña nueva.
     */
    public function dominioDeLaHoja(): ?string
    {
        if (! $this->tieneHoja()) {
            return null;
        }

        return parse_url((string) $this->hoja_url, PHP_URL_HOST) ?: null;
    }
}
