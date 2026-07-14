<?php

namespace App\Services\LeagueCategoryPrice;

use App\Http\Resources\LeagueCategoryPrice\LeagueCategoryPriceCollection;
use App\Http\Resources\LeagueCategoryPrice\LeagueCategoryPriceResource;
use App\Models\LeagueCategoryPrice;

class LeagueCategoryPriceService
{
    public function index(array $data): LeagueCategoryPriceCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $leagueId = $data['league_id'] ?? null;

        $query = LeagueCategoryPrice::query()
            ->when($leagueId, fn ($query) => $query->where('league_id', $leagueId))
            ->orderBy('category');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new LeagueCategoryPriceCollection($paginator);
    }

    public function update(array $data): LeagueCategoryPriceResource
    {
        $categoryPrice = LeagueCategoryPrice::findOrFail($data['id']);

        $categoryPrice->update([
            'base_salary' => $data['base_salary'] ?? $categoryPrice->base_salary,
            'min_overall' => $data['min_overall'] ?? $categoryPrice->min_overall,
        ]);

        return new LeagueCategoryPriceResource($categoryPrice);
    }
}
