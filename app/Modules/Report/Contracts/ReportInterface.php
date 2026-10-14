<?php

declare(strict_types=1);

namespace App\Modules\Report\Contracts;

use App\Modules\Report\Support\ReportFilters;
use Illuminate\Support\Collection;

/**
 * Every report answers the same five questions, so one Blade template and one
 * Excel exporter serve all of them.
 */
interface ReportInterface
{
    /**
     * Stable key used in routes and the export URL.
     */
    public function key(): string;

    public function title(): string;

    /**
     * Column headings, in order.
     *
     * @return array<int, string>
     */
    public function headings(): array;

    /**
     * Rows as plain arrays matching headings(), ready for both the table and
     * the spreadsheet.
     *
     * @return Collection<int, array<int, string|int|float|null>>
     */
    public function rows(ReportFilters $filters): Collection;

    /**
     * Figures shown above the table, e.g. ['Total earning' => '₹ 4,500.00'].
     *
     * @return array<string, string>
     */
    public function totals(ReportFilters $filters): array;

    /**
     * Which filter inputs this report shows: any of driver, customer, spot,
     * tanker, search. The date range is always shown.
     *
     * @return array<int, string>
     */
    public function filterFields(): array;
}
