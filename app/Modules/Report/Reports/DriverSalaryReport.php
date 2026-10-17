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
 * What each driver delivered over the range, and what they are paid.
 *
 * The salary is deliberately NOT prorated across the range. A salary is a
 * monthly figure and the range here is arbitrary, so dividing one by the other
 * would produce something that reads like a cost per delivery and is wrong for
 * any range that is not exactly a month. The total is labelled accordingly.
 *
 * This is also the report the owner's 11:59pm WhatsApp summary is built from,
 * so the daily command and the admin screen read the same numbers from here.
 */
final class DriverSalaryReport implements ReportInterface
{
    public function key(): string
    {
        return 'driver-salary';
    }

    public function title(): string
    {
        return 'Driver Deliveries & Salary';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        // Expenses is APPENDED, never inserted. DailyReportMessage reads this
        // report's rows by position (0, 3, 4), so a column added in the middle
        // would silently change what the owner's nightly WhatsApp says.
        return [
            'Driver', 'Mobile', 'Tanker', 'Delivered', 'Tankers',
            'Order Value', 'Refill Cost', 'Monthly Salary', 'Expenses',
        ];
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
                (int) $driver->loads,
                (float) $driver->order_value,
                (float) $driver->refill_cost,
                (float) $driver->monthly_salary,
                (float) $driver->expense_total,
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
            'Deliveries' => number_format((float) $rows->sum('delivered_count')),
            'Tankers delivered' => number_format((float) $rows->sum('loads')),
            'Order value' => '₹ '.number_format((float) $rows->sum('order_value'), 2),
            // Named at length on purpose: a bare "Total salary" beside
            // range-bound figures reads as the cost of the range, which it is not.
            'Salary per month (not for the range)' => '₹ '.number_format((float) $rows->sum('monthly_salary'), 2),
            // This one IS range-bound, hence the different wording. The two
            // must never be added together into a single "cost".
            'Expenses in the range' => '₹ '.number_format((float) $rows->sum('expense_total'), 2),
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
            // `loads`, not `tankers`: $driver->tankers would read as nonsense.
            ->withSum(['deliveredOrders as loads' => $inRange], 'tanker_count')
            ->withSum(['deliveredOrders as order_value' => $inRange], 'order_amount')
            ->withSum(['deliveredOrders as refill_cost' => $inRange], 'refill_charge')
            // What the driver spent out of pocket over the same range, so the
            // owner's real cost per driver — salary plus spending — is on one
            // row. expense_date is a DATE column, so plain dates, not the
            // datetime helpers the order columns need.
            ->withSum(['expenses as expense_total' => fn (Builder $q) => $q
                ->whereBetween('expense_date', [$filters->from, $filters->to]),
            ], 'amount')
            ->when($filters->driverId !== null, fn (Builder $q) => $q->whereKey($filters->driverId))
            ->when($filters->search !== null, fn (Builder $q) => $q
                ->whereAny(['first_name', 'last_name', 'mobile'], 'like', "%{$filters->search}%"))
            // Was ordered by earning, which no longer exists. Busiest first is
            // the useful order now; salary is a property of the driver, not of
            // the range, so ordering by it would say nothing about the period.
            ->orderByDesc('delivered_count')
            ->orderBy('first_name');
    }
}
