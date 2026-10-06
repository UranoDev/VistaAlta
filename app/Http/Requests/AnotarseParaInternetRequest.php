<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Calle;
use App\Support\Telefono;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * El formulario de `/internet`. Los mensajes van aquí porque el sitio no trae
 * archivos de idioma: un «validation.required» en la pantalla de un vecino sería
 * peor que no validar.
 */
class AnotarseParaInternetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('celular'))) {
            $this->merge(['celular' => Telefono::aDiezDigitos($this->input('celular'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'calle' => ['required', Rule::enum(Calle::class)],
            // El domicilio se da por número oficial, o por manzana y lote; con una
            // de las dos formas basta. Dar las dos también vale.
            'numero_oficial' => ['nullable', 'string', 'max:20', 'required_without_all:manzana,lote'],
            'manzana' => ['nullable', 'string', 'max:20', 'required_without:numero_oficial'],
            'lote' => ['nullable', 'string', 'max:20', 'required_without:numero_oficial'],
            'celular' => ['required', 'regex:/^\d{10}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Falta este dato.',
            'max' => 'Es demasiado largo.',
            'numero_oficial.required_without_all' => 'Escribe el número oficial, o bien la manzana y el lote.',
            'manzana.required_without' => 'Falta la manzana. Si no la tienes, escribe el número oficial.',
            'lote.required_without' => 'Falta el lote. Si no lo tienes, escribe el número oficial.',
            'calle.required' => 'Elige tu calle.',
            'calle.enum' => 'Elige una de las calles.',
            'celular.required' => 'Escribe tu celular.',
            'celular.regex' => 'Escribe el celular a 10 dígitos.',
        ];
    }

    /**
     * @return array{calle: string, numero_oficial: string, manzana: string, lote: string}
     */
    public function domicilio(): array
    {
        $datos = $this->validated();

        return [
            'calle' => $datos['calle'],
            // Lo que no se dio queda vacío y no nulo: el índice único del domicilio
            // no distingue entre dos NULL, y sí entre dos cadenas vacías iguales.
            'numero_oficial' => $datos['numero_oficial'] ?? '',
            'manzana' => $datos['manzana'] ?? '',
            'lote' => $datos['lote'] ?? '',
        ];
    }

    public function celular(): string
    {
        return $this->validated()['celular'];
    }
}
