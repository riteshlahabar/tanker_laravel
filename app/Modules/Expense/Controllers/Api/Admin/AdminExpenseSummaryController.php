<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers\Api\Admin;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Expense\Contracts\ExpenseRepositoryInterface;
use App\Modules\Expense\Support\ExpenseFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * What the admin's Driver Expenses screen is spending, totalled by category.
 *
 * Two figures, and the difference between them is the point:
 *
 * * **`filtered`** totals exactly the rows the list endpoint returns for the
 *   same query string, so the headline figure and the rows under it always
 *   agree. With no filters that is all spending ever recorded.
 * * **`month`** is this calendar month across every driver, ignoring the
 *   filters — a fixed reference the admin can read while narrowing the list,
 *   which is the question "what are we spending" rather than "what am I
 *   looking at".
 *
 * The driver's own `/api/driver/expenses/summary` is deliberately a separate
 * endpoint: it is scoped to the token's driver and needs no filters, and
 * merging the two would mean one controller deciding what it is allowed to
 * total — the thing CLAUDE.md keeps in the repository's `visibleTo()`.
 */
final class AdminExpenseSummaryController
{
    public function __construct(private readonly ExpenseRepositoryInterface $expenses) {}

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ExpenseView->value);

        $request->validate(ExpenseFilters::rules());

        $user = $request->user();

        return ApiResponse::success([
            'filtered' => $this->expenses->summarise(
                $this->expenses->filtered($user, ExpenseFilters::fromRequest($request)),
            ),
            'month' => $this->expenses->summarise(
                $this->expenses->filtered($user, new ExpenseFilters(
                    from: now()->startOfMonth()->toDateString(),
                    to: now()->endOfMonth()->toDateString(),
                )),
            ),
        ]);
    }
}
