<?php

declare(strict_types=1);

namespace App\Modules\Spot\Policies;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Spot\Models\Spot;

final class SpotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::SpotView->value);
    }

    public function view(User $user, Spot $spot): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::SpotManage->value);
    }

    public function update(User $user, Spot $spot): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Spot $spot): bool
    {
        return $this->create($user);
    }
}
