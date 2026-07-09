<?php

namespace App\Http\Requests\User;

use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'username'  => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8'],
            'phone'     => ['required', 'string', 'max:15'],
            'league_id' => ['nullable', 'uuid', 'exists:leagues,id'],
            'user_type' => ['required', Rule::enum(UserType::class)],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'username'  => 'username',
            'email'     => 'email',
            'password'  => 'senha',
            'phone'     => 'telefone',
            'league_id' => 'ID da liga',
            'user_type' => 'tipo de usuário',
        ];
    }
}
