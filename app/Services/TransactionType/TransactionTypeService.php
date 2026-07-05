<?php

namespace App\Services\TransactionType;

use App\Http\Resources\TransactionType\TransactionTypeCollection;
use App\Http\Resources\TransactionType\TransactionTypeResource;
use App\Models\TransactionType;

class TransactionTypeService
{
    public function index(array $data): TransactionTypeCollection
    {
        $perPage = (int) ($data['per_page'] ?? 10);
        $page    = (int) ($data['page']     ?? 1);

        $query = TransactionType::query()->orderByDesc('created_at');

        if (! empty($data['search'])) {
            $query->where('name', 'like', '%' . $data['search'] . '%');
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new TransactionTypeCollection($paginator);
    }

    public function store(array $data): TransactionTypeResource
    {
        $transactionType = TransactionType::create($data);

        return new TransactionTypeResource($transactionType);
    }

    public function update(array $data): TransactionTypeResource
    {
        $transactionType = TransactionType::findOrFail($data['id']);

        $transactionType->update($data);

        return new TransactionTypeResource($transactionType);
    }

    public function destroy(array $data): void
    {
        $transactionType = TransactionType::findOrFail($data['id']);

        $transactionType->delete();
    }
}
