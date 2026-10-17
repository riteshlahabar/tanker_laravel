<?php

declare(strict_types=1);

namespace App\Modules\Order\Exports;

use App\Models\User;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exports the same filtered, visibility-scoped set the table shows.
 */
final class OrderExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly User $user,
        private readonly array $filters = [],
    ) {}

    public function query(): Builder
    {
        $filter = fn (string $key): mixed => $this->filters[$key] ?? null;

        return app(OrderRepositoryInterface::class)->visibleTo($this->user)
            ->with(['customer:id,first_name,last_name,mobile', 'driver:id,first_name,last_name',
                'tanker:id,vehicle_number', 'spot:id,name'])
            ->when($filter('status'), fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filter('driver_id'), fn (Builder $q, $v) => $q->where('driver_id', $v))
            ->when($filter('from'), fn (Builder $q, $v) => $q->whereDate('delivery_date', '>=', $v))
            ->when($filter('to'), fn (Builder $q, $v) => $q->whereDate('delivery_date', '<=', $v))
            ->when($filter('search'), fn (Builder $q, $v) => $q
                ->where(fn (Builder $inner) => $inner
                    ->where('order_no', 'like', "%{$v}%")
                    ->orWhereHas('customer', fn (Builder $c) => $c
                        ->whereAny(['first_name', 'last_name', 'mobile'], 'like', "%{$v}%"))))
            ->latest('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Order No', 'Customer', 'Mobile', 'Delivery Address', 'Delivery Date', 'Tankers',
            'Amount', 'Driver', 'Tanker', 'Refill Spot', 'Refill Charge',
            'Status', 'Delivered At',
        ];
    }

    /**
     * @param  Order  $row
     * @return array<int, string|float|int|null>
     */
    public function map($row): array
    {
        return [
            $row->order_no,
            $row->customer?->full_name,
            $row->customer?->mobile,
            $row->delivery_address,
            $row->delivery_date?->format('d-m-Y'),
            $row->tanker_count,
            (float) $row->order_amount,
            $row->driver?->full_name,
            $row->tanker?->vehicle_number,
            $row->spot?->name,
            (float) $row->refill_charge,
            $row->status->label(),
            $row->delivered_at?->format('d-m-Y H:i'),
        ];
    }
}
