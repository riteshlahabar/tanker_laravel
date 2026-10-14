<?php

declare(strict_types=1);

namespace App\Modules\Report\Controllers;

use App\Modules\Report\Controllers\Concerns\RendersReport;
use App\Modules\Report\Reports\SpotRefillReport;
use App\Modules\Report\Services\ReportPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SpotRefillReportController
{
    use RendersReport;

    public function __invoke(Request $request, SpotRefillReport $report, ReportPresenter $presenter): View
    {
        return $this->renderReport($report, $request, $presenter);
    }
}
