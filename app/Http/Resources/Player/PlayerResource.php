<?php

namespace App\Http\Resources\Player;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $salary = $this->salary !== null ? (string) $this->salary : null;

        return [
            'id' => $this->id,
            'name' => (string) $this->name,
            'overall' => (int) $this->overall,
            'position' => (string) $this->position,
            'nationality' => (string) $this->nationality,
            'category' => (string) $this->category->value,
            'salary' => $salary,
            'passe' => $salary !== null ? bcmul($salary, '10', 2) : null,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
