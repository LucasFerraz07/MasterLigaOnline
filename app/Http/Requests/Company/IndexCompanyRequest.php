<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class IndexCompanyRequest extends FormRequest
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
            'ativo'    => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ativo'    => 'ativo',
            'per_page' => 'por página',
            'page'     => 'página',
        ];
    }
}
