<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Calle;
use App\Enums\SituacionDelLote;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un lote tal como lo declaró su propietario: calle y número oficial, manzana y
 * lote, y cómo está. Ver `RegistroDePropietario` para por qué no es una Unidad.
 */
#[Fillable(['calle', 'numero_oficial', 'manzana', 'lote', 'situacion'])]
class LoteRegistrado extends Model
{
    protected $table = 'lotes_registrados';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'calle' => Calle::class,
            'situacion' => SituacionDelLote::class,
        ];
    }

    /**
     * @return BelongsTo<RegistroDePropietario, $this>
     */
    public function registro(): BelongsTo
    {
        return $this->belongsTo(RegistroDePropietario::class, 'registro_de_propietario_id');
    }

    /**
     * Cómo se nombra el lote en listas y búsquedas: «Margarita 128».
     */
    public function etiqueta(): string
    {
        return $this->calle->value.' '.$this->numero_oficial;
    }
}
