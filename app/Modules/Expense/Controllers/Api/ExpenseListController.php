<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Core\Support\Paginated;
use App\Modules\Expense\Contracts\ExpenseRepositoryInterface;
use App\Modules\Expense\Resources\ExpenseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The driver's own expenses, newest first.
 *
 * Scoped by visibleTo() rather than by a where clause written here, so the
 * ownership rule lives in one place for every surface that reads expenses.
 */
final class ExpenseListController
{
    public function __construct(private readonly ExpenseRepositoryInterface $expenses) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $page = $this->expenses->visibleTo($request->user())
            ->with(['tanker:id,vehicle_name,vehicle_number', 'enteredBy:id,first_name,last_name'])
            ->between(
                $request->string('from')->value() ?: null,
                $request->string('to')->value() ?: null,
            )
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(Paginated::perPage($request->input('per_page')));

        return ApiResponse::success(Paginated::of($page, ExpenseResource::class));
    }
}
