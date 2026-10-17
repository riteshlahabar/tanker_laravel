<?php

declare(strict_types=1);

namespace App\Modules\Driver\Exports;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class DriverExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?string $search = null,
        private readonly ?bool $status = null,
    ) {}

    public function query(): Builder
    {
        return User::query()
            ->role(RoleEnum::Driver->value)
            ->with('currentAssignment.tanker:id,vehicle_name,vehicle_number')
            ->when($this->status !== null, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->search !== null && $this->search !== '', fn (Builder $q) => $q
                ->where(fn (Builder $inner) => $inner
                    ->whereAny(['first_name', 'last_name', 'mobile'], 'like', "%{$this->search}%")))
            ->orderBy('first_name');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Driver', 'Mobile', 'Assigned Tanker', 'Monthly Salary', 'Address', 'Status'];
    }

    /**
     * @param  User  $row
     * @return array<int, string|float|null>
     */
    public function map($row): array
    {
        return [
            $row->full_name,
            $row->mobile,
            $row->currentAssignment?->tanker?->label(),
            (float) $row->monthly_salary,
            $row->address,
            $row->status ? 'Active' : 'Inactive',
        ];
    }
}
