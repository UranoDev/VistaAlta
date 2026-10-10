<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Calle;
use App\Enums\SituacionDelLote;
use App\Support\Telefono;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * El formulario de `/registro`. Todo el texto que ve quien se equivoca vive en
 * `messages()` y `attributes()`: el sitio no trae archivos de idioma, y un error
 * como «validation.required» en la pantalla de un propietario sería peor que
 * dejarlo sin validar.
 *
 * Los teléfonos se normalizan antes de validar (puros dígitos, sin lada de país)
 * para que «55 1234-5678» y «5512345678» sean el mismo número en el panel.
 */
class RegistrarPropietarioRequest extends FormRequest
{
    private const CAMPOS_DE_TELEFONO = ['telefono', 'emergencia_telefono'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $datos = $this->all();

        foreach (self::CAMPOS_DE_TELEFONO as $campo) {
            if (isset($datos[$campo]) && is_string($datos[$campo])) {
                $datos[$campo] = Telefono::aDiezDigitos($datos[$campo]);
            }
        }

        foreach (['lotes', 'contactos'] as $lista) {
            if (! isset($datos[$lista]) || ! is_array($datos[$lista])) {
                continue;
            }

            foreach ($datos[$lista] as $i => $renglon) {
                if (is_array($renglon) && isset($renglon['telefono']) && is_string($renglon['telefono'])) {
                    $datos[$lista][$i]['telefono'] = Telefono::aDiezDigitos($renglon['telefono']);
                }
            }
        }

        $this->replace($datos);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $telefono = ['nullable', 'regex:/^\d{10}$/'];

        return [
            'nombre' => ['required', 'string', 'max:255'],
            // Con uno de los dos basta. Si se dan los dos, los dos se verifican, cada
            // uno por separado: el correo con un código y un enlace, el celular con
            // un código por SMS.
            'telefono' => [...$telefono, 'required_without:correo'],
            'correo' => ['nullable', 'email:rfc', 'max:255', 'required_without:telefono'],

            'lotes' => ['required', 'array', 'min:1', 'max:20'],
            'lotes.*.calle' => ['required', Rule::enum(Calle::class)],
            // El lote se ubica por número oficial, o por manzana y lote; con una de
            // las dos formas basta, igual que en la lista de espera de internet.
            'lotes.*.numero_oficial' => ['nullable', 'string', 'max:20', 'required_without_all:lotes.*.manzana,lotes.*.lote'],
            'lotes.*.manzana' => ['nullable', 'string', 'max:20', 'required_without:lotes.*.numero_oficial'],
            'lotes.*.lote' => ['nullable', 'string', 'max:20', 'required_without:lotes.*.numero_oficial'],
            'lotes.*.situacion' => ['required', Rule::enum(SituacionDelLote::class)],

            'contactos' => ['nullable', 'array', 'max:10'],
            'contactos.*.nombre' => ['required', 'string', 'max:255'],
            'contactos.*.telefono' => [...$telefono, 'required_without:contactos.*.correo'],
            'contactos.*.correo' => ['nullable', 'email:rfc', 'max:255', 'required_without:contactos.*.telefono'],

            'emergencia_nombre' => ['nullable', 'string', 'max:255', 'required_with:emergencia_telefono'],
            'emergencia_telefono' => [...$telefono, 'required_with:emergencia_nombre'],

            'acepto_aviso' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Falta este dato.',
            'required_without' => 'Indica un celular o un correo. Con uno basta.',
            'required_with' => 'Falta este dato.',
            'max' => 'Es demasiado largo.',
            'email' => 'Escribe un correo válido, como nombre@correo.com.',
            'telefono.regex' => 'Escribe el teléfono a 10 dígitos.',
            'contactos.*.telefono.regex' => 'Escribe el teléfono a 10 dígitos.',
            'emergencia_telefono.regex' => 'Escribe el teléfono a 10 dígitos.',
            'lotes.required' => 'Registra al menos un lote.',
            'lotes.min' => 'Registra al menos un lote.',
            'lotes.max' => 'Son demasiados lotes para un solo registro. Escríbenos y lo vemos.',
            'contactos.max' => 'Son demasiados contactos. Deja los más importantes.',
            'lotes.*.calle.enum' => 'Elige una de las calles.',
            'lotes.*.numero_oficial.required_without_all' => 'Escribe el número oficial, o bien la manzana y el lote.',
            'lotes.*.manzana.required_without' => 'Falta la manzana. Si no la tienes, escribe el número oficial.',
            'lotes.*.lote.required_without' => 'Falta el lote. Si no lo tienes, escribe el número oficial.',
            'lotes.*.situacion.enum' => 'Elige cómo está el lote.',
            'acepto_aviso.accepted' => 'Marca esta casilla para poder enviar tu registro.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'telefono' => 'teléfono',
            'correo' => 'correo',
            'lotes' => 'lotes',
            'contactos' => 'contactos',
        ];
    }

    /**
     * Lo que se guarda del propietario, ya normalizado.
     *
     * @return array<string, mixed>
     */
    public function datosDelPropietario(): array
    {
        $datos = $this->validated();

        return [
            'nombre' => trim($datos['nombre']),
            'telefono' => $datos['telefono'] ?? null,
            'correo' => filled($datos['correo'] ?? null) ? mb_strtolower(trim($datos['correo'])) : null,
            'emergencia_nombre' => filled($datos['emergencia_nombre'] ?? null) ? trim($datos['emergencia_nombre']) : null,
            'emergencia_telefono' => $datos['emergencia_telefono'] ?? null,
            'aceptado_en' => now(),
            'aviso_version' => (string) config('contenido.legal.actualizado_en'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lotes(): array
    {
        return array_values(array_map(fn (array $lote): array => [
            'calle' => $lote['calle'],
            // Lo que no se dio queda vacío, no nulo: las columnas no aceptan NULL.
            'numero_oficial' => trim($lote['numero_oficial'] ?? ''),
            'manzana' => trim($lote['manzana'] ?? ''),
            'lote' => trim($lote['lote'] ?? ''),
            'situacion' => $lote['situacion'],
        ], $this->validated()['lotes']));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function contactos(): array
    {
        return array_values(array_map(fn (array $contacto): array => [
            'nombre' => trim($contacto['nombre']),
            'telefono' => $contacto['telefono'] ?? null,
            'correo' => filled($contacto['correo'] ?? null) ? mb_strtolower(trim($contacto['correo'])) : null,
        ], $this->validated()['contactos'] ?? []));
    }
}
