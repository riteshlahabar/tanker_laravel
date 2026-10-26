<?php

declare(strict_types=1);

namespace App\Modules\Spot\Exports;

use App\Modules\Spot\Models\Spot;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class SpotExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?string $search = null,
        private readonly ?bool $status = null,
        private readonly ?bool $available = null,
    ) {}

    public function query(): Builder
    {
        return Spot::query()
            ->when($this->status !== null, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->available !== null, fn (Builder $q) => $q->where('is_available', $this->available))
            ->when($this->search !== null && $this->search !== '', fn (Builder $q) => $q
                ->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('address', 'like', "%{$this->search}%")
                    ->orWhere('contact_person', 'like', "%{$this->search}%")))
            ->orderBy('name');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Filling Point Name', 'Address', 'Refill Charge', 'Water Type',
            'Latitude', 'Longitude', 'Contact Person', 'Contact Mobile',
            'Availability', 'Status',
        ];
    }

    /**
     * @param  Spot  $row
     * @return array<int, string|float|null>
     */
    public function map($row): array
    {
        return [
            $row->name,
            $row->address,
            (float) $row->refill_charge,
            $row->water_type,
            $row->latitude,
            $row->longitude,
            $row->contact_person,
            $row->contact_mobile,
            $row->is_available ? 'Available' : 'Unavailable',
            $row->status ? 'Active' : 'Inactive',
        ];
    }
}
