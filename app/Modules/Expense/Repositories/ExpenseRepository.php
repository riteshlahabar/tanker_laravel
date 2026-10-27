<?php

declare(strict_types=1);

namespace App\Modules\Expense\Repositories;

use App\Models\User;
use App\Modules\Core\Enums\ExpenseCategoryEnum;
use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Expense\Contracts\ExpenseRepositoryInterface;
use App\Modules\Expense\Models\DriverExpense;
use App\Modules\Expense\Support\ExpenseFilters;
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
     * The admin screen's filters, applied once. Both the list and the totals
     * above it are built from this, so the figure and the rows can never
     * answer different questions.
     */
    public function filtered(User $user, ExpenseFilters $filters): Builder
    {
        return $this->visibleTo($user)
            ->between($filters->from, $filters->to)
            ->when($filters->driverId !== null, fn (Builder $q) => $q
                ->where('driver_id', $filters->driverId))
            ->when($filters->category !== null, fn (Builder $q) => $q
                ->where('category', $filters->category))
            ->when($filters->search !== null, fn (Builder $q) => $q
                ->where(fn (Builder $inner) => $inner
                    ->where('notes', 'like', "%{$filters->search}%")
                    ->orWhereHas('driver', fn (Builder $d) => $d
                        ->whereAny(['first_name', 'last_name', 'mobile'], 'like', "%{$filters->search}%"))));
    }

    /**
     * @return array<string, float|int>
     */
    public function summaryFor(int $driverId, string $from, string $to): array
    {
        return $this->summarise(
            DriverExpense::query()->where('driver_id', $driverId)->between($from, $to),
        );
    }

    /**
     * Totals by category over whatever query is handed in — one driver's
     * month for the driver's own header, or every driver under the admin's
     * filters for theirs.
     *
     * @return array<string, float|int>
     */
    public function summarise(Builder $query): array
    {
        $rows = $query
            ->reorder()
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
