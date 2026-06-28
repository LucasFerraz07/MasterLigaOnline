<?php

namespace App\Http\Resources\Vitalab;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VitalabCollection extends ResourceCollection
{
    public $collects = VitalabResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data'       => $this->collection,
            'pagination' => [
                'total'        => $this->total(),
                'count'        => $this->count(),
                'per_page'     => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages'  => $this->lastPage(),
            ],
        ];
    }
}
