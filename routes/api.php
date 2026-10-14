<?php

use Illuminate\Support\Facades\Route;

/*
 * API routes live in each module's app/Modules/<Name>/Routes/api.php and are
 * loaded by that module's service provider under the /api prefix.
 */

Route::get('health', fn () => ['success' => true, 'message' => 'ok', 'data' => null]);
