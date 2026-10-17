<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers;

use App\Modules\Expense\Contracts\ExpenseServiceInterface;
use App\Modules\Expense\Controllers\Concerns\BuildsExpenseForm;
use App\Modules\Expense\Models\DriverExpense;
use App\Modules\Expense\Requests\StoreExpenseRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * The admin recording an expense on a driver's behalf — the case the owner
 * asked for, when the driver has not entered it themselves.
 */
final class ExpenseCreateController
{
    use BuildsExpenseForm;

    public function __construct(private readonly ExpenseServiceInterface $expenses) {}

    public function form(): View
    {
        Gate::authorize('create', DriverExpense::class);

        return view('expense::create', $this->formOptions());
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->expenses->record($request->validated(), $request->user());

        return redirect()
            ->route('admin.expense.index')
            ->with('success', __('Expense added successfully.'));
    }
}
