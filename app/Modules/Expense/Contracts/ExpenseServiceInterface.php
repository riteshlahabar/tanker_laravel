<?php

declare(strict_types=1);

namespace App\Modules\Expense\Contracts;

use App\Models\User;
use App\Modules\Expense\Models\DriverExpense;

interface ExpenseServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, User $enteredBy): DriverExpense;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DriverExpense $expense, array $data): DriverExpense;

    public function remove(DriverExpense $expense): bool;
}
