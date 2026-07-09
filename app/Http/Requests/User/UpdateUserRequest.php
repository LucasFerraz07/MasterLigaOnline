<?php

namespace App\Http\Requests\User;

use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Merge the route parameter into the data to be validated.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id'        => ['required', 'uuid', 'exists:users,id'],
            'username'  => ['nullable', 'string', 'max:255', 'unique:users,username,' . $this->route('id') . ',id'],
            'email'     => ['nullable', 'email', 'max:255', 'unique:users,email,' . $this->route('id') . ',id'],
            'password'  => ['nullable', 'string', 'min:8'],
            'phone'     => ['nullable', 'string', 'max:15'],
            'user_type' => ['nullable', Rule::enum(UserType::class)],
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
