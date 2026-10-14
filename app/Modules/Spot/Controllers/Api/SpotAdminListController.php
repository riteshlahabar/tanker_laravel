<?php

declare(strict_types=1);

namespace App\Modules\Spot\Controllers\Api;

use App\Modules\Core\Controllers\Api\Concerns\RespondsWithList;
use App\Modules\Spot\Models\Spot;
use App\Modules\Spot\Resources\SpotResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class SpotAdminListController
{
    use RespondsWithList;

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Spot::class);

        return $this->listResponse(
            Spot::query()->orderBy('name'),
            $request,
            SpotResource::class,
            ['name', 'address', 'contact_person'],
        );
    }
}
