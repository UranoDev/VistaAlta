<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Calle;
use Database\Factories\SolicitudDeInternetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Un domicilio en la lista de espera para la instalación de internet.
 *
 * La lista es por **propiedad**: calle y número oficial, o calle, manzana y lote
 * (o los tres). Lo que la persona no da se guarda como cadena vacía y no como
 * nulo, porque el índice único no distingue dos NULL y sí dos vacíos. Dos
 * personas que den formas distintas de la misma propiedad —una el número, otra
 * manzana y lote— no se reconocen como repetidas: no hay forma de saberlo. El
 * celular es solo cómo localizar a quien la pidió, y por eso se puede repetir:
 * quien tiene tres propiedades anota las tres con el mismo celular y recibe tres
 * folios.
 *
 * Que un domicilio ya esté en la lista no es un error de quien lo anota otra vez,
 * sino un dato: `anotar()` devuelve la solicitud que ya existía y no crea otra.
 * La tabla no sabe si el domicilio existe en el fraccionamiento (no hay padrón
 * al que preguntarle); solo sabe si ya está en la lista.
 */
#[Fillable(['calle', 'numero_oficial', 'manzana', 'lote', 'celular'])]
class SolicitudDeInternet extends Model
{
    /** @use HasFactory<SolicitudDeInternetFactory> */
    use HasFactory;

    protected $table = 'solicitudes_de_internet';

    /** Cuántas veces se reintenta si dos personas piden folio al mismo tiempo. */
    private const INTENTOS = 5;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'calle' => Calle::class,
            'numero' => 'integer',
        ];
    }

    /**
     * El folio como se le da a la persona: INT-001, INT-002… A partir de mil
     * deja de rellenar con ceros (INT-1000), no se trunca.
     */
    public function folio(): string
    {
        return 'INT-'.str_pad((string) $this->numero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * El domicilio como se le lee a la persona: «Margarita 128, manzana 4, lote
     * 12». Lo que no dio no aparece.
     */
    public function domicilio(): string
    {
        $partes = [trim($this->calle->value.' '.$this->numero_oficial)];

        if ($this->manzana !== '') {
            $partes[] = 'manzana '.$this->manzana;
        }

        if ($this->lote !== '') {
            $partes[] = 'lote '.$this->lote;
        }

        return implode(', ', $partes);
    }

    /**
     * Anota el domicilio en la lista y le da su folio, o devuelve la solicitud
     * que ya lo tenía (`wasRecentlyCreated` dice cuál de las dos pasó).
     *
     * El siguiente número se calcula dentro de la transacción que guarda, y el
     * índice único de `numero` cubre a dos personas que lo piden a la vez: la
     * segunda choca, y reintenta con el número que sigue. Si lo que chocó fue el
     * domicilio, el reintento ya lo encuentra.
     *
     * @param  array{calle: string, numero_oficial: string, manzana: string, lote: string}  $domicilio
     */
    public static function anotar(array $domicilio, string $celular): self
    {
        $domicilio = self::normalizar($domicilio);

        for ($intento = 0; $intento < self::INTENTOS; $intento++) {
            try {
                return DB::transaction(function () use ($domicilio, $celular): self {
                    $existente = self::query()->where($domicilio)->first();

                    if ($existente !== null) {
                        return $existente;
                    }

                    $solicitud = new self([...$domicilio, 'celular' => $celular]);
                    $solicitud->numero = (int) self::query()->max('numero') + 1;
                    $solicitud->save();

                    return $solicitud;
                });
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }

        throw new RuntimeException('No se pudo asignar un folio de internet.');
    }

    /**
     * La solicitud de un domicilio, si ya está en la lista.
     *
     * @param  array{calle: string, numero_oficial: string, manzana: string, lote: string}  $domicilio
     */
    public static function delDomicilio(array $domicilio): ?self
    {
        return self::query()->where(self::normalizar($domicilio))->first();
    }

    /**
     * Número oficial, manzana y lote en mayúsculas y sin espacios, para que
     * «12a», «12A» y «12 A» sean el mismo domicilio.
     *
     * @param  array{calle: string, numero_oficial: string, manzana: string, lote: string}  $domicilio
     * @return array{calle: string, numero_oficial: string, manzana: string, lote: string}
     */
    public static function normalizar(array $domicilio): array
    {
        $limpiar = fn (string $valor): string => mb_strtoupper((string) preg_replace('/\s+/u', '', $valor));

        return [
            'calle' => $domicilio['calle'],
            'numero_oficial' => $limpiar($domicilio['numero_oficial']),
            'manzana' => $limpiar($domicilio['manzana']),
            'lote' => $limpiar($domicilio['lote']),
        ];
    }
}
