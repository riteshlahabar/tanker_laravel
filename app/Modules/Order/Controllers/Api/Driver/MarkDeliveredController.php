<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api\Driver;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\InvalidStatusTransitionException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Delivery confirmed. This is the moment `delivered_at` and the driver's
 * earning snapshot become part of the reports.
 */
final class MarkDeliveredController
{
    public function __construct(private readonly OrderServiceInterface $orders) {}

    public function __invoke(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('updateStatus', $order);

        $request->validate(['note' => ['nullable', 'string', 'max:255']]);

        try {
            $this->orders->moveTo(
                $order,
                OrderStatusEnum::Delivered,
                $request->user(),
                $request->string('note')->value() ?: null,
            );
        } catch (InvalidStatusTransitionException $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(
            new OrderResource($order->load('statusLogs.changedBy')),
            __('Delivery recorded.'),
        );
    }
}
