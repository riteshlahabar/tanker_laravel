<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Report\Contracts\ReportRegistryInterface;
use App\Modules\Report\Exports\ReportExport;
use App\Modules\Report\Support\ReportFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * One export route for all five reports — the report is named in the URL and
 * the filters come straight from the screen the user was looking at.
 */
final class ReportExportController
{
    public function __construct(private readonly ReportRegistryInterface $registry) {}

    public function __invoke(Request $request, string $report): BinaryFileResponse
    {
        Gate::authorize(PermissionEnum::ReportExport->value);

        abort_unless(array_key_exists($report, $this->registry->all()), 404);

        $export = new ReportExport(
            $this->registry->get($report),
            ReportFilters::fromRequest($request),
        );

        return Excel::download($export, $export->filename());
    }
}
