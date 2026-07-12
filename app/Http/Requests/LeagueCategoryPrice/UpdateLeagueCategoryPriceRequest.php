<?php

namespace App\Http\Requests\LeagueCategoryPrice;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeagueCategoryPriceRequest extends FormRequest
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
            'id' => ['required', 'uuid', 'exists:league_category_prices,id'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'min_overall' => ['required', 'integer', 'min:0', 'max:99'],
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
            'base_salary' => 'salário base',
            'min_overall' => 'piso de overall',
        ];
    }
}
