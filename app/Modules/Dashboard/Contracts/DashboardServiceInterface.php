<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Contracts;

use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface DashboardServiceInterface
{
    /**
     * Counters shown on the admin dashboard tiles.
     *
     * @return array<string, int|float>
     */
    public function summary(): array;

    /**
     * The outstanding orders the dashboard lists under its tiles, newest
     * first, plus the total so a capped list can say what it is hiding.
     *
     * Deliberately NOT part of [summary]: those counters are cached for a
     * minute, and this list has to drop a delivered order the moment it is
     * delivered rather than up to a minute later.
     *
     * @return array{orders: Collection<int, Order>, total: int}
     */
    public function openOrders(): array;
}
