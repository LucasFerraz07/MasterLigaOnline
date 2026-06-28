<?php

namespace App\Services\Company;

use App\Http\Resources\Company\CompanyCollection;
use App\Http\Resources\Company\CompanyResource;
use App\Models\Company;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyService
{
    public function index(array $data): CompanyCollection
    {
        $perPage = (int) ($data['per_page'] ?? 10);
        $page    = (int) ($data['page']     ?? 1);

        $query = Company::query()
            ->with('owners.user')
            ->when(isset($data['ativo']), fn ($q) => $q->where('ativo', $data['ativo']))
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new CompanyCollection($paginator);
    }

    public function store(array $data): CompanyResource
    {
        return DB::transaction(function () use ($data): CompanyResource {
            $company = Company::create([
                'nome'  => $data['nome'],
                'ativo' => $data['ativo'] ?? true,
            ]);

            $owner = User::create([
                'name'       => $data['owner_name'],
                'email'      => $data['owner_email'],
                'password'   => bcrypt($data['owner_password']),
                'company_id' => $company->id,
            ]);

            $owner->assignRole('tenant_admin');

            $ownerRecord = Owner::create([
                'cpf'        => $data['owner_cpf'],
                'company_id' => $company->id,
                'user_id'    => $owner->id,
            ]);

            $ownerRecord->companies()->attach($company->id);

            return new CompanyResource($company->load('owners.user'));
        });
    }
}
