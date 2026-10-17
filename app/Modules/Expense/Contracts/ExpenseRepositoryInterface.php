<?php

declare(strict_types=1);

namespace App\Modules\Expense\Contracts;

use App\Models\User;
use App\Modules\Core\Contracts\BaseRepositoryInterface;
use App\Modules\Expense\Models\DriverExpense;
use Illuminate\Database\Eloquent\Builder;

interface ExpenseRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Every expense query starts here: a driver only ever sees their own.
     */
    public function visibleTo(User $user): Builder;

    public function findForUser(User $user, int $id): DriverExpense;

    /**
     * Totals by category for one driver over a date range, plus the overall
     * sum — what the app's totals header shows.
     *
     * @return array<string, float|int>
     */
    public function summaryFor(int $driverId, string $from, string $to): array;
}
