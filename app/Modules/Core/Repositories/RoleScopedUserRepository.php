<?php

declare(strict_types=1);

namespace App\Modules\Core\Repositories;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Builder;

/**
 * Drivers and customers are both rows in `users` separated by role, so both
 * modules share this base: every read is scoped to one role, which means a
 * customer id can never be edited through the driver module or vice versa.
 */
abstract class RoleScopedUserRepository extends BaseRepository
{
    abstract protected function role(): RoleEnum;

    public function model(): string
    {
        return User::class;
    }

    public function scopedQuery(): Builder
    {
        return User::query()->role($this->role()->value);
    }

    public function findOrFail(int $id): User
    {
        return $this->scopedQuery()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(array $attributes): User
    {
        $user = User::query()->create($attributes);
        $user->assignRole($this->role()->value);

        return $user;
    }
}
