<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers;

use App\Modules\Expense\Contracts\ExpenseServiceInterface;
use App\Modules\Expense\Controllers\Concerns\BuildsExpenseForm;
use App\Modules\Expense\Models\DriverExpense;
use App\Modules\Expense\Requests\UpdateExpenseRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ExpenseUpdateController
{
    use BuildsExpenseForm;

    public function __construct(private readonly ExpenseServiceInterface $expenses) {}

    public function form(DriverExpense $expense): View
    {
        Gate::authorize('update', $expense);

        return view('expense::edit', [
            'expense' => $expense,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, DriverExpense $expense): RedirectResponse
    {
        $this->expenses->update($expense, $request->validated());

        return redirect()
            ->route('admin.expense.index')
            ->with('success', __('Expense updated successfully.'));
    }
}
