<?php

namespace App\Http\Resources\ClubIdentity;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ClubIdentityResource extends JsonResource
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
            'user' => [
                'id' => $this->user_id,
                'username' => (string) $this->owner_username,
            ],
            'club' => [
                'id' => $this->club_id,
                'name' => (string) $this->club_name,
                'crest' => $this->club_crest ? Storage::disk('public')->url($this->club_crest) : null,
                'region' => (string) $this->club_region,
            ],
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
