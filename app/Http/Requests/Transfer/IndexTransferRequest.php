<?php

namespace App\Http\Requests\Transfer;

use App\Enums\TransferType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTransferRequest extends FormRequest
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
            'per_page' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'type' => ['nullable', Rule::enum(TransferType::class)],
            'league_id' => ['nullable', 'uuid', 'exists:leagues,id'],
        ];
    }
}
