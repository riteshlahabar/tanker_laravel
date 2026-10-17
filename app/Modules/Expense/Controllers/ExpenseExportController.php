<?php

declare(strict_types=1);

namespace App\Modules\Expense\Controllers;

use App\Modules\Expense\Exports\ExpenseExport;
use App\Modules\Expense\Models\DriverExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ExpenseExportController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', DriverExpense::class);

        $driverId = $request->integer('driver_id');

        $export = new ExpenseExport(
            driverId: $driverId > 0 ? $driverId : null,
            category: $request->string('category')->value() ?: null,
            from: $request->string('from')->value() ?: null,
            to: $request->string('to')->value() ?: null,
        );

        return Excel::download($export, 'driver_expenses_'.now()->format('Ymd_His').'.xlsx');
    }
}
