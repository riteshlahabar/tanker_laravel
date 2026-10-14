<?php

declare(strict_types=1);

namespace App\Modules\Order\Contracts;

use App\Models\User;
use App\Modules\Core\Contracts\BaseRepositoryInterface;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Builder;

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
}
