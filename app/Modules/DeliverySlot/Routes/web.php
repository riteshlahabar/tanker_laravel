<?php

declare(strict_types=1);

use App\Modules\DeliverySlot\Controllers\SlotCreateController;
use App\Modules\DeliverySlot\Controllers\SlotDeleteController;
use App\Modules\DeliverySlot\Controllers\SlotListController;
use App\Modules\DeliverySlot\Controllers\SlotStatusController;
use App\Modules\DeliverySlot\Controllers\SlotUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/slot')
    ->name('admin.slot.')
    ->group(function (): void {
        Route::get('/', SlotListController::class)->name('index');
        Route::get('create', [SlotCreateController::class, 'form'])->name('create');
        Route::post('/', [SlotCreateController::class, 'store'])->name('store');
        Route::get('{slot}/edit', [SlotUpdateController::class, 'form'])->name('edit');
        Route::put('{slot}', [SlotUpdateController::class, 'update'])->name('update');
        Route::put('{slot}/status', SlotStatusController::class)->name('status');
        Route::delete('{slot}', SlotDeleteController::class)->name('destroy');
    });
