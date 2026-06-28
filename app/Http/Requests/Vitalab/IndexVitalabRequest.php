<?php

namespace App\Http\Requests\Vitalab;

use Illuminate\Foundation\Http\FormRequest;

class IndexVitalabRequest extends FormRequest
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
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'search'     => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page'   => 'por página',
            'page'       => 'página',
            'search'     => 'busca',
            'company_id' => 'empresa',
        ];
    }
}
