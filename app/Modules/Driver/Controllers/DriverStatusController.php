<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Driver\Contracts\DriverServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class DriverStatusController
{
    public function __construct(private readonly DriverServiceInterface $drivers) {}

    public function __invoke(Request $request, User $driver): JsonResponse
    {
        Gate::authorize(PermissionEnum::DriverManage->value);

        $request->validate(['status' => ['required', 'boolean']]);

        return ApiResponse::success(
            ['status' => $this->drivers->setStatus($driver, $request->boolean('status'))->status],
            __('Status updated successfully.'),
        );
    }
}
