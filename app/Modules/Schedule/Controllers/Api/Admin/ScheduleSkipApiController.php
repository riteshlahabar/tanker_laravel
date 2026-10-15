<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Schedule\Contracts\ScheduleSkipRepositoryInterface;
use App\Modules\Schedule\Contracts\ScheduleSkipServiceInterface;
use App\Modules\Schedule\Exceptions\ScheduleSkipException;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Requests\SkipDateRequest;
use App\Modules\Schedule\Resources\ScheduleSkipResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin may call off or restore any date on any routine. They override the
 * six hours' notice, but the status backstop still binds them — once the
 * tanker is being filled, the water is bought.
 */
final class ScheduleSkipApiController
{
    public function __construct(
        private readonly ScheduleSkipServiceInterface $skips,
        private readonly ScheduleSkipRepositoryInterface $repository,
    ) {}

    public function index(DeliverySchedule $schedule): JsonResponse
    {
        Gate::authorize('view', $schedule);

        return ApiResponse::success(
            ScheduleSkipResource::collection($this->repository->forSchedule($schedule))->resolve(),
        );
    }

    public function store(SkipDateRequest $request, DeliverySchedule $schedule): JsonResponse
    {
        Gate::authorize('skip', $schedule);

        try {
            $skip = $this->skips->skip($schedule, $request->date(), $request->user(), $request->reason());
        } catch (ScheduleSkipException $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(
            new ScheduleSkipResource($skip->load(['requester', 'order'])),
            __('Delivery called off.'),
            Response::HTTP_CREATED,
        );
    }

    public function destroy(Request $request, DeliverySchedule $schedule, string $date): JsonResponse
    {
        Gate::authorize('skip', $schedule);

        try {
            $this->skips->restore($schedule, Carbon::parse($date)->startOfDay(), $request->user());
        } catch (ScheduleSkipException $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(null, __('Delivery restored.'));
    }
}
