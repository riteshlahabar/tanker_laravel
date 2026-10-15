<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\DeliverySlot\Contracts\DeliverySlotRepositoryInterface;
use App\Modules\DeliverySlot\Resources\DeliverySlotResource;
use Illuminate\Http\JsonResponse;

/**
 * Active slots, so the app can offer the same windows the panel does.
 */
final class SlotApiListController
{
    public function __construct(private readonly DeliverySlotRepositoryInterface $slots) {}

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(
            DeliverySlotResource::collection($this->slots->active())->resolve(),
        );
    }
}
