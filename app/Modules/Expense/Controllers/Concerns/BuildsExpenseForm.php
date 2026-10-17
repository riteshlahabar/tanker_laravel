<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers\Concerns;

use App\Modules\Core\Enums\ExpenseCategoryEnum;
use App\Modules\Driver\Contracts\DriverRepositoryInterface;
use App\Modules\Tanker\Models\Tanker;

/**
 * The dropdowns both expense forms need. Shared here rather than repeated, so
 * the create and edit screens can never offer different lists.
 */
trait BuildsExpenseForm
{
    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'driverOptions' => app(DriverRepositoryInterface::class)
                ->scopedQuery()
                ->where('status', true)
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->mapWithKeys(fn ($driver): array => [$driver->id => $driver->full_name])
                ->all(),
            // Every tanker, not just unassigned ones: an expense may well be
            // recorded against a vehicle somebody else is driving today.
            'tankerOptions' => Tanker::query()
                ->orderBy('vehicle_name')
                ->get(['id', 'vehicle_name', 'vehicle_number'])
                ->mapWithKeys(fn (Tanker $tanker): array => [$tanker->id => $tanker->label()])
                ->all(),
            'categoryOptions' => ExpenseCategoryEnum::options(),
        ];
    }
}
