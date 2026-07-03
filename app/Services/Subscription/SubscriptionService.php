<?php

namespace App\Services\Subscription;

use App\Exceptions\ApiException;
use App\Http\Resources\Subscription\SubscriptionCollection;
use App\Http\Resources\Subscription\SubscriptionResource;
use App\Models\Subscription;
use App\Models\League;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function index(array $data): SubscriptionCollection
    {
        $perPage = (int) ($data['per_page'] ?? 10);
        $page    = (int) ($data['page']     ?? 1);

        $query = Subscription::query()->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new SubscriptionCollection($paginator);
    }

    public function store(array $data): SubscriptionResource
    {
        return DB::transaction(function () use ($data): SubscriptionResource {
            $subscription = Subscription::create([
                'name'  => $data['name'],
                'user_limit' => $data['user_limit'],
                'price'      => $data['price'] ?? 0,
            ]);
            return new SubscriptionResource($subscription);
        });
    }

    public function destroy(array $data): void
    {
        $subscription = Subscription::findOrFail($data['id']);
        $league = League::where('subscription_id', $data['id'])->first();
        if($league) {
            throw new ApiException('This subscription is used in a league and cannot be deleted.', 409);
        }else{
            $subscription->delete();
        }
    }
}