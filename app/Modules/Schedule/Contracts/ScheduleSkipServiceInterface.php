<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Contracts;

use App\Models\User;
use App\Modules\Schedule\Exceptions\ScheduleSkipException;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Models\ScheduleSkip;
use App\Modules\Schedule\Support\UpcomingDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface ScheduleSkipServiceInterface
{
    /**
     * Call off one date. Cancels the order when one already exists, records
     * the skip either way, and never moves `next_run_date`.
     *
     * @throws ScheduleSkipException when the
     *                               six hours' notice has passed or the water is already bought
     */
    public function skip(
        DeliverySchedule $schedule,
        Carbon $date,
        User $actor,
        ?string $reason = null,
    ): ScheduleSkip;

    /**
     * Put the date back: drop the skip and, if it is still due, regenerate the
     * order for that one date.
     *
     * @throws ScheduleSkipException
     */
    public function restore(DeliverySchedule $schedule, Carbon $date, User $actor): void;

    /**
     * The next $count due dates with everything the app needs to draw a Skip
     * button: the order so far, whether it is skipped, and whether it may
     * still be skipped by this actor.
     *
     * @return Collection<int, UpcomingDate>
     */
    public function upcoming(DeliverySchedule $schedule, User $actor, int $count = 14): Collection;
}
