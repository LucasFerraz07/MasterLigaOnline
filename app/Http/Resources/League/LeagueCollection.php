<?php

namespace App\Http\Resources\League;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LeagueCollection extends ResourceCollection
{
    public $collects = LeagueResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data'       => $this->collection,
            'pagination' => [
                'total'        => (int) $this->total(),
                'count'        => (int) $this->count(),
                'per_page'     => (int) $this->perPage(),
                'current_page' => (int) $this->currentPage(),
                'total_pages'  => (int) $this->lastPage(),
            ],
        ];
    }
}
