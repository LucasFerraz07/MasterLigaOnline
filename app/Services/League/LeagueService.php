<?php

namespace App\Services\League;

use App\Exceptions\ApiException;
use App\Http\Resources\League\LeagueCollection;
use App\Http\Resources\League\LeagueResource;
use App\Models\League;

class LeagueService
{
    public function index(array $data): LeagueCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $trashed = $data['with_trashed'] ?? null;

        $query = League::query()
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
        $league = League::findOrFail($data['id']);

        return new LeagueResource($league);
    }
}