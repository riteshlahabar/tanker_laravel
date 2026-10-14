<?php

declare(strict_types=1);

namespace App\Modules\Spot\Controllers;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Spot\Contracts\SpotServiceInterface;
use App\Modules\Spot\Models\Spot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class SpotStatusController
{
    public function __construct(private readonly SpotServiceInterface $spots) {}

    public function __invoke(Request $request, Spot $spot): JsonResponse
    {
        Gate::authorize('update', $spot);

        $request->validate(['status' => ['required', 'boolean']]);

        return ApiResponse::success(
            ['status' => $this->spots->setStatus($spot, $request->boolean('status'))->status],
            __('Status updated successfully.'),
        );
    }
}
