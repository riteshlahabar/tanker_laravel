<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers\Api\Customer;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Schedule\Contracts\DeliveryScheduleRepositoryInterface;
use App\Modules\Schedule\Contracts\ScheduleSkipServiceInterface;
use App\Modules\Schedule\Exceptions\ScheduleSkipException;
use App\Modules\Schedule\Requests\SkipDateRequest;
use App\Modules\Schedule\Resources\ScheduleSkipResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * "I don't need the tanker on Thursday."
 */
final class SkipStoreController
{
    public function __construct(
        private readonly DeliveryScheduleRepositoryInterface $schedules,
        private readonly ScheduleSkipServiceInterface $skips,
    ) {}

    public function __invoke(SkipDateRequest $request): JsonResponse
    {
        $schedule = $this->schedules->activeForCustomer($request->user()->id);

        if ($schedule === null) {
            return ApiResponse::notFound(__('You are not on a delivery routine.'));
        }

        Gate::authorize('skip', $schedule);

        try {
            $skip = $this->skips->skip($schedule, $request->date(), $request->user(), $request->reason());
        } catch (ScheduleSkipException $e) {
            // A business rule refused it, and the message says which.
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(
            new ScheduleSkipResource($skip->load(['requester', 'order'])),
            __('Delivery called off. We have told the office and your driver.'),
            Response::HTTP_CREATED,
        );
    }
}
