<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Repositories;

use App\Models\User;
use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Schedule\Contracts\DeliveryScheduleRepositoryInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class DeliveryScheduleRepository extends BaseRepository implements DeliveryScheduleRepositoryInterface
{
    public function model(): string
    {
        return DeliverySchedule::class;
    }

    /**
     * An admin sees every schedule; a customer only their own. A driver has no
     * business reading schedules at all — they see the orders a schedule
     * produced, which the order scope already covers.
     */
    public function visibleTo(User $user): Builder
    {
        $query = DeliverySchedule::query();

        return match (true) {
            $user->isCustomer() => $query->forCustomer($user->id),
            $user->isAdmin() => $query,
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function findForUser(User $user, int $id): DeliverySchedule
    {
        return $this->visibleTo($user)->findOrFail($id);
    }

    public function activeForCustomer(int $customerId): ?DeliverySchedule
    {
        return DeliverySchedule::query()
            ->forCustomer($customerId)
            ->active()
            ->with(['slot', 'preferredDriver:id,first_name,last_name,mobile'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, DeliverySchedule>
     */
    public function dueBy(Carbon $date): Collection
    {
        return DeliverySchedule::query()
            ->dueBy($date)
            // Eager-loaded because the generator reads all three for every row
            // it creates — one query each instead of one per schedule.
            ->with(['customer', 'slot', 'preferredDriver'])
            ->orderBy('next_run_date')
            ->orderBy('id')
            ->get();
    }

    public function countDueOn(Carbon $date): int
    {
        return DeliverySchedule::query()
            ->active()
            ->where('is_paused', false)
            ->get(['id', 'interval_days', 'weekdays', 'start_date', 'end_date'])
            ->filter(fn (DeliverySchedule $schedule): bool => $schedule->pattern()->matches($date))
            ->count();
    }
}
