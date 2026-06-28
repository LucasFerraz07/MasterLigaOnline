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
            'company.create',
            'company.view',
            'company.show',
            'company.update',
            'company.delete',
            'vitalab.create',
            'vitalab.view',
            'vitalab.update',
            'vitalab.delete',
            'measurement.create',
            'measurement.view',
            'measurement.update',
            'measurement.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $systemAdmin = Role::firstOrCreate(['name' => UserType::SYSTEM_ADMIN->value, 'guard_name' => 'api']);
        $tenantAdmin = Role::firstOrCreate(['name' => UserType::TENANT_ADMIN->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserType::USER->value, 'guard_name' => 'api']);

        $systemAdmin->syncPermissions(Permission::where('guard_name', 'api')->get());

        $tenantAdmin->syncPermissions([
            'company.show',
            'vitalab.create',
            'vitalab.view',
            'vitalab.update',
            'vitalab.delete',
            'measurement.create',
            'measurement.view',
            'measurement.update',
            'measurement.delete',
        ]);
    }
}
