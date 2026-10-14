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
 * The driver's own earnings over a range, plus today's figures for the home
 * screen. Computed from the same snapshots the owner's report uses.
 */
final class MyEarningsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->date('from')?->toDateString() ?? Carbon::now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? Carbon::now()->toDateString();

        return ApiResponse::success([
            'from' => $from,
            'to' => $to,
            'range' => $this->totals($request->user()->id, $from, $to),
            'today' => $this->totals($request->user()->id, now()->toDateString(), now()->toDateString()),
        ]);
    }

    /**
     * @return array{delivered: int, earning: float, order_value: float}
     */
    private function totals(int $driverId, string $from, string $to): array
    {
        $query = Order::query()
            ->where('driver_id', $driverId)
            ->where('status', OrderStatusEnum::Delivered)
            ->whereBetween('delivered_at', [$from.' 00:00:00', $to.' 23:59:59']);

        return [
            'delivered' => (int) $query->clone()->count(),
            'earning' => (float) $query->clone()->sum('driver_earning'),
            'order_value' => (float) $query->clone()->sum('order_amount'),
        ];
    }
}
