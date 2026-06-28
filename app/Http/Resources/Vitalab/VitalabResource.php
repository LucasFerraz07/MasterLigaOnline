<?php

namespace App\Http\Resources\Vitalab;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Company\CompanyResource;

class VitalabResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'company' => $this->whenLoaded('company', fn () => new CompanyResource($this->company)),
            'esp_code'   => $this->esp_code,
            'last_sync'  => $this->last_sync,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
