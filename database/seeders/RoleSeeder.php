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
            // Planos
            'plan.view',
            'plan.create',
            'plan.update',
            'plan.delete',
            // Clubes
            'club.view',
            'club.create',
            'club.update',
            'club.delete',
            // Tipo de transação
            'transaction-type.view',
            'transaction-type.create',
            'transaction-type.update',
            'transaction-type.delete',
            // Ligas
            'league.view',
            'league.create',
            'league.update',
            'league.delete',
            'league.restore',
            'league.force-delete',
            // Usuários
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'user.balance',
            // Preço das categorias
            'league-category-price.view',
            'league-category-price.update',
            // Temporadas
            'season.view',
            'season.create',
            'season.update',
            // Partidas
            'match.view',
            'match.update',
            // Jogadores
            'player.view',
            'player.update',
            'player.import',
            // Escalação
            'squad.view',
            'squad.create',
            'squad.update',
            // ClubIdentity
            'club-identity.view',
            'club-identity.update',
            // Histórico de transferências
            'transfer.view',
            // Transferências
            'transfer-bid.view',
            'transfer-bid.create',
            'transfer-bid.update',
            'mulct.view',
            'mulct.create',
            // Histórico financeiro
            'financial-transaction.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $systemAdmin = Role::firstOrCreate(['name' => UserType::SYSTEM_ADMIN->value, 'guard_name' => 'api']);
        $leagueAdmin = Role::firstOrCreate(['name' => UserType::LEAGUE_ADMIN->value, 'guard_name' => 'api']);

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
            'match.view',
            'match.update',
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
            'season.view',
            'player.view',
            'league-category-price.view',
            'league-category-price.update',
            'match.view',
            'match.update',
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
            'league-category-price.view',
            'season.view',
            'match.view',
            'match.update',
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
            'user.update',
        ]);
    }
}
