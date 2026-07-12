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
            'category' => (string) $this->category->value,
            'min_overall' => (int) $this->min_overall,
            'base_salary' => (string) $this->base_salary,
            'base_passe' => (string) $this->base_passe,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
