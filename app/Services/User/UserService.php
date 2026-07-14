<?php

namespace App\Services\User;

use App\Enums\TransactionOperation;
use App\Enums\UserType;
use App\Exceptions\ApiException;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\FinancialTransaction;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\ClubIdentity\ClubIdentityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private readonly ClubIdentityService $clubIdentityService
    ) {}

    public function index(array $data): UserCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $trashed = $data['with_trashed'] ?? null;

        $query = User::query()
            ->with('roles.permissions')
            ->when($trashed, fn ($query) => $query->withTrashed())
            ->when($search, function ($query) use ($search): void {
                $query->where('username', 'ILIKE', "%{$search}%");
            })
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new UserCollection($paginator);
    }

    public function show(array $data): UserResource
    {
        $user = User::with('roles.permissions')->findOrFail($data['id']);

        return new UserResource($user);
    }

    public function store(array $data): UserResource
    {
        return DB::transaction(function () use ($data): UserResource {
            $actor = Auth::user();
            $leagueId = $actor->user_type === UserType::SYSTEM_ADMIN
                ? $data['league_id']
                : $actor->league_id;

            $user = User::create([
                'username'  => $data['username'],
                'email'     => $data['email'],
                'password'  => $data['password'],
                'phone'     => $data['phone'],
                'league_id' => $leagueId,
                'user_type' => UserType::USER,
            ]);

            $user->assignRole($data['role']);

            $this->clubIdentityService->assignRandomClub($leagueId, $user->id);

            return new UserResource($user);
        });
    }

    public function update(array $data): UserResource
    {
        return DB::transaction(function () use ($data): UserResource {
            $user = User::findOrFail($data['id']);

            $user->update([
                'username' => $data['username'] ?? $user->username,
                'email'    => $data['email'] ?? $user->email,
                'password' => $data['password'] ?? $user->password,
                'phone'    => $data['phone'] ?? $user->phone,
            ]);

            if (! empty($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

            return new UserResource($user);
        });
    }

    public function adjustBalance(array $data): UserResource
    {
        return DB::transaction(function () use ($data): UserResource {
            $actor = Auth::user();
            $isSystemAdmin = $actor->hasRole(UserType::SYSTEM_ADMIN->value);

            $targetUser = User::findOrFail($data['user_id']);

            if (! $isSystemAdmin && $targetUser->league_id !== $actor->league_id) {
                throw new ApiException('Você só pode ajustar o saldo de usuários da sua própria liga.', 403);
            }

            $operation = TransactionOperation::from($data['operation']);
            $amount = (string) $data['amount'];

            if ($operation === TransactionOperation::Debit && bccomp($amount, (string) $targetUser->balance, 2) === 1) {
                throw new ApiException('O débito excede o saldo disponível do usuário.', 422);
            }

            $targetUser->balance = $operation === TransactionOperation::Credit
                ? bcadd((string) $targetUser->balance, $amount, 2)
                : bcsub((string) $targetUser->balance, $amount, 2);
            $targetUser->save();

            $transactionType = TransactionType::where(
                'name',
                $operation === TransactionOperation::Credit ? 'manual_credit' : 'manual_debit'
            )->firstOrFail();

            FinancialTransaction::create([
                'league_id' => $targetUser->league_id,
                'user_id' => $targetUser->id,
                'transaction_type_id' => $transactionType->id,
                'amount' => $amount,
                'description' => $data['description'] ?? ($operation === TransactionOperation::Credit ? 'Crédito manual' : 'Débito manual'),
            ]);

            return new UserResource($targetUser->load('roles.permissions'));
        });
    }

    public function destroy(array $data): void
    {
        $user = User::findOrFail($data['id']);

        $user->delete();
    }
}
