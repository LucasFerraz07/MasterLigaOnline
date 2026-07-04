<?php

namespace App\Services\Club;

use App\Http\Resources\Club\ClubCollection;
use App\Models\Club;

class ClubService
{
    public function index(array $data): ClubCollection
    {
        $perPage = (int) ($data['per_page'] ?? 10);
        $page    = (int) ($data['page']     ?? 1);

        $query = Club::query()->orderByDesc('created_at');

        if (! empty($data['search'])) {
            $query->where('name', 'like', '%' . $data['search'] . '%');
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new ClubCollection($paginator);
    }
}
