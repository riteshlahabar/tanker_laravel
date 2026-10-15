<?php

declare(strict_types=1);

use App\Modules\DeliverySlot\Controllers\Api\Admin\SlotAdminDeleteController;
use App\Modules\DeliverySlot\Controllers\Api\Admin\SlotAdminListController;
use App\Modules\DeliverySlot\Controllers\Api\Admin\SlotAdminStoreController;
use App\Modules\DeliverySlot\Controllers\Api\Admin\SlotAdminUpdateController;
use App\Modules\DeliverySlot\Controllers\Api\SlotApiListController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api'])
    ->group(function (): void {
        Route::get('delivery-slots', SlotApiListController::class)->name('api.delivery-slots.index');

        Route::middleware('role:admin')->prefix('admin')->name('api.admin.')->group(function (): void {
            Route::get('delivery-slots', SlotAdminListController::class)->name('delivery-slots.index');
            Route::post('delivery-slots', SlotAdminStoreController::class)->name('delivery-slots.store');
            Route::put('delivery-slots/{slot}', SlotAdminUpdateController::class)->name('delivery-slots.update');
            Route::delete('delivery-slots/{slot}', SlotAdminDeleteController::class)->name('delivery-slots.destroy');
        });
    });
