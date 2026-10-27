<?php

declare(strict_types=1);

use App\Modules\Customer\Controllers\Api\CustomerDeleteController;
use App\Modules\Customer\Controllers\Api\CustomerListController;
use App\Modules\Customer\Controllers\Api\CustomerStoreController;
use App\Modules\Customer\Controllers\Api\CustomerUpdateController;
use App\Modules\Customer\Controllers\Api\Driver\CustomerLookupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api', 'role:admin'])
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function (): void {
        Route::get('customers', CustomerListController::class)->name('customers.index');
        Route::post('customers', CustomerStoreController::class)->name('customers.store');
        Route::put('customers/{customer}', CustomerUpdateController::class)->name('customers.update');
        Route::delete('customers/{customer}', CustomerDeleteController::class)->name('customers.destroy');
    });

/*
 * A driver writing an order needs to find the customer. Read-only, narrowed to
 * name/mobile/address by CustomerLookupResource, and gated on its own
 * `customer.lookup` permission — the driver never reaches the admin group
 * above, which carries the full record and the writes.
 */
Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api', 'role:driver'])
    ->prefix('driver')
    ->name('api.driver.')
    ->group(function (): void {
        Route::get('customers', CustomerLookupController::class)->name('customers.index');
    });
