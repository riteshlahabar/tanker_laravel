<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Services;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Schedule\Models\DeliverySchedule;
use RuntimeException;

/**
 * Who a generated order is recorded as being placed by.
 *
 * The nightly run has no signed-in user, but `OrderService::place()` needs one
 * for the status log and the audit columns — a standing order is the admin's
 * doing, so it is attributed to the admin who set the schedule up, falling
 * back to any active admin if that account is gone.
 */
final class GeneratorActor
{
    public function for(DeliverySchedule $schedule): User
    {
        $creator = $schedule->creator;

        if ($creator !== null && $creator->status && $creator->isAdmin()) {
            return $creator;
        }

        return $this->anyAdmin();
    }

    public function anyAdmin(): User
    {
        $admin = User::query()
            ->active()
            ->role(RoleEnum::Admin->value)
            ->orderBy('id')
            ->first();

        if ($admin === null) {
            throw new RuntimeException(
                'No active admin account exists to attribute scheduled orders to.'
            );
        }

        return $admin;
    }
}
