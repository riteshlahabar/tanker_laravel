<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\DeliverySlot\Contracts\DeliverySlotServiceInterface;
use App\Modules\DeliverySlot\Requests\StoreDeliverySlotRequest;
use App\Modules\DeliverySlot\Resources\AdminDeliverySlotResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class SlotAdminStoreController
{
    public function __construct(private readonly DeliverySlotServiceInterface $slots) {}

    public function __invoke(StoreDeliverySlotRequest $request): JsonResponse
    {
        return ApiResponse::success(
            new AdminDeliverySlotResource($this->slots->create($request->validated())),
            __('Delivery slot added.'),
            Response::HTTP_CREATED,
        );
    }
}
