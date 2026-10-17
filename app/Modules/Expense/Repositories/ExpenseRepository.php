<?php

declare(strict_types=1);

namespace App\Modules\Expense\Repositories;

use App\Models\User;
use App\Modules\Core\Enums\ExpenseCategoryEnum;
use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Expense\Contracts\ExpenseRepositoryInterface;
use App\Modules\Expense\Models\DriverExpense;
use Illuminate\Database\Eloquent\Builder;

final class ExpenseRepository extends BaseRepository implements ExpenseRepositoryInterface
{
    public function model(): string
    {
        return DriverExpense::class;
    }

    /**
     * The ownership rule, in the one place every query goes through — the
     * DataTable, the export, the API list and the policy all lean on this, so
     * a driver cannot reach somebody else's spending by any route.
     */
    public function visibleTo(User $user): Builder
    {
        $query = DriverExpense::query();

        return $user->isAdmin() ? $query : $query->where('driver_id', $user->id);
    }

    public function findForUser(User $user, int $id): DriverExpense
    {
        /** @var DriverExpense */
        return $this->visibleTo($user)->findOrFail($id);
    }

    /**
     * @return array<string, float|int>
     */
    public function summaryFor(int $driverId, string $from, string $to): array
    {
        $rows = DriverExpense::query()
            ->where('driver_id', $driverId)
            ->between($from, $to)
            ->selectRaw('category, count(*) as entries, sum(amount) as total')
            ->groupBy('category')
            ->get();

        // Every category appears, zero included, so the app's totals header
        // does not change shape with the data.
        $summary = ['entries' => 0, 'total' => 0.0];

        foreach (ExpenseCategoryEnum::cases() as $category) {
            $summary[$category->value] = 0.0;
        }

        foreach ($rows as $row) {
            $key = $row->category instanceof ExpenseCategoryEnum
                ? $row->category->value
                : (string) $row->category;

            $summary[$key] = (float) $row->total;
            $summary['entries'] += (int) $row->entries;
            $summary['total'] += (float) $row->total;
        }

        return $summary;
    }
}
