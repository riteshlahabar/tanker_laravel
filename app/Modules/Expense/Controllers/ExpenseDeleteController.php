<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers;

use App\Modules\Expense\Contracts\ExpenseServiceInterface;
use App\Modules\Expense\Models\DriverExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ExpenseDeleteController
{
    public function __construct(private readonly ExpenseServiceInterface $expenses) {}

    public function __invoke(DriverExpense $expense): RedirectResponse
    {
        Gate::authorize('delete', $expense);

        $this->expenses->remove($expense);

        return back()->with('success', __('Expense deleted successfully.'));
    }
}
