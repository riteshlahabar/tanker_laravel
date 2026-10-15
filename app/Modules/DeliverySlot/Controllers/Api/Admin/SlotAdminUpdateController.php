<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\DeliverySlot\Contracts\DeliverySlotServiceInterface;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use App\Modules\DeliverySlot\Requests\UpdateDeliverySlotRequest;
use App\Modules\DeliverySlot\Resources\AdminDeliverySlotResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class SlotAdminUpdateController
{
    public function __construct(private readonly DeliverySlotServiceInterface $slots) {}

    public function __invoke(UpdateDeliverySlotRequest $request, DeliverySlot $slot): JsonResponse
    {
        Gate::authorize('update', $slot);

        return ApiResponse::success(
            new AdminDeliverySlotResource($this->slots->update($slot, $request->validated())),
            __('Delivery slot updated.'),
        );
    }
}
