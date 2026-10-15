<?php

declare(strict_types=1);

use App\Modules\Schedule\Controllers\Api\Admin\ScheduleApiDeleteController;
use App\Modules\Schedule\Controllers\Api\Admin\ScheduleApiListController;
use App\Modules\Schedule\Controllers\Api\Admin\ScheduleApiShowController;
use App\Modules\Schedule\Controllers\Api\Admin\ScheduleApiStoreController;
use App\Modules\Schedule\Controllers\Api\Admin\ScheduleApiUpdateController;
use App\Modules\Schedule\Controllers\Api\Admin\ScheduleGenerateApiController;
use App\Modules\Schedule\Controllers\Api\Admin\ScheduleSkipApiController;
use App\Modules\Schedule\Controllers\Api\Customer\MyScheduleController;
use App\Modules\Schedule\Controllers\Api\Customer\SkipDeleteController;
use App\Modules\Schedule\Controllers\Api\Customer\SkipStoreController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api'])->group(function (): void {

    /*
     * The customer's own routine. Read-only apart from calling off a single
     * date — they never create or edit a schedule, which is the whole point of
     * a standing order.
     */
    Route::prefix('customer')->name('api.customer.')->middleware('role:customer')->group(function (): void {
        Route::get('schedule', MyScheduleController::class)->name('schedule.show');
        Route::post('schedule/skips', SkipStoreController::class)->name('schedule.skips.store');
        Route::delete('schedule/skips/{date}', SkipDeleteController::class)->name('schedule.skips.destroy');
    });

    Route::prefix('admin')->name('api.admin.')->middleware('role:admin')->group(function (): void {
        Route::get('schedules', ScheduleApiListController::class)->name('schedules.index');
        Route::post('schedules', ScheduleApiStoreController::class)->name('schedules.store');
        Route::post('schedules/generate', ScheduleGenerateApiController::class)->name('schedules.generate');

        Route::get('schedules/{schedule}', ScheduleApiShowController::class)->name('schedules.show');
        Route::put('schedules/{schedule}', ScheduleApiUpdateController::class)->name('schedules.update');
        Route::delete('schedules/{schedule}', ScheduleApiDeleteController::class)->name('schedules.destroy');

        Route::get('schedules/{schedule}/skips', [ScheduleSkipApiController::class, 'index'])
            ->name('schedules.skips.index');
        Route::post('schedules/{schedule}/skips', [ScheduleSkipApiController::class, 'store'])
            ->name('schedules.skips.store');
        Route::delete('schedules/{schedule}/skips/{date}', [ScheduleSkipApiController::class, 'destroy'])
            ->name('schedules.skips.destroy');
    });
});
