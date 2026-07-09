<?php

namespace App\Http\Requests\LeagueCategoryPrice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexLeagueCategoryPriceRequest extends FormRequest
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
            'league_id' => ['nullable', 'uuid', 'exists:leagues,id'],
            'per_page' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
