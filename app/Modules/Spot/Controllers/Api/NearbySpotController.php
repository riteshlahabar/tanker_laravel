<?php

declare(strict_types=1);

namespace App\Modules\Spot\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Spot\Contracts\SpotRepositoryInterface;
use App\Modules\Spot\Requests\NearbySpotRequest;
use App\Modules\Spot\Resources\SpotResource;
use Illuminate\Http\JsonResponse;

/**
 * Spots nearest the driver's current position — the "refill on the route or at
 * a nearby spot" case.
 */
final class NearbySpotController
{
    public function __construct(private readonly SpotRepositoryInterface $spots) {}

    public function __invoke(NearbySpotRequest $request): JsonResponse
    {
        $spots = $this->spots->nearest(
            latitude: (float) $request->input('latitude'),
            longitude: (float) $request->input('longitude'),
            limit: (int) ($request->input('limit') ?: 10),
        );

        return ApiResponse::success(SpotResource::collection($spots)->resolve());
    }
}
