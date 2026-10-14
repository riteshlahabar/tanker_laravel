<?php

declare(strict_types=1);

namespace App\Modules\Report\Reports;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Report\Contracts\ReportInterface;
use App\Modules\Report\Support\ReportFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Day-by-day trading summary: what came in, what went out, what it earned.
 */
final class DailyDeliveryReport implements ReportInterface
{
    public function key(): string
    {
        return 'daily-delivery';
    }

    public function title(): string
    {
        return 'Daily Delivery Summary';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Date', 'Orders', 'Delivered', 'Cancelled', 'Revenue', 'Refill Cost', 'Driver Earning'];
    }

    /**
     * @return array<int, string>
     */
    public function filterFields(): array
    {
        return [];
    }

    /**
     * @return Collection<int, array<int, string|int|float|null>>
     */
    public function rows(ReportFilters $filters): Collection
    {
        $placed = $this->placedPerDay($filters);
        $delivered = $this->deliveredPerDay($filters);

        return collect($this->datesIn($filters))
            ->map(function (string $date) use ($placed, $delivered): array {
                $p = $placed[$date] ?? null;
                $d = $delivered[$date] ?? null;

                return [
                    Carbon::parse($date)->format('d M Y'),
                    (int) ($p?->orders ?? 0),
                    (int) ($d?->delivered ?? 0),
                    (int) ($p?->cancelled ?? 0),
                    (float) ($d?->revenue ?? 0),
                    (float) ($d?->refill_cost ?? 0),
                    (float) ($d?->driver_earning ?? 0),
                ];
            })
            ->values();
    }

    /**
     * @return array<string, string>
     */
    public function totals(ReportFilters $filters): array
    {
        $rows = $this->rows($filters);

        return [
            'Orders placed' => number_format((float) $rows->sum(1)),
            'Delivered' => number_format((float) $rows->sum(2)),
            'Cancelled' => number_format((float) $rows->sum(3)),
            'Revenue' => '₹ '.number_format((float) $rows->sum(4), 2),
            'Driver earning' => '₹ '.number_format((float) $rows->sum(6), 2),
        ];
    }

    /**
     * @return array<string, object>
     */
    private function placedPerDay(ReportFilters $filters): array
    {
        return Order::query()
            ->selectRaw('date(created_at) as day, count(*) as orders')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as cancelled', [OrderStatusEnum::Cancelled->value])
            ->whereBetween('created_at', [$filters->fromDateTime(), $filters->toDateTime()])
            ->groupByRaw('date(created_at)')
            ->get()
            ->keyBy('day')
            ->all();
    }

    /**
     * @return array<string, object>
     */
    private function deliveredPerDay(ReportFilters $filters): array
    {
        return Order::query()
            ->selectRaw('date(delivered_at) as day, count(*) as delivered')
            ->selectRaw('sum(order_amount) as revenue, sum(refill_charge) as refill_cost, sum(driver_earning) as driver_earning')
            ->where('status', OrderStatusEnum::Delivered)
            ->whereBetween('delivered_at', [$filters->fromDateTime(), $filters->toDateTime()])
            ->groupByRaw('date(delivered_at)')
            ->get()
            ->keyBy('day')
            ->all();
    }

    /**
     * Newest first, and every day in the range appears even with no trade.
     *
     * @return array<int, string>
     */
    private function datesIn(ReportFilters $filters): array
    {
        $dates = [];

        for ($day = Carbon::parse($filters->to); $day->gte(Carbon::parse($filters->from)); $day->subDay()) {
            $dates[] = $day->toDateString();
        }

        return $dates;
    }
}
