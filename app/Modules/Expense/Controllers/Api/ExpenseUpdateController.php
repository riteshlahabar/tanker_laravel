<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Expense\Contracts\ExpenseServiceInterface;
use App\Modules\Expense\Models\DriverExpense;
use App\Modules\Expense\Requests\UpdateExpenseRequest;
use App\Modules\Expense\Resources\ExpenseResource;
use Illuminate\Http\JsonResponse;

/**
 * Serves both role surfaces; the policy decides who may touch which row.
 */
final class ExpenseUpdateController
{
    public function __construct(private readonly ExpenseServiceInterface $expenses) {}

    public function __invoke(UpdateExpenseRequest $request, DriverExpense $expense): JsonResponse
    {
        $updated = $this->expenses->update($expense, $request->validated());

        return ApiResponse::success(
            new ExpenseResource($updated->load(['tanker', 'driver', 'enteredBy'])),
            __('Expense updated.'),
        );
    }
}
