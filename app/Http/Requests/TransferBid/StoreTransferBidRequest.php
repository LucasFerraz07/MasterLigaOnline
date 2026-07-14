<?php

namespace App\Http\Requests\TransferBid;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransferBidRequest extends FormRequest
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
            'receiver_id' => ['required', 'uuid', 'exists:users,id'],
            'offered_players' => ['nullable', 'array'],
            'offered_players.*' => ['uuid', 'exists:players,id'],
            'offered_cash' => ['nullable', 'numeric', 'min:0.01'],
            'requested_players' => ['nullable', 'array'],
            'requested_players.*' => ['uuid', 'exists:players,id'],
            'requested_cash' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * Configure additional validation that depends on multiple fields at once.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator): void {
            $hasItems = ! empty($this->input('offered_players'))
                || ! empty($this->input('requested_players'))
                || $this->input('offered_cash') !== null
                || $this->input('requested_cash') !== null;

            if (! $hasItems) {
                $validator->errors()->add('offered_players', 'A proposta precisa conter ao menos um jogador ou valor em dinheiro.');
            }

            if ($this->input('receiver_id') === $this->user()?->id) {
                $validator->errors()->add('receiver_id', 'Não é possível propor uma negociação para si mesmo.');
            }
        });
    }
}
