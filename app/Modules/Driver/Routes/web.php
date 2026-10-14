<?php

declare(strict_types=1);

use App\Modules\Driver\Controllers\DriverChangeTankerController;
use App\Modules\Driver\Controllers\DriverCreateController;
use App\Modules\Driver\Controllers\DriverDeleteController;
use App\Modules\Driver\Controllers\DriverExportController;
use App\Modules\Driver\Controllers\DriverListController;
use App\Modules\Driver\Controllers\DriverStatusController;
use App\Modules\Driver\Controllers\DriverUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/driver')
    ->name('admin.driver.')
    ->group(function (): void {
        Route::get('/', DriverListController::class)->name('index');
        Route::get('export', DriverExportController::class)->middleware('throttle:exports')->name('export');

        Route::get('create', [DriverCreateController::class, 'form'])->name('create');
        Route::post('/', [DriverCreateController::class, 'store'])->name('store');

        Route::get('{driver}/edit', [DriverUpdateController::class, 'form'])->name('edit');
        Route::put('{driver}', [DriverUpdateController::class, 'update'])->name('update');

        Route::get('{driver}/tanker', [DriverChangeTankerController::class, 'form'])->name('tanker.form');
        Route::put('{driver}/tanker', [DriverChangeTankerController::class, 'update'])->name('tanker.update');

        Route::put('{driver}/status', DriverStatusController::class)->name('status');
        Route::delete('{driver}', DriverDeleteController::class)->name('destroy');
    });
