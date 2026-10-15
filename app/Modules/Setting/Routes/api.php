<?php

declare(strict_types=1);

use App\Modules\Setting\Controllers\Api\SettingShowController;
use App\Modules\Setting\Controllers\Api\SettingUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api', 'role:admin'])
    ->prefix('admin')->name('api.admin.')->group(function (): void {
        Route::get('settings', SettingShowController::class)->name('settings.show');
        Route::put('settings', SettingUpdateController::class)->name('settings.update');
    });
