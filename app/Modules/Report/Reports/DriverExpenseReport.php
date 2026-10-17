<?php

declare(strict_types=1);

namespace App\Modules\Report\Reports;

use App\Models\User;
use App\Modules\Core\Enums\ExpenseCategoryEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Report\Contracts\ReportInterface;
use App\Modules\Report\Support\ReportFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * What each driver spent over the range, split by category.
 *
 * Every expense counts: the owner chose to record spending rather than approve
 * it (2026-10-17), so there is no approved/pending split to filter on.
 */
final class DriverExpenseReport implements ReportInterface
{
    public function key(): string
    {
        return 'driver-expense';
    }

    public function title(): string
    {
        return 'Driver Expenses';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Driver', 'Mobile', 'Entries', 'Fuel', 'Toll', 'Maintenance', 'Food', 'Other', 'Total'];
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
                (int) $driver->expense_entries,
                (float) $driver->fuel_total,
                (float) $driver->toll_total,
                (float) $driver->maintenance_total,
                (float) $driver->food_total,
                (float) $driver->other_total,
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
            'Entries' => number_format((float) $rows->sum('expense_entries')),
            'Fuel' => '₹ '.number_format((float) $rows->sum('fuel_total'), 2),
            'Everything else' => '₹ '.number_format(
                (float) $rows->sum('expense_total') - (float) $rows->sum('fuel_total'),
                2,
            ),
            'Total spent' => '₹ '.number_format((float) $rows->sum('expense_total'), 2),
        ];
    }

    /**
     * One query for every driver and every category, rather than one per cell.
     */
    private function query(ReportFilters $filters): Builder
    {
        // Plain dates, not the datetime helpers: expense_date is a DATE column,
        // so a 23:59:59 upper bound would compare a date against a datetime.
        $inRange = fn (Builder $query) => $query
            ->whereBetween('expense_date', [$filters->from, $filters->to]);

        $query = User::query()
            ->role(RoleEnum::Driver->value)
            ->withCount(['expenses as expense_entries' => $inRange])
            ->withSum(['expenses as expense_total' => $inRange], 'amount');

        foreach (ExpenseCategoryEnum::cases() as $category) {
            $query->withSum([
                "expenses as {$category->value}_total" => fn (Builder $q) => $inRange($q)
                    ->where('category', $category->value),
            ], 'amount');
        }

        return $query
            ->when($filters->driverId !== null, fn (Builder $q) => $q->whereKey($filters->driverId))
            ->when($filters->search !== null, fn (Builder $q) => $q
                ->whereAny(['first_name', 'last_name', 'mobile'], 'like', "%{$filters->search}%"))
            ->orderByDesc('expense_total')
            ->orderBy('first_name');
    }
}
