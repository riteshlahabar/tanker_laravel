<?php

declare(strict_types=1);

use App\Modules\Tanker\Controllers\Api\TankerDeleteController;
use App\Modules\Tanker\Controllers\Api\TankerListController;
use App\Modules\Tanker\Controllers\Api\TankerStoreController;
use App\Modules\Tanker\Controllers\Api\TankerUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api', 'role:admin'])
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function (): void {
        Route::get('tankers', TankerListController::class)->name('tankers.index');
        Route::post('tankers', TankerStoreController::class)->name('tankers.store');
        Route::put('tankers/{tanker}', TankerUpdateController::class)->name('tankers.update');
        Route::delete('tankers/{tanker}', TankerDeleteController::class)->name('tankers.destroy');
    });
