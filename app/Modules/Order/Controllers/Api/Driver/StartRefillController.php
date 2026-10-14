<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api\Driver;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\InvalidStatusTransitionException;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Requests\Api\StartRefillRequest;
use App\Modules\Order\Resources\OrderResource;
use App\Modules\Order\Services\OrderRefillRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * "I am filling the tanker now" — optionally naming the spot the driver
 * actually chose on the route.
 */
final class StartRefillController
{
    public function __construct(
        private readonly OrderServiceInterface $orders,
        private readonly OrderRefillRecorder $refills,
    ) {}

    public function __invoke(StartRefillRequest $request, Order $order): JsonResponse
    {
        Gate::authorize('updateStatus', $order);

        try {
            $this->refills->recordSpot($order, $request->input('spot_id'));

            $this->orders->moveTo(
                $order,
                OrderStatusEnum::Refilling,
                $request->user(),
                $request->string('note')->value() ?: null,
            );
        } catch (InvalidStatusTransitionException|OrderAssignmentException $e) {
            // A rejected spot or a disallowed move is the driver's mistake to
            // correct, not a server fault — answer 400 with the reason.
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(
            new OrderResource($order->load(['spot', 'statusLogs.changedBy'])),
            __('Refill started.'),
        );
    }
}
