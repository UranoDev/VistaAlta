<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMonthlyFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date'],
            'surcharge_type' => ['nullable', 'in:percentage,fixed'],
            'surcharge_value' => ['nullable', 'required_with:surcharge_type', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'El importe de la cuota es obligatorio.',
            'amount.min' => 'El importe debe ser mayor a cero.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date' => 'La fecha de inicio no es válida.',
            'surcharge_type.in' => 'El tipo de recargo no es válido.',
            'surcharge_value.required_with' => 'El valor del recargo es obligatorio cuando se selecciona un tipo.',
            'surcharge_value.min' => 'El valor del recargo debe ser mayor a cero.',
        ];
    }
}
