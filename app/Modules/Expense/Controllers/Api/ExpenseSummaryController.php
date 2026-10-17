<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Expense\Contracts\ExpenseRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Today and this month, totalled by category — the app's totals header.
 */
final class ExpenseSummaryController
{
    public function __construct(private readonly ExpenseRepositoryInterface $expenses) {}

    public function __invoke(Request $request): JsonResponse
    {
        $driverId = $request->user()->id;
        $today = now()->toDateString();

        return ApiResponse::success([
            'today' => $this->expenses->summaryFor($driverId, $today, $today),
            'month' => $this->expenses->summaryFor(
                $driverId,
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ),
        ]);
    }
}
