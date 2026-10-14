<?php

declare(strict_types=1);

use App\Modules\Auth\Controllers\Web\LoginController;
use App\Modules\Auth\Controllers\Web\LogoutController;
use App\Modules\Auth\Controllers\Web\ShowLoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', ShowLoginController::class)->name('login');
    Route::post('login', LoginController::class)->middleware('throttle:login')->name('login.attempt');
});

Route::post('logout', LogoutController::class)->middleware('auth')->name('logout');
