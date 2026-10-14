<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\InvalidStatusTransitionException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Requests\CancelOrderRequest;
use App\Modules\Order\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class CancelOrderController
{
    public function __construct(private readonly OrderServiceInterface $orders) {}

    public function __invoke(CancelOrderRequest $request, Order $order): JsonResponse
    {
        Gate::authorize('cancel', $order);

        try {
            $this->orders->cancel($order, $request->string('cancel_reason')->value(), $request->user());
        } catch (InvalidStatusTransitionException $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(
            new OrderResource($order->load('statusLogs.changedBy')),
            __('Order cancelled.'),
        );
    }
}
