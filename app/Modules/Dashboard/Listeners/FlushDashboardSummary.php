<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Listeners;

use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Order\Events\OrderStatusChanged;
use Illuminate\Support\Facades\Cache;

/**
 * The dashboard counters are cached; an order moving makes them stale.
 */
final class FlushDashboardSummary
{
    public function handle(OrderStatusChanged $event): void
    {
        Cache::forget(DashboardService::CACHE_KEY);
    }
}
