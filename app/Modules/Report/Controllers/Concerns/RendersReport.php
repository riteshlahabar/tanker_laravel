<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers\Concerns;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Report\Contracts\ReportInterface;
use App\Modules\Report\Services\ReportPresenter;
use App\Modules\Report\Support\ReportFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Shared by the five report page controllers: authorise, read the filters,
 * hand the report to the presenter.
 */
trait RendersReport
{
    private function renderReport(ReportInterface $report, Request $request, ReportPresenter $presenter): View
    {
        Gate::authorize(PermissionEnum::ReportView->value);

        return view('report::show', $presenter->present($report, ReportFilters::fromRequest($request)));
    }
}
