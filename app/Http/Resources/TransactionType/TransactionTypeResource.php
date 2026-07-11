<?php

namespace App\Http\Resources\TransactionType;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => (string) $this->name,
            'description' => $this->description !== null ? (string) $this->description : null,
            'operation'   => (string) $this->operation->value,
            'created_at'  => (string) $this->created_at,
            'updated_at'  => (string) $this->updated_at,
        ];
    }
}
