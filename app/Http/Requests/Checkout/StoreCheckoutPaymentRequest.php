<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutPaymentRequest extends FormRequest
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
        return ['idempotency_key' => ['required', 'uuid'], 'payment' => ['required', 'array'], 'payment.payment_method_id' => ['required', Rule::in(['pix'])], 'payment.payer' => ['required', 'array'], 'payment.payer.identification' => ['required', 'array'], 'payment.payer.identification.type' => ['required', Rule::in(['CPF', 'CNPJ'])], 'payment.payer.identification.number' => ['required', 'string', 'regex:/^\d{11,14}$/']];
    }
}
