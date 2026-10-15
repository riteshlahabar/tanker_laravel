<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Contracts;

use App\Models\User;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Support\Carbon;

interface DeliveryScheduleServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): DeliverySchedule;

    /**
     * Changing a schedule never rewrites orders it has already generated —
     * only what it will produce from here on.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(DeliverySchedule $schedule, array $data): DeliverySchedule;

    public function delete(DeliverySchedule $schedule): void;

    public function setStatus(DeliverySchedule $schedule, bool $status): DeliverySchedule;

    /**
     * Paused dates are simply missed — there is no catch-up when it resumes.
     */
    public function pause(DeliverySchedule $schedule, ?Carbon $until = null): DeliverySchedule;

    public function resume(DeliverySchedule $schedule): DeliverySchedule;
}
