<?php

namespace App\Http\Requests\League;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeagueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subscription_id' => ['nullable', 'exists:subscriptions,id'],
            'subscription_end' => ['nullable', 'date'],
            'owner' => ['required', 'array'],
            'owner.username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'owner.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner.password' => ['required', 'string', 'min:8'],
            'owner.phone' => ['required', 'string', 'max:15'],
            'owner.full_name' => ['required', 'string', 'max:255'],
            'owner.cpf' => ['required', 'string', 'max:14'],
        ];
    }
}
