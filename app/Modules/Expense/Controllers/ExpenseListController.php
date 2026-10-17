<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers;

use App\Modules\Driver\Contracts\DriverRepositoryInterface;
use App\Modules\Expense\DataTables\ExpenseDataTable;
use App\Modules\Expense\Models\DriverExpense;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class ExpenseListController
{
    public function __construct(private readonly DriverRepositoryInterface $drivers) {}

    public function __invoke(ExpenseDataTable $dataTable): View|JsonResponse
    {
        Gate::authorize('viewAny', DriverExpense::class);

        return $dataTable->render('expense::index', [
            'driverFilterOptions' => $this->drivers->scopedQuery()
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->mapWithKeys(fn ($driver): array => [$driver->id => $driver->full_name])
                ->all(),
        ]);
    }
}
