<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers\Api;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Driver\Contracts\DriverServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class DriverDeleteController
{
    public function __construct(private readonly DriverServiceInterface $drivers) {}

    public function __invoke(User $driver): JsonResponse
    {
        Gate::authorize(PermissionEnum::DriverManage->value);

        $this->drivers->delete($driver);

        return ApiResponse::success(null, __('Driver deleted successfully.'));
    }
}
