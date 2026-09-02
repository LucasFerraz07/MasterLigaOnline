<?php

namespace App\Services\Club;

use App\Exceptions\ApiException;
use App\Http\Resources\Club\ClubCollection;
use App\Http\Resources\Club\ClubResource;
use App\Models\Club;
use App\Models\ClubIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClubService
{
    public function index(array $data): ClubCollection
    {
        $perPage = (int) ($data['per_page'] ?? 10);
        $page = (int) ($data['page'] ?? 1);

        $query = Club::query()->orderByDesc('created_at');

        if (! empty($data['search'])) {
            $query->where('name', 'ilike', '%'.$data['search'].'%');
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
        $crest = DB::transaction(function () use ($data): ?string {
            $club = Club::query()->lockForUpdate()->findOrFail($data['id']);

            if (ClubIdentity::query()->where('club_id', $club->id)->exists()) {
                throw new ApiException('Não é possível excluir um clube atribuído a um participante.', 409);
            }

            $club->delete();

            return $club->crest;
        });

        if ($crest !== null) {
            Storage::disk('public')->delete($crest);
        }
    }
}
