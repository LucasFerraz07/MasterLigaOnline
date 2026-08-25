<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'alpha_dash', 'max:64', 'unique:plans,code'], 'name' => ['required', 'string', 'max:255'], 'user_limit' => ['nullable', 'integer', 'min:1', 'max:255'], 'active' => ['sometimes', 'boolean']];
    }
}
