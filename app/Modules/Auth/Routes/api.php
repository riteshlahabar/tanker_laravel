<?php

declare(strict_types=1);

use App\Modules\Auth\Controllers\Api\ChangePasswordController;
use App\Modules\Auth\Controllers\Api\LoginController;
use App\Modules\Auth\Controllers\Api\LogoutController;
use App\Modules\Auth\Controllers\Api\MeController;
use App\Modules\Auth\Controllers\Api\RefreshTokenController;
use App\Modules\Auth\Controllers\Api\UpdateProfileController;
use App\Modules\Auth\Services\SanctumTokenIssuer;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('api.auth.')->group(function (): void {
    Route::post('login', LoginController::class)
        ->middleware('throttle:login')
        ->name('login');

    Route::post('refresh', RefreshTokenController::class)
        ->middleware(['auth:sanctum', 'ability:'.SanctumTokenIssuer::REFRESH_ABILITY])
        ->name('refresh');

    Route::middleware(['auth:sanctum', 'access-token', 'active', 'throttle:api'])->group(function (): void {
        Route::get('me', MeController::class)->name('me');
        Route::post('logout', LogoutController::class)->name('logout');
        Route::post('change-password', ChangePasswordController::class)->name('change-password');
        Route::put('profile', UpdateProfileController::class)->name('profile.update');
    });
});
