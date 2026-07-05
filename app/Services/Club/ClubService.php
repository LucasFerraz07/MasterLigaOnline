<?php

namespace App\Services\Club;

use App\Http\Resources\Club\ClubCollection;
use App\Http\Resources\Club\ClubResource;
use App\Models\Club;
use Illuminate\Support\Facades\Storage;

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

    public function store(array $data): ClubResource
    {
        if (! empty($data['crest'])) {
            $data['crest'] = $data['crest']->store('images/clubs', 'public');
        }

        $club = Club::create($data);

        return new ClubResource($club);
    }

    public function update(array $data): ClubResource
    {
        $club = Club::findOrFail($data['id']);

        if (! empty($data['crest'])) {
            if ($club->crest !== null) {
                Storage::disk('public')->delete($club->crest);
            }

            $data['crest'] = $data['crest']->store('images/clubs', 'public');
        }

        $club->update($data);

        return new ClubResource($club);
    }

    public function destroy(array $data): void
    {
        $club = Club::findOrFail($data['id']);

        if ($club->crest !== null) {
            Storage::disk('public')->delete($club->crest);
        }

        $club->delete();
    }
}
