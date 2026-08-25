<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('plan')]);
    }

    public function rules(): array
    {
        return ['id' => ['required', 'uuid', 'exists:plans,id'], 'name' => ['sometimes', 'string', 'max:255'], 'user_limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:255'], 'active' => ['sometimes', 'boolean']];
    }
}
