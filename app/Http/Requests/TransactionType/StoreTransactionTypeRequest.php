<?php

namespace App\Http\Requests\TransactionType;

use App\Enums\TransactionOperation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionTypeRequest extends FormRequest
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
            'name'       => ['required', 'string', 'max:50', 'unique:transaction_types,name'],
            'name_label' => ['required', 'string', 'max:100', 'unique:transaction_types,name_label'],
            'operation'  => ['required', Rule::enum(TransactionOperation::class)],
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
            'name'       => 'nome',
            'name_label' => 'rótulo',
            'operation'  => 'operação',
        ];
    }
}
