<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Expense\Contracts\ExpenseServiceInterface;
use App\Modules\Expense\Models\DriverExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Serves both role surfaces. The policy is what stops one driver deleting
 * another driver's row, since route binding alone would resolve any id.
 */
final class ExpenseDeleteController
{
    public function __construct(private readonly ExpenseServiceInterface $expenses) {}

    public function __invoke(Request $request, DriverExpense $expense): JsonResponse
    {
        Gate::authorize('delete', $expense);

        $this->expenses->remove($expense);

        return ApiResponse::success(null, __('Expense deleted.'));
    }
}
