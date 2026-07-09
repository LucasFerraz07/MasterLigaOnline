<?php

namespace App\Http\Resources\LeagueCategoryPrice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeagueCategoryPriceResource extends JsonResource
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
            'category' => $this->category,
            'base_salary' => $this->base_salary,
            'base_passe' => $this->base_passe,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
