<?php

declare(strict_types=1);

use App\Modules\Report\Controllers\Api\ReportApiController;
use App\Modules\Report\Controllers\Api\ReportIndexController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api', 'role:admin'])
    ->prefix('admin/reports')
    ->name('api.admin.reports.')
    ->group(function (): void {
        Route::get('/', ReportIndexController::class)->name('index');
        Route::get('{report}', ReportApiController::class)->name('show');
    });
