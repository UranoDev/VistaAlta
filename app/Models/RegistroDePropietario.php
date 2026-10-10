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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Lo que un propietario declaró en el formulario de `/registro`: quién es, qué
 * lotes dice tener y cómo localizarlo.
 *
 * Es lo que cada propietario declara por su cuenta y **no** el Padrón del control
 * de cuotas (ver `CONTEXT.md`): aquí nadie comprobó nada, no hay cuenta ni
 * sesión, y un lote declarado no es una Unidad. Por eso los lotes viven en su
 * propia tabla (`LoteRegistrado`) y no se mezclan con ese modelo cuando exista.
 *
 * ## Tres cosas distintas que no se mezclan
 *
 * Con uno de los dos medios basta para registrarse, y cada medio que se da se
 * verifica.
 *
 * 1. **El correo verificado** (`correo_verificado_en`): la persona escribió el
 *    código que llegó a ese correo, o tocó su enlace.
 * 2. **El celular verificado** (`telefono_verificado_en`): escribió el código del
 *    SMS. Son dos verificaciones separadas, cada una con su código, y una no
 *    verifica a la otra. Quien dio los dos tiene que verificar los dos.
 * 3. **La validación de la Administración** (`validado_en`): alguien de la
 *    Administración revisó el registro y lo dio por cierto. Verificar prueba que
 *    la persona controla el correo y el celular, no que sea dueña de los lotes;
 *    esa revisión solo la hace la Administración, y solo se puede hacer con todo
 *    lo que la persona dio ya verificado.
 *
 * Ningún campo de verificación ni de validación es asignable en masa.
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
#[Hidden(['correo_codigo_hash', 'correo_enlace_hash', 'telefono_codigo_hash'])]
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
            'correo_expira_en' => 'datetime',
            'correo_enviado_en' => 'datetime',
            'correo_verificado_en' => 'datetime',
            'correo_verificado_por' => MedioDeConfirmacion::class,
            'telefono_expira_en' => 'datetime',
            'telefono_enviado_en' => 'datetime',
            'telefono_verificado_en' => 'datetime',
            'validado_en' => 'datetime',
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    public function correoVerificado(): bool
    {
        return $this->correo_verificado_en !== null;
    }

    public function telefonoVerificado(): bool
    {
        return $this->telefono_verificado_en !== null;
    }

    public function tieneCorreo(): bool
    {
        return filled($this->correo);
    }

    public function tieneTelefono(): bool
    {
        return filled($this->telefono);
    }

    /**
     * Todo lo que la persona dio, verificado: el correo si dio correo y el celular
     * si dio celular. Quien dio los dos tiene que verificar los dos; quien dio uno
     * solo, ese. El formulario no deja registrar sin ninguno.
     */
    public function estaVerificado(): bool
    {
        if (! $this->tieneCorreo() && ! $this->tieneTelefono()) {
            return false;
        }

        return (! $this->tieneCorreo() || $this->correoVerificado())
            && (! $this->tieneTelefono() || $this->telefonoVerificado());
    }

    public function correoVencido(): bool
    {
        return $this->correo_expira_en === null || $this->correo_expira_en->isPast();
    }

    public function telefonoVencido(): bool
    {
        return $this->telefono_expira_en === null || $this->telefono_expira_en->isPast();
    }

    public function estaValidado(): bool
    {
        return $this->validado_en !== null;
    }

    /**
     * La Administración da por cierto el registro. Solo se puede con todo lo que
     * la persona dio ya verificado: validar un registro cuyo contacto no se pudo
     * comprobar no tendría a quién avisarle.
     *
     * @throws LogicException Si falta verificar el correo o el celular que dio.
     */
    public function validar(User $quien, ?string $nota = null): void
    {
        if (! $this->estaVerificado()) {
            throw new LogicException('Un registro se valida cuando ya tiene verificado todo lo que la persona dio.');
        }

        $this->forceFill([
            'validado_en' => now(),
            'validado_por' => $quien->id,
            'validacion_nota' => filled($nota) ? trim($nota) : null,
        ])->save();
    }

    public function quitarValidacion(): void
    {
        $this->forceFill(['validado_en' => null, 'validado_por' => null, 'validacion_nota' => null])->save();
    }

    /**
     * Con todo lo que dieron verificado: el correo si dieron correo y el celular
     * si dieron celular.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function verificados(Builder $query): void
    {
        $query
            ->where(fn (Builder $q): Builder => $q->whereNotNull('correo')->orWhereNotNull('telefono'))
            ->where(fn (Builder $q): Builder => $q->whereNull('correo')->orWhereNotNull('correo_verificado_en'))
            ->where(fn (Builder $q): Builder => $q->whereNull('telefono')->orWhereNotNull('telefono_verificado_en'));
    }

    /**
     * Validados por la Administración.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function validados(Builder $query): void
    {
        $query->whereNotNull('validado_en');
    }

    /**
     * Verificados que la Administración todavía no valida: lo que hay que revisar.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function porValidar(Builder $query): void
    {
        $query->verificados()->whereNull('validado_en');
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
