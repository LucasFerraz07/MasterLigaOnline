<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ClubResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'crest'      => $this->crest ? Storage::disk('public')->url($this->crest) : null,
            'region'     => $this->region,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
