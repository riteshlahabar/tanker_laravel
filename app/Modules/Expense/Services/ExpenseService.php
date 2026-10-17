<?php

declare(strict_types=1);

namespace App\Modules\Expense\Services;

use App\Models\User;
use App\Modules\Core\Services\BaseService;
use App\Modules\Expense\Contracts\ExpenseRepositoryInterface;
use App\Modules\Expense\Contracts\ExpenseServiceInterface;
use App\Modules\Expense\Models\DriverExpense;

final class ExpenseService extends BaseService implements ExpenseServiceInterface
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    /**
     * `driver_id` is only ever taken from the caller when an admin supplies
     * it; a driver's own request has it stamped from the token, so one cannot
     * file spending against another.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, User $enteredBy): DriverExpense
    {
        $driverId = $enteredBy->isAdmin()
            ? (int) $data['driver_id']
            : $enteredBy->id;

        return $this->transaction(fn (): DriverExpense => $this->expenses->store([
            ...$data,
            'driver_id' => $driverId,
            // Who typed it. Different from driver_id exactly when the admin
            // filled in for a driver who had not.
            'created_by' => $enteredBy->id,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DriverExpense $expense, array $data): DriverExpense
    {
        // The owner of the row never changes on an edit: moving spending from
        // one driver to another would quietly rewrite both their totals.
        unset($data['driver_id'], $data['created_by']);

        return $this->transaction(function () use ($expense, $data): DriverExpense {
            $expense->update($data);

            return $expense->refresh();
        });
    }

    public function remove(DriverExpense $expense): bool
    {
        return $this->transaction(fn (): bool => (bool) $expense->delete());
    }
}
