<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Exports;

use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Runs off the same filters as the list, so the spreadsheet and the screen
 * always agree.
 */
final class DeliveryScheduleExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ?bool $status = null,
        private readonly ?bool $paused = null,
        private readonly ?int $customerId = null,
    ) {}

    public function query(): Builder
    {
        return DeliverySchedule::query()
            ->with(['customer:id,first_name,last_name,mobile', 'slot', 'preferredDriver:id,first_name,last_name'])
            ->when($this->status !== null, fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->paused !== null, fn (Builder $q) => $q->where('is_paused', $this->paused))
            ->when($this->customerId !== null, fn (Builder $q) => $q->where('customer_id', $this->customerId))
            ->orderBy('next_run_date')
            ->orderByDesc('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Customer', 'Mobile', 'Pattern', 'Window', 'Tankers', 'Fixed Amount',
            'Usual Driver', 'Starts', 'Ends', 'Next Delivery', 'Paused', 'Status',
        ];
    }

    /**
     * @param  DeliverySchedule  $row
     * @return array<int, string|int|float|null>
     */
    public function map($row): array
    {
        return [
            $row->customer?->full_name,
            $row->customer?->mobile,
            $row->patternLabel(),
            $row->slot?->label() ?? $row->slotLabel(),
            $row->tanker_count,
            // Raw number, so the spreadsheet keeps a real numeric value.
            $row->order_amount === null ? null : (float) $row->order_amount,
            $row->preferredDriver?->full_name ?? 'Assigned by admin',
            $row->start_date?->toDateString(),
            $row->end_date?->toDateString(),
            $row->next_run_date?->toDateString(),
            $row->is_paused ? 'Paused' : 'Running',
            $row->status ? 'Active' : 'Inactive',
        ];
    }
}
