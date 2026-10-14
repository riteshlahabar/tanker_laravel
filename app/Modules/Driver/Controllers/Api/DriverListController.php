<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers\Api;

use App\Modules\Core\Controllers\Api\Concerns\RespondsWithList;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Driver\Contracts\DriverRepositoryInterface;
use App\Modules\Driver\Resources\DriverResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class DriverListController
{
    use RespondsWithList;

    public function __construct(private readonly DriverRepositoryInterface $drivers) {}

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::DriverView->value);

        return $this->listResponse(
            $this->drivers->scopedQuery()
                ->with('currentAssignment.tanker:id,vehicle_name,vehicle_number')
                ->orderBy('first_name'),
            $request,
            DriverResource::class,
            ['first_name', 'last_name', 'mobile'],
        );
    }
}
