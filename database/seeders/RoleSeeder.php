<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

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
            'league.restore',
            'league.force-delete',
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'league-category-price.view',
            'league-category-price.update',
            'season.view',
            'season.create',
            'season.update',
            'player.view',
            'player.update',
            'squad.view',
            'squad.create',
            'squad.update',
            'club-identity.view',
            'club-identity.update',
            'transfer.view',
            'transfer-bid.view',
            'transfer-bid.create',
            'transfer-bid.update',
            'mulct.view',
            'mulct.create',
            'financial-transaction.view',
            'user.balance',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // system_admin e league_admin: role 1:1 com o user_type (sem variação de cargo).
        $systemAdmin = Role::firstOrCreate(['name' => UserType::SYSTEM_ADMIN->value, 'guard_name' => 'api']);
        $leagueAdmin = Role::firstOrCreate(['name' => UserType::LEAGUE_ADMIN->value, 'guard_name' => 'api']);

        // user_type "user": cargos com permissões distintas dentro do mesmo tipo.
        $coOwner = Role::firstOrCreate(['name' => UserRole::CO_OWNER->value, 'guard_name' => 'api']);
        $default = Role::firstOrCreate(['name' => UserRole::DEFAULT->value, 'guard_name' => 'api']);

        $systemAdmin->syncPermissions(Permission::where('guard_name', 'api')->get());

        $leagueAdmin->syncPermissions([
            'league.view',
            'league.update',
            'league-category-price.view',
            'league-category-price.update',
            'season.view',
            'season.create',
            'season.update',
            'player.view',
            'squad.view',
            'squad.create',
            'squad.update',
            'club-identity.view',
            'club-identity.update',
            'transfer.view',
            'transfer-bid.view',
            'transfer-bid.create',
            'transfer-bid.update',
            'mulct.view',
            'mulct.create',
            'financial-transaction.view',
            'user.balance',
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
        ]);

        $coOwner->syncPermissions([
            'league.view',
            'league.update',
            'player.view',
            'league-category-price.view',
            'league-category-price.update',
            'squad.view',
            'squad.create',
            'squad.update',
            'club-identity.view',
            'club-identity.update',
            'transfer.view',
            'transfer-bid.view',
            'transfer-bid.create',
            'transfer-bid.update',
            'mulct.view',
            'mulct.create',
            'financial-transaction.view',
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
        ]);

        $default->syncPermissions([
            'league.view',
            'player.view',
            'squad.view',
            'squad.create',
            'squad.update',
            'club-identity.view',
            'club-identity.update',
            'transfer.view',
            'transfer-bid.view',
            'transfer-bid.create',
            'transfer-bid.update',
            'mulct.view',
            'mulct.create',
            'financial-transaction.view',
            'user.view',
            'user.update'
        ]);
    }
}
