<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone'    => ['required', 'string', 'max:15'],
        ];
    }

    public function attributes(): array
    {
        return [
            'username' => 'usuário',
            'email'    => 'e-mail',
            'password' => 'senha',
            'phone'    => 'telefone',
        ];
    }
}
