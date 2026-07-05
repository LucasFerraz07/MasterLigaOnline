<?php

namespace Database\Seeders;

use App\Enums\UserType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'subscription.view',
            'subscription.create',
            'subscription.update',
            'subscription.delete',
            'club.view',
            'club.create',
            'club.update',
            'club.delete',
            'transaction-type.view',
            'transaction-type.create',
            'transaction-type.update',
            'transaction-type.delete',
            'league.view',
            'league.create',
            'league.update',
            'league.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $systemAdmin = Role::firstOrCreate(['name' => UserType::SYSTEM_ADMIN->value, 'guard_name' => 'api']);
        $leagueAdmin = Role::firstOrCreate(['name' => UserType::LEAGUE_ADMIN->value, 'guard_name' => 'api']);
        $user = Role::firstOrCreate(['name' => UserType::USER->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserType::USER->value, 'guard_name' => 'api']);

        $systemAdmin->syncPermissions(Permission::where('guard_name', 'api')->get());

        $leagueAdmin->syncPermissions([
            'league.view',
        ]);

        $user->syncPermissions([
            'league.view',
        ]);
    }
}
