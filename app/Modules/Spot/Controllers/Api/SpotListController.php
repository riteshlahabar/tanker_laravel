<?php

declare(strict_types=1);

namespace App\Modules\Spot\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Spot\Contracts\SpotRepositoryInterface;
use App\Modules\Spot\Resources\SpotResource;
use Illuminate\Http\JsonResponse;

/**
 * Usable refill spots. Drivers need them to pick where to fill; customers see
 * them for information.
 */
final class SpotListController
{
    public function __construct(private readonly SpotRepositoryInterface $spots) {}

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(
            SpotResource::collection($this->spots->usable())->resolve(),
        );
    }
}
