<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza with_trashed vindo da query string ("true"/"false") para boolean.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('with_trashed')) {
            $this->merge([
                'with_trashed' => filter_var($this->input('with_trashed'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'with_trashed' => ['nullable', 'boolean'],
        ];
    }
}
