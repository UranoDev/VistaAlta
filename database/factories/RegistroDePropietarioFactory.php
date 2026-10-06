<?php

namespace Database\Factories;

use App\Enums\Calle;
use App\Enums\MedioDeConfirmacion;
use App\Enums\SituacionDelLote;
use App\Models\RegistroDePropietario;
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
            'residentes' => null,
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
     * Ya confirmado, como si hubiera escrito el código.
     */
    public function confirmado(): static
    {
        return $this->state(fn (array $atributos) => [
            'confirmado_en' => now(),
            'confirmado_por' => MedioDeConfirmacion::Codigo,
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
