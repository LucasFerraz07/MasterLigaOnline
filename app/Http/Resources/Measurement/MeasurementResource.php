<?php

namespace App\Http\Resources\Measurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeasurementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'vitalab_id'         => $this->vitalab_id,
            'temperature'        => $this->temperature,
            'humidity'           => $this->humidity,
            'measured_at'        => $this->measured_at,
            'failed_measurement' => $this->failed_measurement,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
