<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers\Api;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The driver's own delivery figures over a range, plus today's, and the salary
 * they are paid.
 *
 * `monthly_salary` sits outside the range blocks on purpose: it is a monthly
 * figure and dividing it by an arbitrary date range would produce something
 * that reads like a cost per delivery and is wrong for any range that is not
 * exactly a month.
 */
final class MySalaryController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $driver = $request->user();

        $from = $request->date('from')?->toDateString() ?? Carbon::now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? Carbon::now()->toDateString();

        return ApiResponse::success([
            'monthly_salary' => (float) $driver->monthly_salary,
            'from' => $from,
            'to' => $to,
            'range' => $this->totals($driver->id, $from, $to),
            'today' => $this->totals($driver->id, now()->toDateString(), now()->toDateString()),
        ]);
    }

    /**
     * @return array{delivered: int, tankers: int, order_value: float}
     */
    private function totals(int $driverId, string $from, string $to): array
    {
        $query = Order::query()
            ->where('driver_id', $driverId)
            ->where('status', OrderStatusEnum::Delivered)
            ->whereBetween('delivered_at', [$from.' 00:00:00', $to.' 23:59:59']);

        return [
            'delivered' => (int) $query->clone()->count(),
            'tankers' => (int) $query->clone()->sum('tanker_count'),
            'order_value' => (float) $query->clone()->sum('order_amount'),
        ];
    }
}
