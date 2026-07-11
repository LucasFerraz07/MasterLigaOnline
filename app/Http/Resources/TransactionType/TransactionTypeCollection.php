<?php

namespace App\Http\Resources\TransactionType;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TransactionTypeCollection extends ResourceCollection
{
    public $collects = TransactionTypeResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
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
