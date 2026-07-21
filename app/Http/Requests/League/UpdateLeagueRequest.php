<?php

namespace App\Http\Requests\League;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeagueRequest extends FormRequest
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
            'id' => ['required', 'uuid', 'exists:leagues,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'player_limit' => ['nullable', 'integer', 'min:0', 'max:255'],
            'silver_limit' => ['nullable', 'integer', 'min:0', 'max:255'],
            'golden_limit' => ['nullable', 'integer', 'min:0', 'max:255'],
            'black_limit' => ['nullable', 'integer', 'min:0', 'max:255'],
            'mulct_contract_limit' => ['nullable', 'integer', 'min:0', 'max:255'],
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
            'name' => 'nome da liga',
            'player_limit' => 'limite de jogadores',
            'silver_limit' => 'limite de jogadores prata',
            'golden_limit' => 'limite de jogadores ouro',
            'black_limit' => 'limite de jogadores black',
            'mulct_contract_limit' => 'limite de contratos de multa',
        ];
    }
}
