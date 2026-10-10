<?php

namespace Database\Factories;

use App\Enums\Calle;
use App\Enums\MedioDeConfirmacion;
use App\Enums\SituacionDelLote;
use App\Models\RegistroDePropietario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistroDePropietario>
 */
class RegistroDePropietarioFactory extends Factory
{
    protected $model = RegistroDePropietario::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'telefono' => '55'.fake()->numerify('########'),
            'correo' => fake()->unique()->safeEmail(),
            'emergencia_nombre' => null,
            'emergencia_telefono' => null,
            'aceptado_en' => now(),
            'aviso_version' => '13 de septiembre de 2026',
        ];
    }

    /**
     * Un registro siempre tiene al menos un lote: es lo mínimo que el formulario
     * deja enviar. Si no se pide ninguno con `conLote()`, queda un lote de relleno
     * (manzana 0), fijo a propósito: uno al azar podía coincidir con la calle que
     * una prueba busca y volverla intermitente.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (RegistroDePropietario $registro): void {
            if ($registro->lotes()->doesntExist()) {
                $registro->lotes()->create([
                    'calle' => Calle::Clavel,
                    'numero_oficial' => '999',
                    'manzana' => '0',
                    'lote' => '0',
                    'situacion' => SituacionDelLote::Terreno,
                ]);
            }
        });
    }

    /**
     * Un lote concreto, para las pruebas de búsqueda y de filtro. Quita el lote de
     * relleno que `configure()` ya puso, para que el registro tenga solo los que
     * la prueba pidió.
     */
    public function conLote(Calle $calle, string $numero, SituacionDelLote $situacion = SituacionDelLote::Terreno): static
    {
        return $this->afterCreating(function (RegistroDePropietario $registro) use ($calle, $numero, $situacion): void {
            $registro->lotes()->where('manzana', '0')->delete();

            $registro->lotes()->create([
                'calle' => $calle,
                'numero_oficial' => $numero,
                'manzana' => '4',
                'lote' => '12',
                'situacion' => $situacion,
            ]);
        });
    }

    /**
     * Quien dio solo el celular.
     */
    public function sinCorreo(): static
    {
        return $this->state(fn (array $atributos) => ['correo' => null]);
    }

    /**
     * Quien dio solo el correo.
     */
    public function sinTelefono(): static
    {
        return $this->state(fn (array $atributos) => ['telefono' => null]);
    }

    /**
     * Con el correo verificado, como si hubiera escrito el código.
     */
    public function conCorreoVerificado(): static
    {
        return $this->state(fn (array $atributos) => [
            'correo_verificado_en' => now(),
            'correo_verificado_por' => MedioDeConfirmacion::Codigo,
        ]);
    }

    /**
     * Con el celular verificado, como si hubiera escrito el código del SMS.
     */
    public function conTelefonoVerificado(): static
    {
        return $this->state(fn (array $atributos) => ['telefono_verificado_en' => now()]);
    }

    /**
     * Con los dos medios verificados.
     */
    public function verificado(): static
    {
        return $this->conCorreoVerificado()->conTelefonoVerificado();
    }

    /**
     * Verificado y además validado por la Administración.
     */
    public function validado(?User $quien = null, ?string $nota = null): static
    {
        return $this->verificado()->state(fn (array $atributos) => [
            'validado_en' => now(),
            'validado_por' => ($quien ?? User::factory()->create())->id,
            'validacion_nota' => $nota,
        ]);
    }

    public function conContactoAdicional(string $nombre): static
    {
        return $this->afterCreating(function (RegistroDePropietario $registro) use ($nombre): void {
            $registro->contactos()->create([
                'nombre' => $nombre,
                'telefono' => '5512345678',
            ]);
        });
    }
}
