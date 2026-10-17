<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Dashboard\Contracts\DashboardServiceInterface;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Models\Order;
use App\Modules\Schedule\Contracts\DeliveryScheduleRepositoryInterface;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The counters are cached briefly so the dashboard never costs the request a
 * pile of counts; the cache is dropped whenever an order changes status.
 *
 * The open-orders list is not cached — see [openOrders].
 */
final class DashboardService implements DashboardServiceInterface
{
    public const CACHE_KEY = 'dashboard.summary';

    private const CACHE_TTL = 60;

    /**
     * How many outstanding orders the dashboard lists before sending the owner
     * to the Orders screen. The dashboard is a glance, not a worklist.
     */
    private const OPEN_LIMIT = 10;

    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly DeliveryScheduleRepositoryInterface $schedules,
    ) {}

    /**
     * @return array<string, int|float>
     */
    public function summary(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn (): array => [
            'tankers' => Tanker::query()->where('status', true)->count(),
            'drivers' => User::query()->where('status', true)->role(RoleEnum::Driver->value)->count(),
            'customers' => User::query()->where('status', true)->role(RoleEnum::Customer->value)->count(),
            // Standing orders are generated the night before, so the owner
            // wants tomorrow's count to plan drivers against.
            'scheduled_tomorrow' => $this->schedules->countDueOn(Carbon::tomorrow()),
            ...$this->orders->todaySummary(),
        ]);
    }

    /**
     * Read fresh on every request, unlike the counters above: the owner's rule
     * is that a delivered order leaves this list straight away, and a minute
     * of cache would show a delivery that has already happened.
     *
     * @return array{orders: Collection<int, Order>, total: int}
     */
    public function openOrders(): array
    {
        return [
            'orders' => $this->orders->open(self::OPEN_LIMIT),
            'total' => $this->orders->openCount(),
        ];
    }
}
