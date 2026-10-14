<?php

declare(strict_types=1);

namespace App\Modules\Report\Reports;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Report\Contracts\ReportInterface;
use App\Modules\Report\Support\ReportFilters;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * How hard each tanker worked, and who was driving it.
 */
final class TankerUtilisationReport implements ReportInterface
{
    public function key(): string
    {
        return 'tanker-utilisation';
    }

    public function title(): string
    {
        return 'Tanker Utilisation';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Tanker', 'Vehicle Number', 'Capacity (L)', 'Current Driver', 'Deliveries', 'Litres', 'Revenue'];
    }

    /**
     * @return array<int, string>
     */
    public function filterFields(): array
    {
        return ['tanker', 'search'];
    }

    /**
     * @return Collection<int, array<int, string|int|float|null>>
     */
    public function rows(ReportFilters $filters): Collection
    {
        return $this->query($filters)
            ->get()
            ->map(fn (Tanker $tanker): array => [
                $tanker->vehicle_name,
                $tanker->vehicle_number,
                $tanker->capacity_litres,
                $tanker->currentAssignment?->driver?->full_name ?? '—',
                (int) $tanker->deliveries,
                (int) $tanker->litres,
                (float) $tanker->revenue,
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
            'Tankers used' => (string) $rows->where('deliveries', '>', 0)->count(),
            'Deliveries' => number_format((float) $rows->sum('deliveries')),
            'Litres delivered' => number_format((float) $rows->sum('litres')),
            'Revenue' => '₹ '.number_format((float) $rows->sum('revenue'), 2),
        ];
    }

    private function query(ReportFilters $filters): Builder
    {
        $inRange = fn (Builder $query) => $query
            ->where('status', OrderStatusEnum::Delivered)
            ->whereBetween('delivered_at', [$filters->fromDateTime(), $filters->toDateTime()]);

        return Tanker::query()
            ->with('currentAssignment.driver:id,first_name,last_name')
            ->withCount(['orders as deliveries' => $inRange])
            ->withSum(['orders as litres' => $inRange], 'quantity_litres')
            ->withSum(['orders as revenue' => $inRange], 'order_amount')
            ->when($filters->tankerId !== null, fn (Builder $q) => $q->whereKey($filters->tankerId))
            ->when($filters->search !== null, fn (Builder $q) => $q
                ->whereAny(['vehicle_name', 'vehicle_number'], 'like', "%{$filters->search}%"))
            ->orderByDesc('deliveries')
            ->orderBy('vehicle_name');
    }
}
