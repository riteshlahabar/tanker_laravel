<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Policies;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\DeliverySlot\Models\DeliverySlot;

final class DeliverySlotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::SlotView->value);
    }

    public function view(User $user, DeliverySlot $slot): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::SlotManage->value);
    }

    public function update(User $user, DeliverySlot $slot): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, DeliverySlot $slot): bool
    {
        return $this->create($user);
    }
}
