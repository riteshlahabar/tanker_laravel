<?php

declare(strict_types=1);

use App\Modules\Expense\Controllers\ExpenseCreateController;
use App\Modules\Expense\Controllers\ExpenseDeleteController;
use App\Modules\Expense\Controllers\ExpenseExportController;
use App\Modules\Expense\Controllers\ExpenseListController;
use App\Modules\Expense\Controllers\ExpenseUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/expense')
    ->name('admin.expense.')
    ->group(function (): void {
        Route::get('/', ExpenseListController::class)->name('index');
        Route::get('export', ExpenseExportController::class)->middleware('throttle:exports')->name('export');

        Route::get('create', [ExpenseCreateController::class, 'form'])->name('create');
        Route::post('/', [ExpenseCreateController::class, 'store'])->name('store');

        Route::get('{expense}/edit', [ExpenseUpdateController::class, 'form'])->name('edit');
        Route::put('{expense}', [ExpenseUpdateController::class, 'update'])->name('update');

        Route::delete('{expense}', ExpenseDeleteController::class)->name('destroy');
    });
