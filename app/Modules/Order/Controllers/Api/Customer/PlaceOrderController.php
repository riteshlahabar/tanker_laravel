<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api\Customer;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Requests\Api\PlaceOrderRequest;
use App\Modules\Order\Resources\OrderResource;
use App\Modules\Order\Services\OrderPricing;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class PlaceOrderController
{
    public function __construct(
        private readonly OrderServiceInterface $orders,
        private readonly OrderPricing $pricing,
    ) {}

    public function __invoke(PlaceOrderRequest $request): JsonResponse
    {
        $amount = $this->pricing->amountFor((int) $request->input('quantity_litres'));

        $order = $this->orders->place($request->orderData($amount), $request->user());

        return ApiResponse::success(
            new OrderResource($order->load(['customer', 'statusLogs.changedBy'])),
            __('Order placed. We will assign a tanker shortly.'),
            Response::HTTP_CREATED,
        );
    }
}
