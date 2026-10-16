<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Exports;

use App\Modules\Tanker\Models\Tanker;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exports the filtered set from the database, not the rows the browser happens
 * to be showing.
 */
final class TankerExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?string $search = null,
        private readonly ?bool $status = null,
    ) {}

    public function query(): Builder
    {
        return Tanker::query()
            ->with('currentAssignment.driver:id,first_name,last_name')
            ->when($this->status !== null, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->search !== null && $this->search !== '', fn (Builder $q) => $q
                ->where(fn (Builder $inner) => $inner
                    ->where('vehicle_name', 'like', "%{$this->search}%")
                    ->orWhere('vehicle_number', 'like', "%{$this->search}%")
                    ->orWhere('rc_number', 'like', "%{$this->search}%")))
            ->orderBy('vehicle_name');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Vehicle Name',
            'Vehicle Number',
            'Capacity (L)',
            'Rate per Tanker',
            'Model Year',
            'RC Number',
            'Insurance Expiry',
            'Current Driver',
            'Status',
        ];
    }

    /**
     * @param  Tanker  $row
     * @return array<int, string|int|null>
     */
    public function map($row): array
    {
        return [
            $row->vehicle_name,
            $row->vehicle_number,
            $row->capacity_litres,
            $row->tanker_rate,
            $row->model_year,
            $row->rc_number,
            $row->insurance_expiry?->format('d-m-Y'),
            $row->currentAssignment?->driver?->full_name,
            $row->status ? 'Active' : 'Inactive',
        ];
    }
}
