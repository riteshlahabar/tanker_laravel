<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers\Api;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Report\Contracts\ReportInterface;
use App\Modules\Report\Contracts\ReportRegistryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Lets the app build its report menu from the server, so adding a report does
 * not require an app release.
 */
final class ReportIndexController
{
    public function __construct(private readonly ReportRegistryInterface $registry) {}

    public function __invoke(): JsonResponse
    {
        Gate::authorize(PermissionEnum::ReportView->value);

        return ApiResponse::success(
            collect($this->registry->all())
                ->map(fn (ReportInterface $report): array => [
                    'key' => $report->key(),
                    'title' => $report->title(),
                    'filters' => $report->filterFields(),
                ])
                ->values()
                ->all(),
        );
    }
}
