<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Policies;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Tanker\Models\Tanker;

final class TankerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::TankerView->value);
    }

    public function view(User $user, Tanker $tanker): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::TankerManage->value);
    }

    public function update(User $user, Tanker $tanker): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Tanker $tanker): bool
    {
        return $this->create($user);
    }
}
