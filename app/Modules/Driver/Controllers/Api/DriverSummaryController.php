<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers\Api;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Driver\Resources\DriverResource;
use App\Modules\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The driver's home screen: who they are, which tanker they have, and today's
 * workload at a glance.
 */
final class DriverSummaryController
{
    public function __invoke(Request $request): JsonResponse
    {
        $driver = $request->user()->load('currentAssignment.tanker');
        $today = now()->toDateString();

        $mine = Order::query()->where('driver_id', $driver->id);

        return ApiResponse::success([
            'driver' => new DriverResource($driver),
            'today' => [
                'assigned' => (int) $mine->clone()->whereDate('delivery_date', $today)->open()->count(),
                'delivered' => (int) $mine->clone()->whereDate('delivered_at', $today)->count(),
                // Tanker-loads, not money: the driver is salaried, so there is
                // no daily earning to show.
                'tankers' => (int) $mine->clone()
                    ->where('status', OrderStatusEnum::Delivered)
                    ->whereDate('delivered_at', $today)
                    ->sum('tanker_count'),
            ],
        ]);
    }
}
