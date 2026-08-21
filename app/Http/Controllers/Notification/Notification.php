<?php

namespace App\Http\Controllers\Notification;

use App\Builder\ReturnApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\IndexNotificationRequest;
use App\Http\Requests\Notification\MarkNotificationAsReadRequest;
use App\Services\Notification\NotificationService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Notification')]
class Notification extends Controller
{
    public function __construct(
        private readonly NotificationService $service
    ) {}

    #[Endpoint(operationId: 'indexNotification', title: 'Index Notification', description: '**operationId:** `indexNotification` — Lista as notificações não expiradas do usuário autenticado, da mais recente para a mais antiga. Em **200**, `data` segue o schema **NotificationCollection** (paginado).')]
    public function index(IndexNotificationRequest $request): JsonResponse
    {
        $data = $this->service->index($request->validated());

        return ReturnApi::success($data, 'Notificações listadas com sucesso.');
    }

    #[Endpoint(operationId: 'markNotificationAsRead', title: 'Mark Notification As Read', description: '**operationId:** `markNotificationAsRead` — Marca uma notificação do usuário autenticado como lida. Em **200**, `data` segue o schema **NotificationResource**.')]
    public function markAsRead(MarkNotificationAsReadRequest $request): JsonResponse
    {
        $data = $this->service->markAsRead($request->validated());

        return ReturnApi::success($data, 'Notificação marcada como lida.');
    }
}
