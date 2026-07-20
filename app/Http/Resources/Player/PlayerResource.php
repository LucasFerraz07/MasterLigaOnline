<?php

namespace App\Http\Resources\Player;

use App\Http\Resources\User\UserWithClubResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
        $owner = $this->relationLoaded('squads') ? $this->squads->first()?->user : null;

        return [
            'id' => $this->id,
            'name' => (string) $this->name,
            'overall' => (int) $this->overall,
            'position' => (string) $this->position,
            'nationality' => (string) $this->nationality,
            'category' => $this->category !== null ? (string) $this->category : null,
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'salary' => $salary,
            'passe' => $salary !== null ? bcmul($salary, '10', 2) : null,
            'owner' => $owner ? UserWithClubResource::make($owner) : null,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
