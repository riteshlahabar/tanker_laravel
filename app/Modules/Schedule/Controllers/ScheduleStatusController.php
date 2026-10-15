<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Schedule\Contracts\DeliveryScheduleServiceInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ScheduleStatusController
{
    public function __construct(private readonly DeliveryScheduleServiceInterface $schedules) {}

    public function __invoke(Request $request, DeliverySchedule $schedule): JsonResponse
    {
        Gate::authorize('update', $schedule);

        $request->validate(['status' => ['required', 'boolean']]);

        return ApiResponse::success(
            ['status' => $this->schedules->setStatus($schedule, $request->boolean('status'))->status],
            __('Status updated successfully.'),
        );
    }
}
