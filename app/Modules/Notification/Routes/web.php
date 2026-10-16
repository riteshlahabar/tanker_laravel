<?php

declare(strict_types=1);

use App\Modules\Notification\Controllers\SendDailyReportController;
use App\Modules\Notification\Controllers\WhatsappLogController;
use App\Modules\Notification\Controllers\WhatsappScanActionController;
use App\Modules\Notification\Controllers\WhatsappScanController;
use App\Modules\Notification\Controllers\WhatsappScanStateController;
use App\Modules\Notification\Controllers\WhatsappScanTestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/whatsapp')
    ->name('admin.whatsapp.')
    ->group(function (): void {
        Route::get('/', WhatsappLogController::class)->name('index');
        Route::post('daily-report', SendDailyReportController::class)
            ->middleware('throttle:exports')
            ->name('daily-report');

        // Pairing the owner's WhatsApp with the Node scan service.
        Route::get('scan', WhatsappScanController::class)->name('scan');
        // Polled every few seconds while the screen is open, so it carries a
        // throttle of its own.
        Route::get('scan/state', WhatsappScanStateController::class)
            ->middleware('throttle:60,1')
            ->name('scan.state');
        Route::post('scan/action', WhatsappScanActionController::class)
            ->middleware('throttle:exports')
            ->name('scan.action');
        Route::post('scan/test', WhatsappScanTestController::class)
            ->middleware('throttle:exports')
            ->name('scan.test');
    });
