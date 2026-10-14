<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Database\Seeder;

final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::withTrashed()->updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'Owner',
                'last_name' => 'Admin',
                'password' => '12345678',
                'status' => true,
                'system_reserve' => true,
                'deleted_at' => null,
            ],
        );

        if (! $admin->hasRole(RoleEnum::Admin->value)) {
            $admin->assignRole(RoleEnum::Admin->value);
        }
    }
}
