<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Policies;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Schedule\Models\DeliverySchedule;

/**
 * Who may see and change a standing order.
 *
 * A customer may read their own routine and call off a single day of it, but
 * never create, edit or delete one — that is the admin's job, which is the
 * whole point of a standing order.
 */
final class DeliverySchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ScheduleView->value);
    }

    public function view(User $user, DeliverySchedule $schedule): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $user->isAdmin() || $schedule->customer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::ScheduleManage->value);
    }

    public function update(User $user, DeliverySchedule $schedule): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, DeliverySchedule $schedule): bool
    {
        return $this->create($user);
    }

    /**
     * Calling off one date. The admin may do it for anyone; a customer only on
     * their own routine. The six-hour notice is a separate rule and lives in
     * SkipWindow — this only answers "is this your routine".
     */
    public function skip(User $user, DeliverySchedule $schedule): bool
    {
        if ($user->isAdmin()) {
            return $user->can(PermissionEnum::ScheduleManage->value);
        }

        return $user->can(PermissionEnum::ScheduleSkip->value)
            && $schedule->customer_id === $user->id;
    }
}
