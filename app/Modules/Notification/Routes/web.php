<?php

declare(strict_types=1);

use App\Modules\Notification\Controllers\SendDailyReportController;
use App\Modules\Notification\Controllers\WhatsappLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/whatsapp')
    ->name('admin.whatsapp.')
    ->group(function (): void {
        Route::get('/', WhatsappLogController::class)->name('index');
        Route::post('daily-report', SendDailyReportController::class)
            ->middleware('throttle:exports')
            ->name('daily-report');
    });
