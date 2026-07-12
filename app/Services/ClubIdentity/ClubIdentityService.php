<?php

namespace App\Services\ClubIdentity;

use App\Enums\LeaguePhase;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\ClubIdentity\ClubIdentityCollection;
use App\Http\Resources\ClubIdentity\ClubIdentityResource;
use App\Models\Club;
use App\Models\ClubIdentity;
use App\Models\Season;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClubIdentityService
{
    public function index(array $data): ClubIdentityCollection
    {
        $actor = Auth::user();
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $userId = $data['user_id'] ?? null;
        $leagueId = $actor->hasRole(UserType::SYSTEM_ADMIN->value) ? ($data['league_id'] ?? null) : null;

        $query = $this->baseQuery()
            ->when($search, fn (Builder $query) => $query->where('clubs.name', 'ILIKE', "%{$search}%"))
            ->when($userId, fn (Builder $query) => $query->where('club_identities.user_id', $userId))
            ->when($leagueId, fn (Builder $query) => $query->where('club_identities.league_id', $leagueId))
            ->orderBy('clubs.name');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new ClubIdentityCollection($paginator);
    }

    public function show(array $data): ClubIdentityResource
    {
        $clubIdentity = $this->baseQuery()->where('club_identities.id', $data['id'])->firstOrFail();

        return new ClubIdentityResource($clubIdentity);
    }

    public function changeClub(array $data): ClubIdentityResource
    {
        return DB::transaction(function () use ($data): ClubIdentityResource {
            $clubIdentity = ClubIdentity::findOrFail($data['id']);
            $actor = Auth::user();

            $isOwner = $clubIdentity->user_id === $actor->id;
            $isSystemAdmin = $actor->hasRole(UserType::SYSTEM_ADMIN->value);

            if (! $isOwner && ! $isSystemAdmin) {
                throw new ApiException('Apenas o dono do clube pode alterá-lo.', 403);
            }

            $season = Season::currentFor($clubIdentity->league_id);

            if ($season === null || $season->phase !== LeaguePhase::WindowOpening) {
                throw new ApiException('A troca de clube só é permitida durante a Janela Inicial.', 409);
            }

            $clubInUse = ClubIdentity::query()
                ->where('league_id', $clubIdentity->league_id)
                ->where('club_id', $data['club_id'])
                ->where('id', '!=', $clubIdentity->id)
                ->exists();

            if ($clubInUse) {
                throw new ApiException('Este clube já está em uso por outro participante nesta liga.', 422);
            }

            $clubIdentity->update(['club_id' => $data['club_id']]);

            return $this->show(['id' => $clubIdentity->id]);
        });
    }

    public function assignRandomClub(string $leagueId, string $userId): ClubIdentity
    {
        $usedClubIds = ClubIdentity::query()->where('league_id', $leagueId)->pluck('club_id');

        $club = Club::query()
            ->when($usedClubIds->isNotEmpty(), fn (Builder $query) => $query->whereNotIn('id', $usedClubIds))
            ->inRandomOrder()
            ->first();

        if ($club === null) {
            throw new ApiException('Não há clubes disponíveis para atribuição automática nesta liga.', 422);
        }

        return ClubIdentity::create([
            'league_id' => $leagueId,
            'user_id' => $userId,
            'club_id' => $club->id,
        ]);
    }

    private function baseQuery(): Builder
    {
        return ClubIdentity::query()
            ->select([
                'club_identities.*',
                'clubs.name as club_name',
                'clubs.crest as club_crest',
                'clubs.region as club_region',
                'users.username as owner_username',
            ])
            ->join('clubs', 'clubs.id', '=', 'club_identities.club_id')
            ->join('users', 'users.id', '=', 'club_identities.user_id');
    }
}
