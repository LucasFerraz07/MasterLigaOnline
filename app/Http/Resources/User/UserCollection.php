<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    public $collects = UserResource::class;

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
