<?php

declare(strict_types=1);

use App\Modules\Schedule\Controllers\ScheduleCreateController;
use App\Modules\Schedule\Controllers\ScheduleDeleteController;
use App\Modules\Schedule\Controllers\ScheduleExportController;
use App\Modules\Schedule\Controllers\ScheduleGenerateController;
use App\Modules\Schedule\Controllers\ScheduleListController;
use App\Modules\Schedule\Controllers\SchedulePauseController;
use App\Modules\Schedule\Controllers\ScheduleShowController;
use App\Modules\Schedule\Controllers\ScheduleSkipController;
use App\Modules\Schedule\Controllers\ScheduleStatusController;
use App\Modules\Schedule\Controllers\ScheduleUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/schedule')
    ->name('admin.schedule.')
    ->group(function (): void {
        Route::get('/', ScheduleListController::class)->name('index');

        Route::get('create', [ScheduleCreateController::class, 'form'])->name('create');
        Route::post('/', [ScheduleCreateController::class, 'store'])->name('store');

        Route::get('export', ScheduleExportController::class)
            ->middleware('throttle:exports')
            ->name('export');

        // Runs the nightly generator on demand.
        Route::post('generate', ScheduleGenerateController::class)->name('generate');

        Route::get('{schedule}', ScheduleShowController::class)->name('show');
        Route::get('{schedule}/edit', [ScheduleUpdateController::class, 'form'])->name('edit');
        Route::put('{schedule}', [ScheduleUpdateController::class, 'update'])->name('update');
        Route::put('{schedule}/status', ScheduleStatusController::class)->name('status');
        Route::delete('{schedule}', ScheduleDeleteController::class)->name('destroy');

        Route::post('{schedule}/pause', [SchedulePauseController::class, 'pause'])->name('pause');
        Route::post('{schedule}/resume', [SchedulePauseController::class, 'resume'])->name('resume');

        // Calling off one date, and putting it back.
        Route::post('{schedule}/skips', [ScheduleSkipController::class, 'store'])->name('skips.store');
        Route::delete('{schedule}/skips/{date}', [ScheduleSkipController::class, 'destroy'])
            ->name('skips.destroy');
    });
