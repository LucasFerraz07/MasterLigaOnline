<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'         => ['required', 'uuid', 'exists:subscriptions,id'],
            'name'       => ['sometimes', 'required', 'string', 'max:255', 'unique:subscriptions,name,' . $this->route('id') . ',id'],
            'user_limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:255'],
            'price'      => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'       => 'nome',
            'user_limit' => 'limite de usuários',
            'price'      => 'preço',
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
