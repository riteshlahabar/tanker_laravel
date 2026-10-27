<?php

declare(strict_types=1);

namespace App\Modules\Expense\Contracts;

use App\Models\User;
use App\Modules\Core\Contracts\BaseRepositoryInterface;
use App\Modules\Expense\Models\DriverExpense;
use App\Modules\Expense\Support\ExpenseFilters;
use Illuminate\Database\Eloquent\Builder;

interface ExpenseRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Every expense query starts here: a driver only ever sees their own.
     */
    public function visibleTo(User $user): Builder;

    public function findForUser(User $user, int $id): DriverExpense;

    /**
     * The admin screen's filters applied to a visible-to query, so its list
     * and the spending total above it are built from the same question.
     */
    public function filtered(User $user, ExpenseFilters $filters): Builder;

    /**
     * Totals by category for one driver over a date range, plus the overall
     * sum — what the driver's own totals header shows.
     *
     * @return array<string, float|int>
     */
    public function summaryFor(int $driverId, string $from, string $to): array;

    /**
     * The same aggregate over any query — the admin's header totals whatever
     * their filters currently select.
     *
     * @return array<string, float|int>
     */
    public function summarise(Builder $query): array;
}
