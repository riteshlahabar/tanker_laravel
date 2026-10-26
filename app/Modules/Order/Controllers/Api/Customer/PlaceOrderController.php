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
        // No vehicle is known yet, and the rate per tanker belongs to the
        // vehicle, so this prices at 0 — honestly: the price is not known until
        // an admin assigns a driver, at which point assignment prices it from
        // the tanker that actually goes out.
        $price = $this->pricing->priceFrom(null, (int) $request->input('tanker_count'));

        $order = $this->orders->place($request->orderData($price), $request->user());

        return ApiResponse::success(
            new OrderResource($order->load(['customer', 'statusLogs.changedBy'])),
            __('Order placed. We will assign a tanker shortly.'),
            Response::HTTP_CREATED,
        );
    }
}
