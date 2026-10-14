<?php

declare(strict_types=1);

namespace App\Modules\Report\Reports;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Report\Contracts\ReportInterface;
use App\Modules\Report\Support\ReportFilters;
use App\Modules\Spot\Models\Spot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * How often each refill spot was used and what those refills cost.
 */
final class SpotRefillReport implements ReportInterface
{
    public function key(): string
    {
        return 'spot-refill';
    }

    public function title(): string
    {
        return 'Spot Refill Report';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Spot', 'Address', 'Charge per Refill', 'Refills', 'Total Refill Cost', 'Litres Delivered'];
    }

    /**
     * @return array<int, string>
     */
    public function filterFields(): array
    {
        return ['spot', 'search'];
    }

    /**
     * @return Collection<int, array<int, string|int|float|null>>
     */
    public function rows(ReportFilters $filters): Collection
    {
        return $this->query($filters)
            ->get()
            ->map(fn (Spot $spot): array => [
                $spot->name,
                $spot->address,
                (float) $spot->refill_charge,
                (int) $spot->refills,
                (float) $spot->refill_cost,
                (int) $spot->litres,
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
            'Spots used' => (string) $rows->where('refills', '>', 0)->count(),
            'Refills' => number_format((float) $rows->sum('refills')),
            'Total refill cost' => '₹ '.number_format((float) $rows->sum('refill_cost'), 2),
        ];
    }

    private function query(ReportFilters $filters): Builder
    {
        $inRange = fn (Builder $query) => $query
            ->where('status', OrderStatusEnum::Delivered)
            ->whereBetween('delivered_at', [$filters->fromDateTime(), $filters->toDateTime()]);

        return Spot::query()
            ->withCount(['orders as refills' => $inRange])
            ->withSum(['orders as refill_cost' => $inRange], 'refill_charge')
            ->withSum(['orders as litres' => $inRange], 'quantity_litres')
            ->when($filters->spotId !== null, fn (Builder $q) => $q->whereKey($filters->spotId))
            ->when($filters->search !== null, fn (Builder $q) => $q
                ->whereAny(['name', 'address'], 'like', "%{$filters->search}%"))
            ->orderByDesc('refills')
            ->orderBy('name');
    }
}
