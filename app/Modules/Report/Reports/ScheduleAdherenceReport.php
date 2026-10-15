<?php

declare(strict_types=1);

namespace App\Modules\Report\Reports;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Report\Contracts\ReportInterface;
use App\Modules\Report\Support\ReportFilters;
use App\Modules\Schedule\Contracts\ScheduleSkipRepositoryInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Services\ScheduleSkipService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Did each standing order actually happen?
 *
 * Due / delivered / skipped / missed, counted over the range. The point of the
 * **skipped** column is that a day the customer called off reads as their
 * choice rather than as the owner failing to deliver — so "missed" means only
 * what is genuinely unaccounted for.
 */
final class ScheduleAdherenceReport implements ReportInterface
{
    public function __construct(private readonly ScheduleSkipRepositoryInterface $skips) {}

    public function key(): string
    {
        return 'schedule-adherence';
    }

    public function title(): string
    {
        return 'Schedule Adherence';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Customer', 'Mobile', 'Pattern', 'Due', 'Delivered', 'Skipped', 'Cancelled', 'Missed', 'Adherence %'];
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
        return $this->figures($filters)
            ->map(fn (array $row): array => [
                $row['customer'],
                $row['mobile'],
                $row['pattern'],
                $row['due'],
                $row['delivered'],
                $row['skipped'],
                $row['cancelled'],
                $row['missed'],
                $row['adherence'],
            ])
            ->values();
    }

    /**
     * @return array<string, string>
     */
    public function totals(ReportFilters $filters): array
    {
        $rows = $this->figures($filters);
        $due = (int) $rows->sum('due');
        $delivered = (int) $rows->sum('delivered');

        return [
            'Standing orders' => (string) $rows->count(),
            'Deliveries due' => number_format($due),
            'Delivered' => number_format($delivered),
            'Called off' => number_format((float) $rows->sum('skipped')),
            'Missed' => number_format((float) $rows->sum('missed')),
            'Adherence' => $due === 0 ? '—' : round($delivered / $due * 100, 1).' %',
        ];
    }

    /**
     * One row per schedule.
     *
     * "Due" is counted from the pattern rather than from the orders table, so
     * a date the generator never reached still counts as due — otherwise a
     * broken scheduler would look like perfect adherence.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function figures(ReportFilters $filters): Collection
    {
        $from = Carbon::parse($filters->from)->startOfDay();
        $to = Carbon::parse($filters->to)->startOfDay();

        return $this->query($filters)
            ->get()
            ->map(function (DeliverySchedule $schedule) use ($from, $to, $filters): array {
                $due = $this->dueDatesIn($schedule, $from, $to);
                $orders = $this->orderCounts($schedule, $filters);
                $skipped = $this->skips->countBetween($schedule->id, $from, $to);

                // Cancellations the customer asked for are already in
                // `skipped`; this column is every other cancellation.
                $otherCancelled = max(0, $orders['cancelled'] - $orders['skip_cancelled']);
                $missed = max(0, $due - $orders['delivered'] - $skipped - $otherCancelled);

                return [
                    'customer' => $schedule->customer?->full_name ?? '—',
                    'mobile' => $schedule->customer?->mobile,
                    'pattern' => $schedule->patternLabel(),
                    'due' => $due,
                    'delivered' => $orders['delivered'],
                    'skipped' => $skipped,
                    'cancelled' => $otherCancelled,
                    'missed' => $missed,
                    'adherence' => $due === 0 ? 0.0 : round($orders['delivered'] / $due * 100, 1),
                ];
            })
            ->sortBy('adherence')
            ->values();
    }

    private function dueDatesIn(DeliverySchedule $schedule, Carbon $from, Carbon $to): int
    {
        $pattern = $schedule->pattern();
        $count = 0;
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            if ($pattern->matches($cursor)) {
                $count++;
            }

            $cursor->addDay();
        }

        return $count;
    }

    /**
     * @return array{delivered: int, cancelled: int, skip_cancelled: int}
     */
    private function orderCounts(DeliverySchedule $schedule, ReportFilters $filters): array
    {
        $inRange = fn (Builder $query): Builder => $query
            ->whereBetween('delivery_date', [$filters->from, $filters->to]);

        $base = $schedule->orders()->getQuery();

        return [
            'delivered' => (int) $inRange($base->clone())
                ->where('status', OrderStatusEnum::Delivered)->count(),
            'cancelled' => (int) $inRange($base->clone())
                ->where('status', OrderStatusEnum::Cancelled)->count(),
            'skip_cancelled' => (int) $inRange($base->clone())
                ->where('status', OrderStatusEnum::Cancelled)
                ->where('cancel_reason', ScheduleSkipService::CANCEL_REASON)
                ->count(),
        ];
    }

    private function query(ReportFilters $filters): Builder
    {
        return DeliverySchedule::query()
            ->with('customer:id,first_name,last_name,mobile')
            ->when($filters->customerId !== null, fn (Builder $q) => $q
                ->where('customer_id', $filters->customerId))
            ->when($filters->search !== null, fn (Builder $q) => $q
                ->whereHas('customer', fn (Builder $c) => $c
                    ->whereAny(['first_name', 'last_name', 'mobile'], 'like', "%{$filters->search}%")))
            ->orderBy('id');
    }
}
