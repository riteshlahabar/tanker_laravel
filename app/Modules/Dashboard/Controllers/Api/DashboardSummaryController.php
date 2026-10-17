<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Dashboard\Contracts\DashboardServiceInterface;
use App\Modules\Order\Resources\OrderListResource;
use Illuminate\Http\JsonResponse;

/**
 * The admin home screen in the app — the same cached counters the panel
 * dashboard shows, plus the outstanding orders listed under them.
 *
 * The list rides along on this one call rather than getting an endpoint of its
 * own: the dashboard always wants both, and two requests to draw one screen
 * would only double the latency.
 */
final class DashboardSummaryController
{
    public function __construct(private readonly DashboardServiceInterface $dashboard) {}

    public function __invoke(): JsonResponse
    {
        $open = $this->dashboard->openOrders();

        return ApiResponse::success([
            ...$this->dashboard->summary(),
            'open_orders' => OrderListResource::collection($open['orders'])->resolve(),
            'open_total' => $open['total'],
        ]);
    }
}
