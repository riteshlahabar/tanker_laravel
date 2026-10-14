<?php

declare(strict_types=1);

use App\Modules\Dashboard\Controllers\Api\DashboardSummaryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api', 'role:admin'])
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function (): void {
        Route::get('dashboard', DashboardSummaryController::class)->name('dashboard');
    });
