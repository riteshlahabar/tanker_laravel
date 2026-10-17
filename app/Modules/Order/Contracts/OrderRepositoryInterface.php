<?php

declare(strict_types=1);

namespace App\Modules\Order\Contracts;

use App\Models\User;
use App\Modules\Core\Contracts\BaseRepositoryInterface;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Orders a given user is allowed to see: everything for an admin, only
     * their own for a driver or a customer. Enforced here as well as in the
     * policy, so no controller can widen it by accident.
     */
    public function visibleTo(User $user): Builder;

    public function findForUser(User $user, int $id): Order;

    /**
     * @return array<string, int|float>
     */
    public function todaySummary(): array;

    /**
     * Orders that still need work doing — everything but delivered and
     * cancelled — newest first, for the dashboard's live queue. A delivered
     * order drops out of this by definition, which is the whole point: the
     * dashboard shows what is outstanding, the Orders screen shows everything.
     *
     * @return Collection<int, Order>
     */
    public function open(int $limit): Collection;

    /**
     * How many orders are outstanding in total, so the dashboard can say that
     * its capped list is only part of the picture.
     */
    public function openCount(): int;
}
