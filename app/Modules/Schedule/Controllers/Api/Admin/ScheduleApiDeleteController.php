<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Schedule\Contracts\DeliveryScheduleServiceInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class ScheduleApiDeleteController
{
    public function __construct(private readonly DeliveryScheduleServiceInterface $schedules) {}

    public function __invoke(DeliverySchedule $schedule): JsonResponse
    {
        Gate::authorize('delete', $schedule);

        $this->schedules->delete($schedule);

        return ApiResponse::success(null, __('Standing order removed.'));
    }
}
