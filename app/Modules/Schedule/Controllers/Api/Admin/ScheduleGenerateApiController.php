<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers\Api\Admin;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Schedule\Contracts\ScheduledOrderGeneratorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * "Generate now" from the app — the same run the scheduler does at 00:05.
 */
final class ScheduleGenerateApiController
{
    public function __construct(private readonly ScheduledOrderGeneratorInterface $generator) {}

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ScheduleManage->value);

        $request->validate([
            'date' => ['nullable', 'date'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $date = $request->input('date');
        $target = is_string($date) && $date !== ''
            ? Carbon::parse($date)->startOfDay()
            : Carbon::tomorrow();

        $report = $this->generator->run($target, $request->boolean('dry_run'));

        return ApiResponse::success([
            'target_date' => $target->toDateString(),
            'created' => $report->createdCount(),
            'skipped' => $report->skippedCount(),
            'failed' => $report->failedCount(),
            'lines' => $report->lines(),
        ], $report->summary());
    }
}
