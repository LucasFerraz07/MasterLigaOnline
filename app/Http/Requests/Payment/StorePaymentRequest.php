<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_id' => ['required', 'uuid', 'exists:subscriptions,id'],
            'months' => ['required', 'integer', 'min:1', 'max:24'],
            'league_name' => ['required', 'string', 'max:255'],
            'owner_full_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'subscription_id' => 'plano',
            'months' => 'quantidade de meses',
            'league_name' => 'nome da liga',
            'owner_full_name' => 'nome completo do responsável',
        ];
    }
}
