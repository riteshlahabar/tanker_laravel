<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Controllers;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\DeliverySlot\Contracts\DeliverySlotServiceInterface;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class SlotStatusController
{
    public function __construct(private readonly DeliverySlotServiceInterface $slots) {}

    public function __invoke(Request $request, DeliverySlot $slot): JsonResponse
    {
        Gate::authorize('update', $slot);

        $request->validate(['status' => ['required', 'boolean']]);

        return ApiResponse::success(
            ['status' => $this->slots->setStatus($slot, $request->boolean('status'))->status],
            __('Status updated successfully.'),
        );
    }
}
