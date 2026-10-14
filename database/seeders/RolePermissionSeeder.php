<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate($role->value, 'web')
                ->syncPermissions(PermissionEnum::forRole($role));
        }
    }
}
