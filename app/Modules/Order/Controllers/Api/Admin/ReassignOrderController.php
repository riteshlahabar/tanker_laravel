<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Requests\ReassignOrderRequest;
use App\Modules\Order\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class ReassignOrderController
{
    public function __construct(private readonly OrderServiceInterface $orders) {}

    public function __invoke(ReassignOrderRequest $request, Order $order): JsonResponse
    {
        Gate::authorize('reassign', $order);

        try {
            $this->orders->reassign(
                order: $order,
                driverId: (int) $request->input('driver_id'),
                spotId: $request->input('spot_id') === null ? null : (int) $request->input('spot_id'),
                actor: $request->user(),
                reason: $request->input('reason'),
            );
        } catch (OrderAssignmentException $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(
            new OrderResource($order->load(['driver', 'tanker', 'spot', 'statusLogs.changedBy'])),
            __('Driver changed.'),
        );
    }
}
