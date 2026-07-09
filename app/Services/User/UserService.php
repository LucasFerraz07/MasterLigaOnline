<?php

namespace App\Services\User;

use App\Enums\UserType;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function index(array $data): UserCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $trashed = $data['with_trashed'] ?? null;

        $query = User::query()
            ->with('roles')
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
        $user = User::with('roles')->findOrFail($data['id']);

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

    public function destroy(array $data): void
    {
        $user = User::findOrFail($data['id']);

        $user->delete();
    }
}
