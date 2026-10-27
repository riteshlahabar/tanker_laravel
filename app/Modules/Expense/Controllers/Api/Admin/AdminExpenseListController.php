<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers\Api\Admin;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Core\Support\Paginated;
use App\Modules\Expense\Contracts\ExpenseRepositoryInterface;
use App\Modules\Expense\Resources\ExpenseResource;
use App\Modules\Expense\Support\ExpenseFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Every driver's spending, with the filters the admin screen offers.
 *
 * The filters themselves live in [ExpenseFilters] and are applied by
 * `ExpenseRepository::filtered()`, which the summary endpoint beside this one
 * also uses — so the total the screen shows above the list is the sum of
 * exactly these rows.
 */
final class AdminExpenseListController
{
    public function __construct(private readonly ExpenseRepositoryInterface $expenses) {}

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ExpenseView->value);

        $request->validate(ExpenseFilters::rules());

        $page = $this->expenses
            ->filtered($request->user(), ExpenseFilters::fromRequest($request))
            ->with([
                'driver:id,first_name,last_name',
                'tanker:id,vehicle_name,vehicle_number',
                'enteredBy:id,first_name,last_name',
            ])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(Paginated::perPage($request->input('per_page')));

        return ApiResponse::success(Paginated::of($page, ExpenseResource::class));
    }
}
