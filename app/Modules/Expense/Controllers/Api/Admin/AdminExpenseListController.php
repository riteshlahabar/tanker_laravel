<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers\Api\Admin;

use App\Modules\Core\Enums\ExpenseCategoryEnum;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Core\Support\Paginated;
use App\Modules\Expense\Contracts\ExpenseRepositoryInterface;
use App\Modules\Expense\Resources\ExpenseResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Every driver's spending, with the filters the admin screen offers.
 */
final class AdminExpenseListController
{
    public function __construct(private readonly ExpenseRepositoryInterface $expenses) {}

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::ExpenseView->value);

        $request->validate([
            'driver_id' => ['nullable', 'integer'],
            'category' => ['nullable', Rule::in(ExpenseCategoryEnum::values())],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $search = $request->string('search')->value();
        $driverId = $request->integer('driver_id');
        $category = $request->string('category')->value();

        $page = $this->expenses->visibleTo($request->user())
            ->with([
                'driver:id,first_name,last_name',
                'tanker:id,vehicle_name,vehicle_number',
                'enteredBy:id,first_name,last_name',
            ])
            ->between(
                $request->string('from')->value() ?: null,
                $request->string('to')->value() ?: null,
            )
            ->when($driverId > 0, fn (Builder $q) => $q->where('driver_id', $driverId))
            ->when($category !== '', fn (Builder $q) => $q->where('category', $category))
            ->when($search !== '', fn (Builder $q) => $q
                ->where(fn (Builder $inner) => $inner
                    ->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('driver', fn (Builder $d) => $d
                        ->whereAny(['first_name', 'last_name', 'mobile'], 'like', "%{$search}%"))))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(Paginated::perPage($request->input('per_page')));

        return ApiResponse::success(Paginated::of($page, ExpenseResource::class));
    }
}
