<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    public function rules(): array
    {
        return ['idempotency_key' => ['required', 'uuid'], 'plan_price_id' => ['required', 'uuid', 'exists:plan_prices,id'], 'league_name' => ['nullable', 'string', 'max:255'], 'owner_full_name' => ['nullable', 'string', 'max:150']];
    }
}
