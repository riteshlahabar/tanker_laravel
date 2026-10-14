<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Controllers\Api;

use App\Modules\Core\Controllers\Api\Concerns\RespondsWithList;
use App\Modules\Tanker\Models\Tanker;
use App\Modules\Tanker\Resources\TankerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class TankerListController
{
    use RespondsWithList;

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Tanker::class);

        return $this->listResponse(
            Tanker::query()->with('currentAssignment.driver:id,first_name,last_name')->orderBy('vehicle_name'),
            $request,
            TankerResource::class,
            ['vehicle_name', 'vehicle_number', 'rc_number'],
        );
    }
}
