<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers\Api;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Report\Contracts\ReportRegistryInterface;
use App\Modules\Report\Support\ReportFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Any of the five reports as JSON, in the same shape the screen uses:
 * headings, rows, totals. The report is named in the URL.
 */
final class ReportApiController
{
    public function __construct(private readonly ReportRegistryInterface $registry) {}

    public function __invoke(Request $request, string $report): JsonResponse
    {
        Gate::authorize(PermissionEnum::ReportView->value);

        abort_unless(array_key_exists($report, $this->registry->all()), 404);

        $definition = $this->registry->get($report);
        $filters = ReportFilters::fromRequest($request);

        return ApiResponse::success([
            'key' => $definition->key(),
            'title' => $definition->title(),
            'range' => ['from' => $filters->from, 'to' => $filters->to, 'label' => $filters->label()],
            'headings' => $definition->headings(),
            'rows' => $definition->rows($filters)->all(),
            'totals' => $definition->totals($filters),
        ]);
    }
}
