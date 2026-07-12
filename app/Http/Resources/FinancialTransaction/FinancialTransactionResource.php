<?php

namespace App\Http\Resources\FinancialTransaction;

use App\Http\Resources\TransactionType\TransactionTypeResource;
use App\Http\Resources\User\SimplifiedUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'league_id' => $this->league_id,
            'user' => SimplifiedUserResource::make($this->whenLoaded('user')),
            'transaction_type' => TransactionTypeResource::make($this->whenLoaded('transactionType')),
            'amount' => (string) $this->amount,
            'description' => $this->description,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
