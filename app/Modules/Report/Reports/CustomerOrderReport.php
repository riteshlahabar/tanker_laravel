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
 * What each customer ordered and paid over the range.
 */
final class CustomerOrderReport implements ReportInterface
{
    public function key(): string
    {
        return 'customer-order';
    }

    public function title(): string
    {
        return 'Customer Order Report';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Customer', 'Mobile', 'Address', 'Orders', 'Delivered', 'Litres', 'Amount Paid'];
    }

    /**
     * @return array<int, string>
     */
    public function filterFields(): array
    {
        return ['customer', 'search'];
    }

    /**
     * @return Collection<int, array<int, string|int|float|null>>
     */
    public function rows(ReportFilters $filters): Collection
    {
        return $this->query($filters)
            ->get()
            ->map(fn (User $customer): array => [
                $customer->full_name,
                $customer->mobile,
                $customer->address,
                (int) $customer->orders_count,
                (int) $customer->delivered_count,
                (int) $customer->litres,
                (float) $customer->amount_paid,
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
            'Customers ordered' => (string) $rows->where('orders_count', '>', 0)->count(),
            'Orders' => number_format((float) $rows->sum('orders_count')),
            'Delivered' => number_format((float) $rows->sum('delivered_count')),
            'Amount paid' => '₹ '.number_format((float) $rows->sum('amount_paid'), 2),
        ];
    }

    private function query(ReportFilters $filters): Builder
    {
        $placed = fn (Builder $query) => $query
            ->whereBetween('created_at', [$filters->fromDateTime(), $filters->toDateTime()]);

        $delivered = fn (Builder $query) => $query
            ->where('status', OrderStatusEnum::Delivered)
            ->whereBetween('delivered_at', [$filters->fromDateTime(), $filters->toDateTime()]);

        return User::query()
            ->role(RoleEnum::Customer->value)
            ->withCount(['orders as orders_count' => $placed])
            ->withCount(['orders as delivered_count' => $delivered])
            ->withSum(['orders as litres' => $delivered], 'quantity_litres')
            ->withSum(['orders as amount_paid' => $delivered], 'order_amount')
            ->when($filters->customerId !== null, fn (Builder $q) => $q->whereKey($filters->customerId))
            ->when($filters->search !== null, fn (Builder $q) => $q
                ->whereAny(['first_name', 'last_name', 'mobile'], 'like', "%{$filters->search}%"))
            ->orderByDesc('amount_paid')
            ->orderBy('first_name');
    }
}
