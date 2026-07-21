<?php

namespace App\Services\League;

use App\Enums\Category;
use App\Enums\UserType;
use App\Http\Resources\League\LeagueCollection;
use App\Http\Resources\League\LeagueResource;
use App\Models\League;
use App\Models\LeagueCategoryPrice;
use App\Models\Owner;
use App\Models\User;
use App\Services\ClubIdentity\ClubIdentityService;
use Illuminate\Support\Facades\DB;

class LeagueService
{
    public function __construct(
        private readonly ClubIdentityService $clubIdentityService
    ) {}

    public function index(array $data): LeagueCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $trashed = $data['with_trashed'] ?? null;

        $query = League::query()
            ->with(['owners.user', 'subscription'])
            ->when($trashed, fn ($query) => $query->withTrashed())
            ->when($search, function ($query) use ($search): void {
                $query->where('name', 'ILIKE', "%{$search}%");
            })
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new LeagueCollection($paginator);
    }

    public function show(array $data): LeagueResource
    {
        $league = League::with(['owners.user', 'subscription'])->findOrFail($data['id']);

        return new LeagueResource($league);
    }

    public function store(array $data): LeagueResource
    {
        return DB::transaction(function () use ($data): LeagueResource {

            $subscriptionEnd = $data['subscription_duration'] ? now()->addMonths($data['subscription_duration']) : null;
            $league = League::create([
                'name' => $data['name'],
                'subscription_id' => $data['subscription_id'] ?? null,
                'subscription_start' => now(),
                'subscription_end' => $subscriptionEnd,
            ]);

            $user = User::create([
                'username' => $data['owner']['username'],
                'email' => $data['owner']['email'],
                'password' => $data['owner']['password'],
                'phone' => $data['owner']['phone'],
                'league_id' => $league->id,
                'user_type' => UserType::LEAGUE_ADMIN,
            ]);

            $user->assignRole(UserType::LEAGUE_ADMIN->value);

            $this->clubIdentityService->assignRandomClub($league->id, $user->id);

            Owner::create([
                'full_name' => $data['owner']['full_name'],
                'cpf' => $data['owner']['cpf'],
                'league_id' => $league->id,
                'user_id' => $user->id,
            ]);

            foreach (Category::cases() as $category) {
                LeagueCategoryPrice::create([
                    'league_id' => $league->id,
                    'category' => $category,
                    'base_salary' => $category->defaultBaseSalary(),
                    'min_overall' => $category->defaultMinOverall(),
                ]);
            }

            $league->load(['owners.user', 'subscription']);

            return new LeagueResource($league);
        });
    }

    public function update(array $data): LeagueResource
    {
        return DB::transaction(function () use ($data): LeagueResource {
            $league = League::findOrFail($data['id']);

            $league->update([
                'name' => $data['name'] ?? $league->name,
                'player_limit' => $data['player_limit'] ?? $league->player_limit,
                'silver_limit' => $data['silver_limit'] ?? $league->silver_limit,
                'golden_limit' => $data['golden_limit'] ?? $league->golden_limit,
                'black_limit' => $data['black_limit'] ?? $league->black_limit,
                'mulct_contract_limit' => $data['mulct_contract_limit'] ?? $league->mulct_contract_limit,
            ]);

            $league->load(['owners.user', 'subscription']);

            return new LeagueResource($league);
        });
    }

    public function delete(array $data): void
    {
        $league = League::findOrFail($data['id']);

        $league->delete();
    }

    public function restore(array $data): void
    {
        $league = League::withTrashed()->findOrFail($data['id']);

        $league->restore();
    }

    public function destroy(array $data): void
    {
        $league = League::withTrashed()->findOrFail($data['id']);

        $league->forceDelete();
    }

    public function renewSubscription(array $data): LeagueResource
    {
        return DB::transaction(function () use ($data): LeagueResource {
            $league = League::findOrFail($data['id']);
            $renewData = [
                'subscription_id' => $data['subscription_id'] ?? $league->subscription_id,
                'subscription_start' => now(),
                'subscription_duration' => $data['subscription_duration'],
            ];

            $league->update($renewData);

            return new LeagueResource($league);
        });
    }
}
