<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Http\Resources\Notification\NotificationCollection;
use App\Http\Resources\Notification\NotificationResource;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    public function createForUser(
        string $userId,
        NotificationType $type,
        string $title,
        ?string $body = null,
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
        ]);
    }

    /**
     * @param  iterable<string>  $userIds
     */
    public function createForUsers(
        iterable $userIds,
        NotificationType $type,
        string $title,
        ?string $body = null,
    ): void {
        foreach ($userIds as $userId) {
            $this->createForUser($userId, $type, $title, $body);
        }
    }

    public function index(array $data): NotificationCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;

        $paginator = Notification::query()
            ->where('user_id', Auth::id())
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationCollection($paginator);
    }

    public function markAsRead(array $data): NotificationResource
    {
        $notification = Notification::query()
            ->where('id', $data['id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return new NotificationResource($notification);
    }
}
