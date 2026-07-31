<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Cifra;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
 * **Un reporte cubre siempre un mes**, y los meses se acumulan. Antes era una
 * tabla de un solo renglón —capturar el mes siguiente borraba el anterior—, lo
 * que dejaba a un sitio de rendición de cuentas sin nada que consultar hacia
 * atrás. Hoy `actual()` devuelve el más reciente y los demás siguen en pie, cada
 * uno con su URL; ver `docs/adr/0005`. El índice único sobre `mes` impide que
 * existan dos del mismo mes.
 *
 * Se lee **sin ninguna barrera**: ver `docs/adr/0004`, y `docs/adr/0005` para lo
 * que el histórico le agrega a esa decisión. Si alguien le agrega un login, está
 * revirtiendo una decisión deliberada.
 *
 * @property-read string|null $periodo
 */
#[Fillable(['mes', 'cifras', 'aclaracion', 'hoja_url'])]
class ReporteFinanciero extends Model
{
    protected $table = 'reporte_financiero';

    /**
     * Los meses en español, escritos aquí y no tomados del locale de Carbon. De
     * este nombre salen el título de la página y el rótulo del Periodo, y un
     * despliegue que arranque con el locale en inglés no tiene por qué cambiar
     * cómo se lee la rendición de cuentas.
     */
    private const MESES = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cifras' => 'array',
            // Con formato explícito para que el mes viaje como '2026-06-01' al
            // salir del modelo en arreglo —que es como lo lee el formulario del
            // panel—, en vez de como la fecha ISO con hora que nadie capturó.
            'mes' => 'date:Y-m-d',
        ];
    }

    /**
     * El mes se guarda siempre normalizado al día 1. Un reporte cubre un mes
     * completo, así que el día es ruido: si se colara, dos capturas del mismo
     * mes con día distinto burlarían el índice único y la Asamblea acabaría con
     * dos reportes de junio sin saber cuál vale.
     */
    protected function mes(): Attribute
    {
        return Attribute::set(
            fn (mixed $valor): ?string => blank($valor)
                ? null
                : CarbonImmutable::parse($valor)->startOfMonth()->toDateString(),
        );
    }

    /**
     * La etiqueta legible del tramo que se rinde —«Junio de 2026»—, derivada del
     * mes en vez de capturarse aparte. Cuando era un campo de texto libre podía
     * decir «Abril – Junio 2026» sobre un reporte que en la URL era `2026-06`:
     * el título y la dirección se contradecían y no había cómo saber cuál de los
     * dos mentía.
     */
    protected function periodo(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->mes === null
            ? null
            : ucfirst(self::nombreDelMes($this->mes)));
    }

    public static function nombreDelMes(CarbonInterface $mes): string
    {
        return self::MESES[(int) $mes->month].' de '.$mes->year;
    }

    /**
     * Del mes más reciente al más viejo, que es el orden en que se consulta un
     * archivo: lo vigente arriba.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function recientes(Builder $query): void
    {
        $query->orderByDesc('mes');
    }

    /**
     * El Reporte vigente: el del mes más reciente que se haya capturado, exista
     * el renglón o no. Es el punto por el que se cuelga `/reporte-financiero`, y
     * es lo que hace que el histórico cascadee solo — capturar un mes nuevo
     * empuja al anterior al archivo sin que nadie tenga que moverlo.
     *
     * Mientras nadie haya llenado ninguno no hay renglón, y la lectura devuelve
     * un reporte vacío sin escribir nada: una visita a la página no provoca un
     * INSERT.
     */
    public static function actual(): self
    {
        return static::query()->recientes()->first() ?? new self;
    }

    /**
     * El Reporte de un mes puntual, buscado por como se escribe en la URL
     * (`2026-06`). Devuelve `null` si ese mes no se ha publicado — de eso sale
     * el 404, que es lo honesto: un mes sin reporte no es un reporte vacío.
     */
    public static function delMes(string $mes): ?self
    {
        [$anio, $numero] = array_pad(explode('-', $mes, 2), 2, '');

        if (! ctype_digit($anio) || ! ctype_digit($numero)) {
            return null;
        }

        // El «13» de `2026-13` pasa la restricción de la ruta, que solo cuenta
        // dígitos. Sin este corte, Carbon lo desbordaría a enero de 2027 y esa
        // URL serviría un reporte que no le corresponde.
        if ((int) $numero < 1 || (int) $numero > 12) {
            return null;
        }

        return static::query()
            ->where('mes', sprintf('%04d-%02d-01', (int) $anio, (int) $numero))
            ->first();
    }

    /**
     * Todos los meses publicados, del más reciente al más viejo. Es el índice
     * del archivo: un histórico al que nadie llega es peso muerto.
     *
     * Se traen enteros y sin paginar a propósito — son doce renglones al año, y
     * el resumen de cada uno ya está en su propia columna.
     *
     * @return EloquentCollection<int, self>
     */
    public static function publicados(): EloquentCollection
    {
        return static::query()->recientes()->get();
    }

    /**
     * Si éste es el Reporte que `/reporte-financiero` está publicando. Deja de
     * serlo solo cuando se captura un mes más reciente, no cuando pasa el
     * tiempo: si la Mesa Directiva no ha capturado julio, junio sigue siendo lo
     * vigente.
     */
    public function esVigente(): bool
    {
        return $this->exists && $this->is(static::actual());
    }

    /**
     * El mes como se escribe en su URL: `2026-06`.
     */
    public function mesEnUrl(): ?string
    {
        return $this->mes?->format('Y-m');
    }

    /**
     * La dirección en la que este Reporte se publica, y la única que la página
     * declara como canónica. El vigente vive en la raíz —`/reporte-financiero`,
     * la que se enlaza desde el menú—; los demás, en la suya con fecha. El
     * reporte vacío, el de un sitio donde todavía no se ha capturado nada,
     * también es la raíz: es la página que lo dice.
     */
    public function urlPublica(): string
    {
        return $this->exists && ! $this->esVigente()
            ? route('reporte-financiero.mes', ['mes' => $this->mesEnUrl()])
            : route('reporte-financiero');
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

    /**
     * La aclaración del Periodo: lo que las cifras no dicen solas. Un mes con un
     * ingreso extraordinario infla el remanente, y quien lea el resumen sin ese
     * contexto concluye que ese excedente es lo normal.
     *
     * Es opcional por diseño: la mayoría de los meses no necesitan una, y una
     * aclaración obligatoria terminaría llenándose de relleno.
     */
    public function tieneAclaracion(): bool
    {
        return filled($this->aclaracion);
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
