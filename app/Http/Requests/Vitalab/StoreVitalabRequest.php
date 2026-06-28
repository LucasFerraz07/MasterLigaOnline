<?php

namespace App\Http\Requests\Vitalab;

use Illuminate\Foundation\Http\FormRequest;

class StoreVitalabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'esp_code'   => ['required', 'string', 'max:255', 'unique:vitalabs,esp_code'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_id' => 'empresa',
            'esp_code'   => 'código ESP',
        ];
    }
}
