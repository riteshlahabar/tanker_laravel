<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Requests\StoreOrderRequest;
use App\Modules\Order\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin creating an order for a customer — the same request class the panel
 * uses, so validation cannot drift between the two.
 */
final class CreateOrderController
{
    public function __construct(private readonly OrderServiceInterface $orders) {}

    public function __invoke(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orders->place($request->validated(), $request->user());

        return ApiResponse::success(
            new OrderResource($order->load(['customer', 'statusLogs.changedBy'])),
            __('Order :no placed.', ['no' => $order->order_no]),
            Response::HTTP_CREATED,
        );
    }
}
