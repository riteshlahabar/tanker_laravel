<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;

trait CreatesUsers
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function userWithRole(RoleEnum $role, array $attributes = []): User
    {
        $user = User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'password' => 'secret-password',
            'status' => true,
            ...$attributes,
        ]);

        $user->assignRole($role->value);

        return $user;
    }

    protected function admin(): User
    {
        return $this->userWithRole(RoleEnum::Admin, ['email' => 'owner@example.com']);
    }
}
