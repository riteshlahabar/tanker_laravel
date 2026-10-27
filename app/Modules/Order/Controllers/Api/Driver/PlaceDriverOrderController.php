<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api\Driver;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Requests\Api\DriverPlaceOrderRequest;
use App\Modules\Order\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * A driver taking an order on the route.
 *
 * The same `placeForDriver()` the admin's form calls, so the order gets both
 * status-log rows (placed, then assigned), the assignment guards still run —
 * an inactive driver, or one holding no tanker, is refused with 400 — and the
 * filling point's refill charge is snapshotted identically. The only
 * difference from the admin path is *who* it is assigned to, and that is not
 * negotiable here: it comes from the token, never the body.
 *
 * No notification work was needed: `order.placed` already reaches the admin
 * and the customer, so the office is told the moment a driver writes one.
 */
final class PlaceDriverOrderController
{
    public function __construct(private readonly OrderServiceInterface $orders) {}

    public function __invoke(DriverPlaceOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orders->placeForDriver(
                $request->orderData(),
                $request->driverId(),
                $request->spotId(),
                $request->tankerRate(),
                $request->user(),
            );
        } catch (OrderAssignmentException $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(
            new OrderResource($order->load(['customer', 'driver', 'tanker', 'spot', 'statusLogs.changedBy'])),
            __('Order :no placed.', ['no' => $order->order_no]),
            Response::HTTP_CREATED,
        );
    }
}
