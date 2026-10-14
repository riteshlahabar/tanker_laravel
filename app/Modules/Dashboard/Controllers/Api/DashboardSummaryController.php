<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Dashboard\Contracts\DashboardServiceInterface;
use Illuminate\Http\JsonResponse;

/**
 * The admin home screen in the app — the same cached counters the panel
 * dashboard shows.
 */
final class DashboardSummaryController
{
    public function __construct(private readonly DashboardServiceInterface $dashboard) {}

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success($this->dashboard->summary());
    }
}
