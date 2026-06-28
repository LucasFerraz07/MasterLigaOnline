<?php

namespace App\Services\Vitalab;

use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Vitalab\VitalabCollection;
use App\Http\Resources\Vitalab\VitalabResource;
use App\Models\Vitalab;

class VitalabService
{
    public function index(array $data): VitalabCollection
    {
        $perPage = (int) ($data['per_page'] ?? 10);
        $page    = (int) ($data['page']     ?? 1);
        $search  = $data['search'] ?? null;

        $query = Vitalab::query()
            ->with('company.owners.user')
            ->when($data['company_id'] ?? null, fn ($q, $id) => $q->where('company_id', $id))
            ->when($search, function ($query, $search) {
                $query->where('esp_code', 'ilike', "%{$search}%");
            })
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new VitalabCollection($paginator);
    }

    public function store(array $data): VitalabResource
    {
        $company = Auth::user()->company->id ?? $data['company_id'];

        $vitalab = Vitalab::create([
            'company_id' => $company,
            'esp_code'   => $data['esp_code'],
        ]);

        return new VitalabResource($vitalab->load('company.owners.user'));
    }
}
