<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
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
            'fraccionamiento_id' => ['required', 'exists:fraccionamientos,id'],
            'owner_id' => ['nullable', 'exists:owners,id'],
            'section' => ['nullable', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:50'],
        ];
    }
}
