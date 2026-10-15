<?php

declare(strict_types=1);

use App\Modules\Report\Controllers\CustomerOrderReportController;
use App\Modules\Report\Controllers\DailyDeliveryReportController;
use App\Modules\Report\Controllers\DriverEarningReportController;
use App\Modules\Report\Controllers\ReportExportController;
use App\Modules\Report\Controllers\ScheduleAdherenceReportController;
use App\Modules\Report\Controllers\SpotRefillReportController;
use App\Modules\Report\Controllers\TankerUtilisationReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/report')
    ->name('admin.report.')
    ->group(function (): void {
        Route::get('driver-earning', DriverEarningReportController::class)->name('driver-earning');
        Route::get('daily-delivery', DailyDeliveryReportController::class)->name('daily-delivery');
        Route::get('spot-refill', SpotRefillReportController::class)->name('spot-refill');
        Route::get('customer-order', CustomerOrderReportController::class)->name('customer-order');
        Route::get('tanker-utilisation', TankerUtilisationReportController::class)->name('tanker-utilisation');
        Route::get('schedule-adherence', ScheduleAdherenceReportController::class)->name('schedule-adherence');

        Route::get('{report}/export', ReportExportController::class)
            ->middleware('throttle:exports')
            ->name('export');
    });
