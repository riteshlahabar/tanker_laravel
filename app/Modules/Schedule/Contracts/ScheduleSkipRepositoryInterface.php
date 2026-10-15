<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Contracts;

use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Models\ScheduleSkip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface ScheduleSkipRepositoryInterface
{
    public function find(DeliverySchedule $schedule, Carbon $date): ?ScheduleSkip;

    public function exists(DeliverySchedule $schedule, Carbon $date): bool;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(array $attributes): ScheduleSkip;

    public function remove(ScheduleSkip $skip): void;

    /**
     * Every skip on a schedule, newest date first, for the admin panel.
     *
     * @return Collection<int, ScheduleSkip>
     */
    public function forSchedule(DeliverySchedule $schedule): Collection;

    /**
     * The skipped dates in a range, keyed by date string — one query behind
     * the "next 14 dates" list rather than one per date.
     *
     * @return Collection<string, ScheduleSkip>
     */
    public function betweenKeyedByDate(DeliverySchedule $schedule, Carbon $from, Carbon $to): Collection;

    /**
     * How many dates a schedule skipped inside a range, for the adherence
     * report.
     */
    public function countBetween(int $scheduleId, Carbon $from, Carbon $to): int;
}
