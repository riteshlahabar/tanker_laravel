<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers\Api;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Driver\Contracts\DriverServiceInterface;
use App\Modules\Driver\Requests\UpdateDriverRequest;
use App\Modules\Driver\Resources\DriverResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class DriverUpdateController
{
    public function __construct(private readonly DriverServiceInterface $drivers) {}

    public function __invoke(UpdateDriverRequest $request, User $driver): JsonResponse
    {
        Gate::authorize(PermissionEnum::DriverManage->value);

        $updated = $this->drivers->update($driver, $request->validated());

        return ApiResponse::success(
            new DriverResource($updated->load('currentAssignment.tanker')),
            __('Driver updated successfully.'),
        );
    }
}
