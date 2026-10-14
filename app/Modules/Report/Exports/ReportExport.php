<?php

declare(strict_types=1);

namespace App\Modules\Report\Exports;

use App\Modules\Report\Contracts\ReportInterface;
use App\Modules\Report\Support\ReportFilters;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * One exporter for every report: it asks the report for the same rows the
 * screen shows, so a download can never disagree with the table.
 */
final class ReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        private readonly ReportInterface $report,
        private readonly ReportFilters $filters,
    ) {}

    /**
     * @return Collection<int, array<int, string|int|float|null>>
     */
    public function collection(): Collection
    {
        return $this->report->rows($this->filters);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function headings(): array
    {
        return [
            [$this->report->title().' — '.$this->filters->label()],
            $this->report->headings(),
        ];
    }

    public function title(): string
    {
        return substr($this->report->title(), 0, 31);
    }

    public function filename(): string
    {
        return str_replace('-', '_', $this->report->key()).'_'.now()->format('Ymd_His').'.xlsx';
    }
}
