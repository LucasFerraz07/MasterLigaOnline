<?php

namespace App\Http\Resources\FinancialTransaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FinancialTransactionCollection extends ResourceCollection
{
    public $collects = FinancialTransactionResource::class;

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
