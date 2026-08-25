<?php

namespace App\Http\Requests\Plan;

use App\Models\PlanPrice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'alpha_dash', 'max:64', 'unique:plan_prices,code'], 'interval_months' => ['required', 'integer', Rule::in(PlanPrice::ALLOWED_INTERVALS)], 'amount_cents' => ['required', 'integer', 'min:1'], 'currency' => ['sometimes', Rule::in(['BRL'])]];
    }
}
