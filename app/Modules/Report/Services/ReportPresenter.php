<?php

declare(strict_types=1);

namespace App\Modules\Report\Services;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Report\Contracts\ReportInterface;
use App\Modules\Report\Support\ReportFilters;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;

/**
 * Assembles what the shared report view needs, so each report's page
 * controller stays a handful of lines.
 */
final class ReportPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(ReportInterface $report, ReportFilters $filters): array
    {
        return [
            'report' => $report,
            'filters' => $filters,
            'headings' => $report->headings(),
            'rows' => $report->rows($filters),
            'totals' => $report->totals($filters),
            'options' => $this->optionsFor($report),
            'exportUrl' => route('admin.report.export', [
                'report' => $report->key(),
                ...$filters->toQueryString(),
            ]),
        ];
    }

    /**
     * Only the dropdowns this report actually shows are queried.
     *
     * @return array<string, array<int, string>>
     */
    private function optionsFor(ReportInterface $report): array
    {
        $fields = $report->filterFields();
        $options = [];

        if (in_array('driver', $fields, true)) {
            $options['driver'] = $this->userOptions(RoleEnum::Driver);
        }

        if (in_array('customer', $fields, true)) {
            $options['customer'] = $this->userOptions(RoleEnum::Customer);
        }

        if (in_array('spot', $fields, true)) {
            $options['spot'] = Spot::query()->orderBy('name')->pluck('name', 'id')->all();
        }

        if (in_array('tanker', $fields, true)) {
            $options['tanker'] = Tanker::query()
                ->orderBy('vehicle_name')
                ->get(['id', 'vehicle_name', 'vehicle_number'])
                ->mapWithKeys(fn (Tanker $t): array => [$t->id => $t->label()])
                ->all();
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    private function userOptions(RoleEnum $role): array
    {
        return User::query()
            ->role($role->value)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $u): array => [$u->id => $u->full_name])
            ->all();
    }
}
