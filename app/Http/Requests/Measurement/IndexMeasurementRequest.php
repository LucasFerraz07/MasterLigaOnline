<?php

namespace App\Http\Requests\Measurement;

use Illuminate\Foundation\Http\FormRequest;

class IndexMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => is_numeric($this->per_page) ? (int) $this->per_page : $this->per_page,
            'page'     => is_numeric($this->page) ? (int) $this->page : $this->page,
        ]);
    }

    public function rules(): array
    {
        return [
            'vitalab_id'         => ['nullable', 'integer', 'exists:vitalabs,id'],
            'failed_measurement' => ['nullable', 'boolean'],
            'measured_from'      => ['nullable', 'date'],
            'measured_to'        => ['nullable', 'date', 'after_or_equal:measured_from'],
            'per_page'           => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'               => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'vitalab_id'         => 'vitalab',
            'failed_measurement' => 'medição com falha',
            'measured_from'      => 'data inicial',
            'measured_to'        => 'data final',
            'per_page'           => 'por página',
            'page'               => 'página',
        ];
    }
}
