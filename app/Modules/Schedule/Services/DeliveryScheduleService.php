<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Services;

use App\Models\User;
use App\Modules\Core\Services\BaseService;
use App\Modules\Schedule\Contracts\DeliveryScheduleRepositoryInterface;
use App\Modules\Schedule\Contracts\DeliveryScheduleServiceInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Support\Carbon;

final class DeliveryScheduleService extends BaseService implements DeliveryScheduleServiceInterface
{
    public function __construct(private readonly DeliveryScheduleRepositoryInterface $schedules) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): DeliverySchedule
    {
        return $this->transaction(function () use ($data, $actor): DeliverySchedule {
            $schedule = $this->schedules->store([
                ...$data,
                'created_by' => $actor->id,
            ]);

            // The cursor is the one source of truth for "when next", so it is
            // set from the pattern rather than trusted from the form.
            return $this->schedules->modify($schedule->id, [
                'next_run_date' => $this->firstRunDate($schedule)?->toDateString(),
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DeliverySchedule $schedule, array $data): DeliverySchedule
    {
        return $this->transaction(function () use ($schedule, $data): DeliverySchedule {
            $updated = $this->schedules->modify($schedule->id, $data);

            // Orders already generated are left exactly as they are; only the
            // cursor moves, and only if the new pattern no longer admits it.
            if ($this->cursorIsStale($updated)) {
                return $this->schedules->modify($updated->id, [
                    'next_run_date' => $this->firstRunDate($updated)?->toDateString(),
                ]);
            }

            return $updated;
        });
    }

    public function delete(DeliverySchedule $schedule): void
    {
        // Soft delete: the orders it produced keep their snapshots and stay in
        // the reports.
        $this->transaction(fn (): bool => $this->schedules->remove($schedule->id));
    }

    public function setStatus(DeliverySchedule $schedule, bool $status): DeliverySchedule
    {
        return $this->schedules->toggleStatus($schedule->id, $status);
    }

    /**
     * A paused schedule simply misses those dates — `next_run_date` keeps
     * moving, so there is no catch-up pile when it resumes.
     */
    public function pause(DeliverySchedule $schedule, ?Carbon $until = null): DeliverySchedule
    {
        return $this->schedules->modify($schedule->id, [
            'is_paused' => true,
            'pause_until' => $until?->toDateString(),
        ]);
    }

    public function resume(DeliverySchedule $schedule): DeliverySchedule
    {
        return $this->transaction(function () use ($schedule): DeliverySchedule {
            $resumed = $this->schedules->modify($schedule->id, [
                'is_paused' => false,
                'pause_until' => null,
            ]);

            // Picked up from today rather than from where it stopped, so a
            // long pause does not generate a backlog the moment it resumes.
            return $this->schedules->modify($resumed->id, [
                'next_run_date' => $this->firstRunDate($resumed, Carbon::today())?->toDateString(),
            ]);
        });
    }

    /**
     * The first due date on or after $from — today when nothing is given, or
     * the start date when that is still in the future.
     */
    private function firstRunDate(DeliverySchedule $schedule, ?Carbon $from = null): ?Carbon
    {
        $pattern = $schedule->pattern();
        $start = $from ?? Carbon::today();
        $cursor = $start->copy()->startOfDay()->max(Carbon::parse($schedule->start_date)->startOfDay());

        return $pattern->matches($cursor) ? $cursor : $pattern->next($cursor);
    }

    private function cursorIsStale(DeliverySchedule $schedule): bool
    {
        if ($schedule->next_run_date === null) {
            return true;
        }

        return ! $schedule->pattern()->matches(Carbon::parse($schedule->next_run_date));
    }
}
