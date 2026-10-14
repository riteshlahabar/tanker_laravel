<?php

declare(strict_types=1);

use App\Modules\Driver\Controllers\Api\DriverDeleteController;
use App\Modules\Driver\Controllers\Api\DriverListController;
use App\Modules\Driver\Controllers\Api\DriverStoreController;
use App\Modules\Driver\Controllers\Api\DriverSummaryController;
use App\Modules\Driver\Controllers\Api\DriverUpdateController;
use App\Modules\Driver\Controllers\Api\MyEarningsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api'])->group(function (): void {

    Route::prefix('driver')->name('api.driver.')->middleware('role:driver')->group(function (): void {
        Route::get('summary', DriverSummaryController::class)->name('summary');
        Route::get('earnings', MyEarningsController::class)->name('earnings');
    });

    Route::middleware('role:admin')->prefix('admin')->name('api.admin.')->group(function (): void {
        Route::get('drivers', DriverListController::class)->name('drivers.index');
        Route::post('drivers', DriverStoreController::class)->name('drivers.store');
        Route::put('drivers/{driver}', DriverUpdateController::class)->name('drivers.update');
        Route::delete('drivers/{driver}', DriverDeleteController::class)->name('drivers.destroy');
    });
});
