<?php

namespace App\Http\Requests\TransactionType;

use App\Enums\TransactionOperation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionTypeRequest extends FormRequest
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
            'id'          => ['required', 'uuid', 'exists:transaction_types,id'],
            'name'        => ['nullable', 'string', 'max:50', 'unique:transaction_types,name,' . $this->route('id') . ',id'],
            'description' => ['nullable', 'string', 'max:100', 'unique:transaction_types,description,' . $this->route('id') . ',id'],
            'operation'   => ['nullable', Rule::enum(TransactionOperation::class)],
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
            'name'        => 'nome',
            'description' => 'descrição',
            'operation'   => 'operação',
        ];
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
}
