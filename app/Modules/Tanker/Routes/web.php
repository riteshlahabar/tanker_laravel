<?php

declare(strict_types=1);

use App\Modules\Tanker\Controllers\TankerCreateController;
use App\Modules\Tanker\Controllers\TankerDeleteController;
use App\Modules\Tanker\Controllers\TankerExportController;
use App\Modules\Tanker\Controllers\TankerListController;
use App\Modules\Tanker\Controllers\TankerStatusController;
use App\Modules\Tanker\Controllers\TankerUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/tanker')
    ->name('admin.tanker.')
    ->group(function (): void {
        Route::get('/', TankerListController::class)->name('index');
        Route::get('export', TankerExportController::class)->middleware('throttle:exports')->name('export');

        Route::get('create', [TankerCreateController::class, 'form'])->name('create');
        Route::post('/', [TankerCreateController::class, 'store'])->name('store');

        Route::get('{tanker}/edit', [TankerUpdateController::class, 'form'])->name('edit');
        Route::put('{tanker}', [TankerUpdateController::class, 'update'])->name('update');

        Route::put('{tanker}/status', TankerStatusController::class)->name('status');
        Route::delete('{tanker}', TankerDeleteController::class)->name('destroy');
    });
