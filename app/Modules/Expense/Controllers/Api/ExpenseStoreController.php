<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Expense\Contracts\ExpenseServiceInterface;
use App\Modules\Expense\Requests\StoreExpenseRequest;
use App\Modules\Expense\Resources\ExpenseResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves both role surfaces. A driver's own entry takes its owner from the
 * token; an admin's must name the driver — which is the whole point of the
 * admin route, filling in for a driver who did not record their own.
 */
final class ExpenseStoreController
{
    public function __construct(private readonly ExpenseServiceInterface $expenses) {}

    public function __invoke(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $this->expenses->record($request->validated(), $request->user());

        return ApiResponse::success(
            new ExpenseResource($expense->load(['tanker', 'driver', 'enteredBy'])),
            __('Expense recorded.'),
            Response::HTTP_CREATED,
        );
    }
}
