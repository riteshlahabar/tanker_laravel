<?php

declare(strict_types=1);

namespace App\Modules\Report\Reports;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Report\Contracts\ReportInterface;
use App\Modules\Report\Support\ReportFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * How many tankers each driver delivered and what they earned. This is the
 * report the owner receives on WhatsApp at 11:59pm, so the daily command and
 * the admin screen read the same numbers from here.
 */
final class DriverEarningReport implements ReportInterface
{
    public function key(): string
    {
        return 'driver-earning';
    }

    public function title(): string
    {
        return 'Driver Delivery & Earning';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Driver', 'Mobile', 'Tanker', 'Delivered', 'Order Value', 'Refill Cost', 'Earning'];
    }

    /**
     * @return array<int, string>
     */
    public function filterFields(): array
    {
        return ['driver', 'search'];
    }

    /**
     * @return Collection<int, array<int, string|int|float|null>>
     */
    public function rows(ReportFilters $filters): Collection
    {
        return $this->query($filters)
            ->get()
            ->map(fn (User $driver): array => [
                $driver->full_name,
                $driver->mobile,
                $driver->currentAssignment?->tanker?->vehicle_number ?? '—',
                (int) $driver->delivered_count,
                (float) $driver->order_value,
                (float) $driver->refill_cost,
                (float) $driver->earning,
            ])
            ->values();
    }

    /**
     * @return array<string, string>
     */
    public function totals(ReportFilters $filters): array
    {
        $rows = $this->query($filters)->get();

        return [
            'Drivers' => (string) $rows->count(),
            'Tankers delivered' => number_format((float) $rows->sum('delivered_count')),
            'Order value' => '₹ '.number_format((float) $rows->sum('order_value'), 2),
            'Total earning' => '₹ '.number_format((float) $rows->sum('earning'), 2),
        ];
    }

    /**
     * Drivers with their delivered totals for the range. Aggregated by the
     * database with withSum/withCount, so one query covers every driver
     * instead of one per row.
     */
    private function query(ReportFilters $filters): Builder
    {
        $inRange = fn (Builder $query) => $query
            ->where('status', OrderStatusEnum::Delivered)
            ->whereBetween('delivered_at', [$filters->fromDateTime(), $filters->toDateTime()]);

        return User::query()
            ->role(RoleEnum::Driver->value)
            ->with('currentAssignment.tanker:id,vehicle_number')
            ->withCount(['deliveredOrders as delivered_count' => $inRange])
            ->withSum(['deliveredOrders as order_value' => $inRange], 'order_amount')
            ->withSum(['deliveredOrders as refill_cost' => $inRange], 'refill_charge')
            ->withSum(['deliveredOrders as earning' => $inRange], 'driver_earning')
            ->when($filters->driverId !== null, fn (Builder $q) => $q->whereKey($filters->driverId))
            ->when($filters->search !== null, fn (Builder $q) => $q
                ->whereAny(['first_name', 'last_name', 'mobile'], 'like', "%{$filters->search}%"))
            ->orderByDesc('earning')
            ->orderBy('first_name');
    }
}
