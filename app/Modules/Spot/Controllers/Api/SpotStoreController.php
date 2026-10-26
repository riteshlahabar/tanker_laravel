<?php

declare(strict_types=1);

namespace App\Modules\Spot\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Spot\Contracts\SpotServiceInterface;
use App\Modules\Spot\Requests\StoreSpotRequest;
use App\Modules\Spot\Resources\SpotResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class SpotStoreController
{
    public function __construct(private readonly SpotServiceInterface $spots) {}

    public function __invoke(StoreSpotRequest $request): JsonResponse
    {
        return ApiResponse::success(
            new SpotResource($this->spots->create($request->validated())),
            __('Filling point added successfully.'),
            Response::HTTP_CREATED,
        );
    }
}
