<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Schedule\Contracts\DeliveryScheduleServiceInterface;
use App\Modules\Schedule\Requests\StoreDeliveryScheduleRequest;
use App\Modules\Schedule\Resources\DeliveryScheduleResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ScheduleApiStoreController
{
    public function __construct(private readonly DeliveryScheduleServiceInterface $schedules) {}

    public function __invoke(StoreDeliveryScheduleRequest $request): JsonResponse
    {
        $schedule = $this->schedules->create($request->validated(), $request->user());

        return ApiResponse::success(
            new DeliveryScheduleResource($schedule->load(['customer', 'slot', 'preferredDriver'])),
            __('Standing order created.'),
            Response::HTTP_CREATED,
        );
    }
}
