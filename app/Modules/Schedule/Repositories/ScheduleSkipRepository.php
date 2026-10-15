<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Repositories;

use App\Modules\Schedule\Contracts\ScheduleSkipRepositoryInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Models\ScheduleSkip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Not a BaseRepository: skips are only ever reached through their schedule and
 * date, never by id from a URL, so the generic crud surface would be dead
 * weight and a loose `findOrFail($id)` would be a way to read another
 * customer's record.
 */
final class ScheduleSkipRepository implements ScheduleSkipRepositoryInterface
{
    public function find(DeliverySchedule $schedule, Carbon $date): ?ScheduleSkip
    {
        return ScheduleSkip::query()
            ->where('delivery_schedule_id', $schedule->id)
            ->onDate($date)
            ->first();
    }

    public function exists(DeliverySchedule $schedule, Carbon $date): bool
    {
        return ScheduleSkip::query()
            ->where('delivery_schedule_id', $schedule->id)
            ->onDate($date)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(array $attributes): ScheduleSkip
    {
        return ScheduleSkip::query()->create($attributes);
    }

    public function remove(ScheduleSkip $skip): void
    {
        $skip->delete();
    }

    /**
     * @return Collection<int, ScheduleSkip>
     */
    public function forSchedule(DeliverySchedule $schedule): Collection
    {
        return ScheduleSkip::query()
            ->where('delivery_schedule_id', $schedule->id)
            ->with(['requester:id,first_name,last_name', 'order:id,order_no,status'])
            ->orderByDesc('skip_date')
            ->get();
    }

    /**
     * @return Collection<string, ScheduleSkip>
     */
    public function betweenKeyedByDate(DeliverySchedule $schedule, Carbon $from, Carbon $to): Collection
    {
        return ScheduleSkip::query()
            ->where('delivery_schedule_id', $schedule->id)
            ->whereBetween('skip_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn (ScheduleSkip $skip): string => Carbon::parse($skip->skip_date)->toDateString());
    }

    public function countBetween(int $scheduleId, Carbon $from, Carbon $to): int
    {
        return ScheduleSkip::query()
            ->where('delivery_schedule_id', $scheduleId)
            ->whereBetween('skip_date', [$from->toDateString(), $to->toDateString()])
            ->count();
    }
}
