<?php

namespace App\Services\League;

use App\Exceptions\ApiException;
use App\Http\Resources\League\LeagueCollection;
use App\Models\League;

class LeagueService
{
    public function index(array $data): LeagueCollection
    {
        $perPage = (int) ($data['per_page'] ?? 10);
        $page    = (int) ($data['page']     ?? 1);

        $query = League::query()
            ->orderByDesc('created_at')
            ->when($data['with_trashed'] ?? false, fn ($q) => $q->withTrashed());

        if (! empty($data['search'])) {
            $query->where('name', 'ilike', '%' . $data['search'] . '%');
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new LeagueCollection($paginator);
    }
}