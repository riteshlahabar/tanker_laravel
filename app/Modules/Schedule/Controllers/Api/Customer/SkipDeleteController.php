<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers\Api\Customer;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Schedule\Contracts\DeliveryScheduleRepositoryInterface;
use App\Modules\Schedule\Contracts\ScheduleSkipServiceInterface;
use App\Modules\Schedule\Exceptions\ScheduleSkipException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * "Actually, I do need it after all." Allowed while the six-hour window is
 * still open; the order is regenerated for that one date.
 */
final class SkipDeleteController
{
    public function __construct(
        private readonly DeliveryScheduleRepositoryInterface $schedules,
        private readonly ScheduleSkipServiceInterface $skips,
    ) {}

    public function __invoke(Request $request, string $date): JsonResponse
    {
        $schedule = $this->schedules->activeForCustomer($request->user()->id);

        if ($schedule === null) {
            return ApiResponse::notFound(__('You are not on a delivery routine.'));
        }

        Gate::authorize('skip', $schedule);

        try {
            $this->skips->restore($schedule, Carbon::parse($date)->startOfDay(), $request->user());
        } catch (ScheduleSkipException $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(null, __('Delivery restored.'));
    }
}
