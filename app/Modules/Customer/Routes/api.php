<?php

declare(strict_types=1);

use App\Modules\Customer\Controllers\Api\CustomerDeleteController;
use App\Modules\Customer\Controllers\Api\CustomerListController;
use App\Modules\Customer\Controllers\Api\CustomerStoreController;
use App\Modules\Customer\Controllers\Api\CustomerUpdateController;
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
