<?php

namespace App\Http\Requests\Player;

use App\Enums\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPlayerRequest extends FormRequest
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
            'category' => ['nullable', Rule::enum(Category::class)],
            'min_overall' => ['nullable', 'integer', 'min:0', 'max:99'],
            'max_overall' => ['nullable', 'integer', 'min:0', 'max:99'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'min:0'],
            'min_passe' => ['nullable', 'numeric', 'min:0'],
            'max_passe' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
