<?php

declare(strict_types=1);

namespace App\Modules\Expense\Exports;

use App\Modules\Expense\Models\DriverExpense;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class ExpenseExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?int $driverId = null,
        private readonly ?string $category = null,
        private readonly ?string $from = null,
        private readonly ?string $to = null,
    ) {}

    public function query(): Builder
    {
        return DriverExpense::query()
            ->with(['driver:id,first_name,last_name', 'tanker:id,vehicle_name,vehicle_number'])
            ->between($this->from, $this->to)
            ->when($this->driverId !== null, fn (Builder $q) => $q->where('driver_id', $this->driverId))
            ->when($this->category !== null && $this->category !== '', fn (Builder $q) => $q
                ->where('category', $this->category))
            ->orderByDesc('expense_date');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Date', 'Driver', 'Category', 'Amount', 'Tanker', 'Added By', 'Notes'];
    }

    /**
     * @param  DriverExpense  $row
     * @return array<int, string|float|null>
     */
    public function map($row): array
    {
        return [
            $row->expense_date?->format('Y-m-d'),
            $row->driver?->full_name,
            $row->category?->label(),
            // A real number, so the spreadsheet can total the column.
            (float) $row->amount,
            $row->tanker?->label(),
            $row->enteredByAdmin() ? 'Office' : 'Driver',
            $row->notes,
        ];
    }
}
