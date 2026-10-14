<?php

declare(strict_types=1);

use App\Modules\Spot\Controllers\Api\NearbySpotController;
use App\Modules\Spot\Controllers\Api\SpotAdminListController;
use App\Modules\Spot\Controllers\Api\SpotDeleteController;
use App\Modules\Spot\Controllers\Api\SpotListController;
use App\Modules\Spot\Controllers\Api\SpotStoreController;
use App\Modules\Spot\Controllers\Api\SpotUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api'])->group(function (): void {
    // Any signed-in role may read the usable spots.
    Route::get('spots', SpotListController::class)->name('api.spots.index');
    Route::get('spots/nearby', NearbySpotController::class)->name('api.spots.nearby');

    Route::middleware('role:admin')->prefix('admin')->name('api.admin.')->group(function (): void {
        Route::get('spots', SpotAdminListController::class)->name('spots.index');
        Route::post('spots', SpotStoreController::class)->name('spots.store');
        Route::put('spots/{spot}', SpotUpdateController::class)->name('spots.update');
        Route::delete('spots/{spot}', SpotDeleteController::class)->name('spots.destroy');
    });
});
