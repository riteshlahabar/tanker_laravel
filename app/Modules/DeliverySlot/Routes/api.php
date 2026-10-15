<?php

declare(strict_types=1);

use App\Modules\DeliverySlot\Controllers\Api\SlotApiListController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api'])
    ->group(function (): void {
        Route::get('delivery-slots', SlotApiListController::class)->name('api.delivery-slots.index');
    });
