<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome'           => ['required', 'string', 'max:255'],
            'ativo'          => ['sometimes', 'boolean'],
            'owner_name'     => ['required', 'string', 'max:255'],
            'owner_email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', Password::min(8)],
            'owner_cpf'      => ['required', 'string', 'size:14', 'unique:owners,cpf'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome'           => 'nome',
            'ativo'          => 'ativo',
            'owner_name'     => 'nome do responsável',
            'owner_email'    => 'e-mail do responsável',
            'owner_password' => 'senha do responsável',
            'owner_cpf'      => 'CPF do responsável',
        ];
    }
}
