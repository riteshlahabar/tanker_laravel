<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Requests\StoreOrderRequest;
use App\Modules\Order\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin creating an order for a customer — the same request class the panel
 * uses, so validation cannot drift between the two.
 *
 * And now the same service call. It used to be `place()`, which ignored the
 * `driver_id` the request had just insisted on: an order written from the admin
 * app arrived unassigned and priced at 0, while the identical form in the panel
 * produced an assigned, priced one. The app mirrors the panel, so it places and
 * assigns in one step too.
 */
final class CreateOrderController
{
    public function __construct(private readonly OrderServiceInterface $orders) {}

    public function __invoke(StoreOrderRequest $request): JsonResponse
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
            __('Order :no placed and assigned.', ['no' => $order->order_no]),
            Response::HTTP_CREATED,
        );
    }
}
