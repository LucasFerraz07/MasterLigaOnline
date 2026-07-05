<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@teste.com')],
            [
                'username'     => 'System Admin',
                'password' => bcrypt(env('ADMIN_PASSWORD', 'password')),
                'phone'    => env('ADMIN_PHONE', '00000000000'),
            ]
        );

        $admin->assignRole(UserType::SYSTEM_ADMIN->value);
    }
}
