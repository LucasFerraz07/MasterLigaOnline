<?php

namespace App\Http\Requests\Measurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vitalab_id'          => ['required', 'integer', 'exists:vitalabs,id'],
            'temperature'         => ['nullable', 'numeric', 'between:-99.99,999.99'],
            'humidity'            => ['nullable', 'numeric', 'between:0,100'],
            'measured_at'         => ['nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }

    public function attributes(): array
    {
        return [
            'vitalab_id'         => 'vitalab',
            'temperature'        => 'temperatura',
            'humidity'           => 'umidade',
            'measured_at'        => 'data da medição',
        ];
    }
}
