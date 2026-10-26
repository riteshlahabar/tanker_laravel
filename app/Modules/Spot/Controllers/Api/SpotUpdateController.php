<?php

declare(strict_types=1);

namespace App\Modules\Spot\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Spot\Contracts\SpotServiceInterface;
use App\Modules\Spot\Models\Spot;
use App\Modules\Spot\Requests\UpdateSpotRequest;
use App\Modules\Spot\Resources\SpotResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class SpotUpdateController
{
    public function __construct(private readonly SpotServiceInterface $spots) {}

    public function __invoke(UpdateSpotRequest $request, Spot $spot): JsonResponse
    {
        Gate::authorize('update', $spot);

        return ApiResponse::success(
            new SpotResource($this->spots->update($spot, $request->validated())),
            __('Filling point updated successfully.'),
        );
    }
}
