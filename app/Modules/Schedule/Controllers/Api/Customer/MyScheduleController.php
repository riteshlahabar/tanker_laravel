<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers\Api\Customer;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Schedule\Contracts\DeliveryScheduleRepositoryInterface;
use App\Modules\Schedule\Contracts\ScheduleSkipServiceInterface;
use App\Modules\Schedule\Resources\DeliveryScheduleResource;
use App\Modules\Schedule\Resources\UpcomingDateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customer's own routine, read-only: the pattern plus the next fortnight
 * of dates, each carrying `can_skip` so the app can disable the button rather
 * than letting the tap fail.
 */
final class MyScheduleController
{
    public function __construct(
        private readonly DeliveryScheduleRepositoryInterface $schedules,
        private readonly ScheduleSkipServiceInterface $skips,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $schedule = $this->schedules->activeForCustomer($request->user()->id);

        if ($schedule === null) {
            // Not an error: most customers order ad-hoc and have no routine.
            return ApiResponse::success(
                ['schedule' => null, 'upcoming' => []],
                __('You are not on a delivery routine.'),
            );
        }

        $upcoming = $this->skips->upcoming($schedule, $request->user());

        return ApiResponse::success([
            'schedule' => new DeliveryScheduleResource($schedule),
            'upcoming' => UpcomingDateResource::collection($upcoming)->resolve(),
        ]);
    }
}
