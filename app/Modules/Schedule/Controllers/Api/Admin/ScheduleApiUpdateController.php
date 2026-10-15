<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Schedule\Contracts\DeliveryScheduleServiceInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Requests\UpdateDeliveryScheduleRequest;
use App\Modules\Schedule\Resources\DeliveryScheduleResource;
use Illuminate\Http\JsonResponse;

final class ScheduleApiUpdateController
{
    public function __construct(private readonly DeliveryScheduleServiceInterface $schedules) {}

    public function __invoke(UpdateDeliveryScheduleRequest $request, DeliverySchedule $schedule): JsonResponse
    {
        $updated = $this->schedules->update($schedule, $request->validated());

        return ApiResponse::success(
            new DeliveryScheduleResource($updated->load(['customer', 'slot', 'preferredDriver'])),
            __('Standing order updated. Orders already generated are unchanged.'),
        );
    }
}
