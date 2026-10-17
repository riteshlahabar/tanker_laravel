<?php

declare(strict_types=1);

namespace App\Modules\Expense\Policies;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Expense\Models\DriverExpense;

/**
 * A driver may only ever touch their own rows; the admin may touch any.
 *
 * There is no approval workflow, so nothing locks a row once written — the
 * owner chose "just record it" (2026-10-17). A driver can therefore correct
 * their own mistake without asking.
 */
final class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ExpenseView->value);
    }

    public function view(User $user, DriverExpense $expense): bool
    {
        return $this->viewAny($user) && $this->ownsOrManages($user, $expense);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::ExpenseCreate->value)
            || $user->can(PermissionEnum::ExpenseManage->value);
    }

    public function update(User $user, DriverExpense $expense): bool
    {
        return $this->create($user) && $this->ownsOrManages($user, $expense);
    }

    public function delete(User $user, DriverExpense $expense): bool
    {
        return $this->update($user, $expense);
    }

    private function ownsOrManages(User $user, DriverExpense $expense): bool
    {
        return $user->can(PermissionEnum::ExpenseManage->value)
            || $expense->driver_id === $user->id;
    }
}
