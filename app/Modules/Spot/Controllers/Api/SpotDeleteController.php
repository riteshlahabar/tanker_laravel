<?php

declare(strict_types=1);

namespace App\Modules\Spot\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Spot\Contracts\SpotServiceInterface;
use App\Modules\Spot\Models\Spot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class SpotDeleteController
{
    public function __construct(private readonly SpotServiceInterface $spots) {}

    public function __invoke(Spot $spot): JsonResponse
    {
        Gate::authorize('delete', $spot);

        $this->spots->delete($spot);

        return ApiResponse::success(null, __('Filling point deleted successfully.'));
    }
}
