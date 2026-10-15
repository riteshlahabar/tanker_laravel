<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Schedule\Contracts\ScheduleSkipServiceInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Resources\DeliveryScheduleResource;
use App\Modules\Schedule\Resources\UpcomingDateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ScheduleApiShowController
{
    public function __construct(private readonly ScheduleSkipServiceInterface $skips) {}

    public function __invoke(Request $request, DeliverySchedule $schedule): JsonResponse
    {
        Gate::authorize('view', $schedule);

        return ApiResponse::success([
            'schedule' => new DeliveryScheduleResource(
                $schedule->load(['customer', 'slot', 'preferredDriver'])
            ),
            'upcoming' => UpcomingDateResource::collection(
                $this->skips->upcoming($schedule, $request->user())
            )->resolve(),
        ]);
    }
}
