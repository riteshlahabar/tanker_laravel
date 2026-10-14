<?php

declare(strict_types=1);

use App\Modules\Setting\Controllers\SettingEditController;
use App\Modules\Setting\Controllers\SettingUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin/setting')
    ->name('admin.setting.')
    ->group(function (): void {
        Route::get('/', SettingEditController::class)->name('edit');
        Route::put('/', SettingUpdateController::class)->name('update');
    });
