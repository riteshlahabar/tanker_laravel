<?php

declare(strict_types=1);

use App\Modules\Order\Controllers\OrderAssignController;
use App\Modules\Order\Controllers\OrderCancelController;
use App\Modules\Order\Controllers\OrderCreateController;
use App\Modules\Order\Controllers\OrderExportController;
use App\Modules\Order\Controllers\OrderListController;
use App\Modules\Order\Controllers\OrderShowController;
use App\Modules\Order\Controllers\OrderStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/order')
    ->name('admin.order.')
    ->group(function (): void {
        Route::get('/', OrderListController::class)->name('index');
        Route::get('export', OrderExportController::class)->middleware('throttle:exports')->name('export');

        Route::get('create', [OrderCreateController::class, 'form'])->name('create');
        Route::post('/', [OrderCreateController::class, 'store'])->name('store');

        Route::get('{order}', OrderShowController::class)->name('show');

        Route::get('{order}/assign', [OrderAssignController::class, 'form'])->name('assign.form');
        Route::put('{order}/assign', [OrderAssignController::class, 'update'])->name('assign.update');

        Route::put('{order}/status', OrderStatusController::class)->name('status');
        Route::put('{order}/cancel', OrderCancelController::class)->name('cancel');
    });
