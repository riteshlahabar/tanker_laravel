<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Contracts;

use App\Models\User;
use App\Modules\Core\Contracts\BaseRepositoryInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface DeliveryScheduleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Schedules a given user may see: everything for an admin, only their own
     * for a customer. Enforced here as well as in the policy, so no controller
     * can widen it by accident.
     */
    public function visibleTo(User $user): Builder;

    public function findForUser(User $user, int $id): DeliverySchedule;

    /**
     * The customer's own live schedule, or null when they are not on one.
     */
    public function activeForCustomer(int $customerId): ?DeliverySchedule;

    /**
     * Due on or before the target date, switched on and not paused — what the
     * generator walks.
     *
     * @return Collection<int, DeliverySchedule>
     */
    public function dueBy(Carbon $date): Collection;

    /**
     * How many deliveries the live schedules will produce on a given date —
     * the dashboard's "scheduled tomorrow" tile.
     */
    public function countDueOn(Carbon $date): int;
}
