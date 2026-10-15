<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Controllers\Api\Admin;

use App\Modules\Core\Controllers\Api\Concerns\RespondsWithList;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use App\Modules\DeliverySlot\Resources\AdminDeliverySlotResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class SlotAdminListController
{
    use RespondsWithList;

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', DeliverySlot::class);

        return $this->listResponse(
            DeliverySlot::query()->ordered(),
            $request,
            AdminDeliverySlotResource::class,
            ['name'],
        );
    }
}
