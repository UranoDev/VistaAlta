<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MedioDeConfirmacion;
use Database\Factories\RegistroDePropietarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Lo que un propietario declaró en el formulario de `/registro`: quién es, qué
 * lotes dice tener y cómo localizarlo.
 *
 * Es lo que cada propietario declara por su cuenta y **no** el Padrón del control
 * de cuotas (ver `CONTEXT.md`): aquí nadie comprobó nada, no hay cuenta ni
 * sesión, y un lote declarado no es una Unidad. Por eso los lotes viven en su
 * propia tabla (`LoteRegistrado`) y no se mezclan con ese modelo cuando exista.
 *
 * ## Confirmado
 *
 * Un registro nace sin confirmar. `confirmado_en` se llena cuando su dueño escribe
 * el código que le llegó por correo o por WhatsApp, o toca el enlace del correo
 * (ver `ConfirmacionDelRegistro`). Confirmado quiere decir que esa persona
 * controla el correo o el teléfono que dio; no que sea dueña de los lotes.
 * Ningún campo de confirmación es asignable en masa.
 *
 * Se crea de un solo golpe con `registrar()`: el registro, sus lotes y sus
 * contactos entran juntos o no entran, para que el panel nunca muestre un
 * propietario sin lotes.
 */
#[Fillable([
    'nombre', 'telefono', 'correo',
    'emergencia_nombre', 'emergencia_telefono', 'residentes',
    'aceptado_en', 'aviso_version',
])]
#[Hidden(['confirmacion_codigo_hash', 'confirmacion_enlace_hash'])]
class RegistroDePropietario extends Model
{
    /** @use HasFactory<RegistroDePropietarioFactory> */
    use HasFactory;

    protected $table = 'registros_de_propietarios';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aceptado_en' => 'datetime',
            'residentes' => 'integer',
            'confirmacion_expira_en' => 'datetime',
            'confirmacion_enviada_en' => 'datetime',
            'confirmado_en' => 'datetime',
            'confirmado_por' => MedioDeConfirmacion::class,
        ];
    }

    /**
     * @return HasMany<LoteRegistrado, $this>
     */
    public function lotes(): HasMany
    {
        return $this->hasMany(LoteRegistrado::class)->orderBy('calle')->orderBy('numero_oficial');
    }

    /**
     * @return HasMany<ContactoDelRegistro, $this>
     */
    public function contactos(): HasMany
    {
        return $this->hasMany(ContactoDelRegistro::class)->orderBy('id');
    }

    public function estaConfirmado(): bool
    {
        return $this->confirmado_en !== null;
    }

    public function confirmacionVencida(): bool
    {
        return $this->confirmacion_expira_en === null || $this->confirmacion_expira_en->isPast();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function confirmados(Builder $query): void
    {
        $query->whereNotNull('confirmado_en');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function sinConfirmar(Builder $query): void
    {
        $query->whereNull('confirmado_en');
    }

    /**
     * Guarda el registro con sus lotes y sus contactos adicionales.
     *
     * @param  array<string, mixed>  $propietario  Los campos del propietario ya validados.
     * @param  list<array<string, mixed>>  $lotes
     * @param  list<array<string, mixed>>  $contactos
     */
    public static function registrar(array $propietario, array $lotes, array $contactos = []): self
    {
        return DB::transaction(function () use ($propietario, $lotes, $contactos): self {
            $registro = self::query()->create($propietario);

            $registro->lotes()->createMany($lotes);

            if ($contactos !== []) {
                $registro->contactos()->createMany($contactos);
            }

            return $registro;
        });
    }
}
