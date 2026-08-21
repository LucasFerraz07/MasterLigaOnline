<?php

namespace App\Http\Resources\Notification;

use App\Enums\NotificationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var NotificationType $type */
        $type = $this->type;

        return [
            'id' => $this->id,
            'type' => $type,
            'title' => $this->title,
            'body' => $this->body,
            'read_at' => $this->read_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
