<?php

declare(strict_types=1);

use App\Modules\Spot\Controllers\SpotCreateController;
use App\Modules\Spot\Controllers\SpotDeleteController;
use App\Modules\Spot\Controllers\SpotExportController;
use App\Modules\Spot\Controllers\SpotListController;
use App\Modules\Spot\Controllers\SpotStatusController;
use App\Modules\Spot\Controllers\SpotUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/spot')
    ->name('admin.spot.')
    ->group(function (): void {
        Route::get('/', SpotListController::class)->name('index');
        Route::get('export', SpotExportController::class)->middleware('throttle:exports')->name('export');

        Route::get('create', [SpotCreateController::class, 'form'])->name('create');
        Route::post('/', [SpotCreateController::class, 'store'])->name('store');

        Route::get('{spot}/edit', [SpotUpdateController::class, 'form'])->name('edit');
        Route::put('{spot}', [SpotUpdateController::class, 'update'])->name('update');

        Route::put('{spot}/status', SpotStatusController::class)->name('status');
        Route::delete('{spot}', SpotDeleteController::class)->name('destroy');
    });
