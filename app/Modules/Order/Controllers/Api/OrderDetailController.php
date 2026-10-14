<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Order detail with the tracking timeline. The policy decides, so a customer
 * asking for someone else's id is refused rather than served.
 */
final class OrderDetailController
{
    public function __invoke(Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        return ApiResponse::success(new OrderResource($order->load([
            'customer', 'driver', 'tanker', 'spot', 'statusLogs.changedBy',
        ])));
    }
}
