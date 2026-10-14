<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Driver\Contracts\DriverServiceInterface;
use App\Modules\Driver\Requests\StoreDriverRequest;
use App\Modules\Driver\Resources\DriverResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class DriverStoreController
{
    public function __construct(private readonly DriverServiceInterface $drivers) {}

    public function __invoke(StoreDriverRequest $request): JsonResponse
    {
        $driver = $this->drivers->create($request->validated());

        return ApiResponse::success(
            new DriverResource($driver->load('currentAssignment.tanker')),
            __('Driver added successfully.'),
            Response::HTTP_CREATED,
        );
    }
}
