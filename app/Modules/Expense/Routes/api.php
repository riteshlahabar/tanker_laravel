<?php

declare(strict_types=1);

use App\Modules\Expense\Controllers\Api\Admin\AdminExpenseListController;
use App\Modules\Expense\Controllers\Api\ExpenseDeleteController;
use App\Modules\Expense\Controllers\Api\ExpenseListController;
use App\Modules\Expense\Controllers\Api\ExpenseStoreController;
use App\Modules\Expense\Controllers\Api\ExpenseSummaryController;
use App\Modules\Expense\Controllers\Api\ExpenseUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api'])->group(function (): void {

    Route::prefix('driver')->name('api.driver.')->middleware('role:driver')->group(function (): void {
        // Registered before the {expense} routes so "summary" is never read as an id.
        Route::get('expenses/summary', ExpenseSummaryController::class)->name('expenses.summary');
        Route::get('expenses', ExpenseListController::class)->name('expenses.index');
        Route::post('expenses', ExpenseStoreController::class)->name('expenses.store');
        Route::put('expenses/{expense}', ExpenseUpdateController::class)->name('expenses.update');
        Route::delete('expenses/{expense}', ExpenseDeleteController::class)->name('expenses.destroy');
    });

    Route::middleware('role:admin')->prefix('admin')->name('api.admin.')->group(function (): void {
        Route::get('expenses', AdminExpenseListController::class)->name('expenses.index');
        // Same controllers as the driver's own: filing an expense for somebody
        // else differs only in who the request names, which the request class
        // and the service already decide from the caller's role.
        Route::post('expenses', ExpenseStoreController::class)->name('expenses.store');
        Route::put('expenses/{expense}', ExpenseUpdateController::class)->name('expenses.update');
        Route::delete('expenses/{expense}', ExpenseDeleteController::class)->name('expenses.destroy');
    });
});
