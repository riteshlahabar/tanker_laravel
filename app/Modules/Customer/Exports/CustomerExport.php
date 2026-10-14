<?php

declare(strict_types=1);

namespace App\Modules\Customer\Exports;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class CustomerExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?string $search = null,
        private readonly ?bool $status = null,
    ) {}

    public function query(): Builder
    {
        return User::query()
            ->role(RoleEnum::Customer->value)
            ->when($this->status !== null, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->search !== null && $this->search !== '', fn (Builder $q) => $q
                ->where(fn (Builder $inner) => $inner
                    ->whereAny(['first_name', 'last_name', 'mobile', 'address'], 'like', "%{$this->search}%")))
            ->orderBy('first_name');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Name', 'Mobile', 'Address', 'Latitude', 'Longitude', 'Joined', 'Status'];
    }

    /**
     * @param  User  $row
     * @return array<int, string|null>
     */
    public function map($row): array
    {
        return [
            $row->full_name,
            $row->mobile,
            $row->address,
            $row->latitude,
            $row->longitude,
            $row->created_at->format('d-m-Y'),
            $row->status ? 'Active' : 'Inactive',
        ];
    }
}
