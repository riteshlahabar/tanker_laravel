<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\DeliverySlot\Contracts\DeliverySlotServiceInterface;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class SlotAdminDeleteController
{
    public function __construct(private readonly DeliverySlotServiceInterface $slots) {}

    public function __invoke(DeliverySlot $slot): JsonResponse
    {
        Gate::authorize('delete', $slot);

        $this->slots->delete($slot);

        return ApiResponse::success(null, __('Delivery slot deleted.'));
    }
}
