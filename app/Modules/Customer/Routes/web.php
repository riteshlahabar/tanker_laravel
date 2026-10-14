<?php

declare(strict_types=1);

use App\Modules\Customer\Controllers\CustomerCreateController;
use App\Modules\Customer\Controllers\CustomerDeleteController;
use App\Modules\Customer\Controllers\CustomerExportController;
use App\Modules\Customer\Controllers\CustomerListController;
use App\Modules\Customer\Controllers\CustomerStatusController;
use App\Modules\Customer\Controllers\CustomerUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/customer')
    ->name('admin.customer.')
    ->group(function (): void {
        Route::get('/', CustomerListController::class)->name('index');
        Route::get('export', CustomerExportController::class)->middleware('throttle:exports')->name('export');

        Route::get('create', [CustomerCreateController::class, 'form'])->name('create');
        Route::post('/', [CustomerCreateController::class, 'store'])->name('store');

        Route::get('{customer}/edit', [CustomerUpdateController::class, 'form'])->name('edit');
        Route::put('{customer}', [CustomerUpdateController::class, 'update'])->name('update');

        Route::put('{customer}/status', CustomerStatusController::class)->name('status');
        Route::delete('{customer}', CustomerDeleteController::class)->name('destroy');
    });
