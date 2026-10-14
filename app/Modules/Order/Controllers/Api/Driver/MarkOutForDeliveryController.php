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

final class MarkOutForDeliveryController
{
    public function __construct(private readonly OrderServiceInterface $orders) {}

    public function __invoke(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('updateStatus', $order);

        try {
            $this->orders->moveTo($order, OrderStatusEnum::OutForDelivery, $request->user());
        } catch (InvalidStatusTransitionException $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(
            new OrderResource($order->load('statusLogs.changedBy')),
            __('On the way to the customer.'),
        );
    }
}
